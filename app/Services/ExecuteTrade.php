<?php

namespace App\Services;

use App\Models\PaperPosition;
use App\Models\PaperTrade;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExecuteTrade
{
    public function execute(User $user, string $side, array $input): PaperTrade
    {
        $symbol = app(MarketData::class)->symbol($input['symbol']);
        if ($existing = $user->paperTrades()->where('order_key', $input['order_key'])->first()) {
            return $this->retry($existing, $side, $symbol, $input);
        }
        $quote = app(MarketData::class)->quote($symbol);
        if (! $quote['tradable'] || ! $quote['price'] || \Illuminate\Support\Carbon::parse($quote['as_of'])->lt(now()->subMinutes(3))) {
            throw ValidationException::withMessages(['order' => 'Geen recente koers beschikbaar. Probeer het later opnieuw.']);
        }
        $qty = (float) $input['quantity'];
        $price = round((float) $quote['price'], 4);
        $total = round($qty * $price, 2);
        if ($side === 'buy' && ((! empty($input['stop_loss']) && $input['stop_loss'] >= $price) || (! empty($input['take_profit']) && $input['take_profit'] <= $price))) {
            throw ValidationException::withMessages(['order' => 'Stop-loss moet onder de instapprijs liggen en take-profit erboven.']);
        }
        if (! is_finite($qty) || $qty <= 0 || $total < .01 || $total > 999999999) {
            throw ValidationException::withMessages(['quantity' => 'Een order moet minimaal € 0,01 waard zijn.']);
        }

        return retry(5, fn () => DB::transaction(function () use ($user, $side, $input, $symbol, $quote, $qty, $price, $total) {
            $user->wallet()->firstOrCreate([], ['balance' => config('trading.starting_balance'), 'currency' => 'EUR']);
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            if ($existing = $user->paperTrades()->where('order_key', $input['order_key'])->first()) {
                return $this->retry($existing, $side, $symbol, $input);
            }
            if ($wallet->currency !== 'EUR') {
                throw ValidationException::withMessages(['order' => 'Deze rekening gebruikt geen EUR.']);
            }
            $position = $user->paperPositions()->where('symbol', $symbol)->lockForUpdate()->first();
            $strategy = $input['strategy'] ?? $position?->strategy;
            if (isset($input['protection_position_id'])) {
                if (! $position || $position->id !== $input['protection_position_id'] ||
                    (! ($position->stop_loss && $price <= $position->stop_loss) && ! ($position->take_profit && $price >= $position->take_profit))) {
                    throw ValidationException::withMessages(['order' => 'De positie is al gesloten of de bescherming is gewijzigd.']);
                }
                if (abs((float) $position->quantity - $qty) > .0000001) {
                    throw ValidationException::withMessages(['order' => 'De positie is gewijzigd; de volgende controle probeert opnieuw.']);
                }
            }
            $profit = null;
            if ($side === 'buy') {
                $debited = Wallet::whereKey($wallet->id)->where('balance', '>=', $total)->decrement('balance', $total);
                if (! $debited) {
                    throw ValidationException::withMessages(['quantity' => 'Onvoldoende saldo voor deze order.']);
                }
                $position ??= PaperPosition::create(['user_id' => $user->id, 'symbol' => $symbol, 'asset_type' => app(MarketData::class)->isCrypto($symbol) ? 'crypto' : 'stock', 'quantity' => 0, 'avg_buy_price' => 0, 'total_invested' => 0]);
                $newQty = round((float) $position->quantity + $qty, 6);
                $cost = round((float) $position->total_invested + $total, 2);
                $position->update(['quantity' => $newQty, 'total_invested' => $cost, 'avg_buy_price' => $cost / $newQty]);
                $position->update(array_filter(['stop_loss' => $input['stop_loss'] ?? null, 'take_profit' => $input['take_profit'] ?? null, 'strategy' => $strategy], fn ($v) => $v !== null));
            } else {
                if (! $position || round((float) $position->quantity - $qty, 6) < 0) {
                    throw ValidationException::withMessages(['quantity' => 'Je bezit onvoldoende van dit instrument.']);
                }
                $lots = app(TradeLedger::class)->forUser($user->id)['lots'][$symbol] ?? [];
                $remaining = $qty;
                $cost = 0;
                foreach ($lots as $lot) {
                    $used = min($remaining, $lot['quantity']);
                    $cost += $lot['cost'] * $used / $lot['quantity'];
                    $remaining -= $used;
                    if ($remaining < .0000001) {
                        break;
                    }
                }
                if ($remaining > .0000001) {
                    throw ValidationException::withMessages(['order' => 'De aankoophistorie van deze positie is onvolledig.']);
                }
                $profit = round($total - $cost, 2);
                $newQty = round((float) $position->quantity - $qty, 6);
                if ($newQty === 0.0) {
                    $position->delete();
                } else {
                    $newCost = round(max(0, array_sum(array_column($lots, 'cost')) - $cost), 2);
                    $position->update(['quantity' => $newQty, 'total_invested' => $newCost, 'avg_buy_price' => $newCost / $newQty]);
                }
                $wallet->deposit($total);
            }

            return PaperTrade::create(['user_id' => $user->id, 'symbol' => $symbol, 'type' => $side,
                'asset_type' => app(MarketData::class)->isCrypto($symbol) ? 'crypto' : 'stock',
                'quantity' => $qty, 'price_per_unit' => $price, 'total_value' => $total, 'wallet_balance_after' => $wallet->fresh()->balance,
                'status' => 'filled', 'currency' => 'EUR', 'order_key' => $input['order_key'], 'quote' => $quote, 'realized_pnl' => $profit,
                'strategy' => $strategy, 'entry_reason' => $input['entry_reason'] ?? null,
                'stop_loss' => $input['stop_loss'] ?? null, 'take_profit' => $input['take_profit'] ?? null,
                'planned_risk' => $side === 'buy' && ! empty($input['stop_loss']) ? round(($price - $input['stop_loss']) * $qty, 2) : null]);
        }, 5), 50, fn (\Throwable $error) => $error instanceof \Illuminate\Database\QueryException && in_array((int) ($error->errorInfo[1] ?? 0), [5, 6], true));
    }

    private function retry(PaperTrade $trade, string $side, string $symbol, array $input): PaperTrade
    {
        if ($trade->type !== $side || $trade->symbol !== $symbol || round((float) $trade->quantity - (float) $input['quantity'], 6) !== 0.0) {
            throw ValidationException::withMessages(['order_key' => 'Dit ordernummer is al gebruikt. Vernieuw de pagina voordat je een andere order plaatst.']);
        }

        return $trade;
    }
}
