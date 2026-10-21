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
        if ($existing = $user->paperTrades()->where('order_key', $input['order_key'])->first()) return $existing;
        $quote = app(MarketData::class)->quote($symbol);
        if (! $quote['tradable'] || ! $quote['price'] || \Illuminate\Support\Carbon::parse($quote['as_of'])->lt(now()->subMinutes(3))) {
            throw ValidationException::withMessages(['order' => 'Geen recente koers beschikbaar. Probeer het later opnieuw.']);
        }
        $qty = (float) $input['quantity'];
        $price = round((float) $quote['price'], 4);
        $total = round($qty * $price, 2);
        if (! is_finite($qty) || $qty <= 0 || $total < .01 || $total > 999999999) {
            throw ValidationException::withMessages(['quantity' => 'Een order moet minimaal € 0,01 waard zijn.']);
        }
        return DB::transaction(function () use ($user, $side, $input, $symbol, $quote, $qty, $price, $total) {
            $user->wallet()->firstOrCreate([], ['balance' => config('trading.starting_balance'), 'currency' => 'EUR']);
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            if ($existing = $user->paperTrades()->where('order_key', $input['order_key'])->first()) return $existing;
            if ($wallet->currency !== 'EUR') throw ValidationException::withMessages(['order' => 'Deze rekening gebruikt geen EUR.']);
            $position = $user->paperPositions()->where('symbol', $symbol)->lockForUpdate()->first();
            $profit = null;
            if ($side === 'buy') {
                $debited = Wallet::whereKey($wallet->id)->where('balance', '>=', $total)->decrement('balance', $total);
                if (! $debited) throw ValidationException::withMessages(['quantity' => 'Onvoldoende saldo voor deze order.']);
                $position ??= PaperPosition::create(['user_id' => $user->id, 'symbol' => $symbol, 'asset_type' => app(MarketData::class)->isCrypto($symbol) ? 'crypto' : 'stock', 'quantity' => 0, 'avg_buy_price' => 0, 'total_invested' => 0]);
                $newQty = round((float) $position->quantity + $qty, 6);
                $cost = round((float) $position->total_invested + $total, 2);
                $position->update(['quantity' => $newQty, 'total_invested' => $cost, 'avg_buy_price' => $cost / $newQty]);
            } else {
                if (! $position || round((float) $position->quantity - $qty, 6) < 0) throw ValidationException::withMessages(['quantity' => 'Je bezit onvoldoende van dit instrument.']);
                $lots = app(TradeLedger::class)->forUser($user->id)['lots'][$symbol] ?? [];
                $remaining = $qty;
                $cost = 0;
                foreach ($lots as $lot) {
                    $used = min($remaining, $lot['quantity']);
                    $cost += $lot['cost'] * $used / $lot['quantity'];
                    $remaining -= $used;
                    if ($remaining < .0000001) break;
                }
                if ($remaining > .0000001) throw ValidationException::withMessages(['order' => 'De aankoophistorie van deze positie is onvolledig.']);
                $profit = round($total - $cost, 2);
                $newQty = round((float) $position->quantity - $qty, 6);
                if ($newQty === 0.0) {
                    $position->delete();
                } else {
                    $newCost = round(max(0, (float) $position->total_invested - $cost), 2);
                    $position->update(['quantity' => $newQty, 'total_invested' => $newCost, 'avg_buy_price' => $newCost / $newQty]);
                }
                $wallet->deposit($total);
            }
            return PaperTrade::create(['user_id' => $user->id, 'symbol' => $symbol, 'type' => $side,
                'asset_type' => app(MarketData::class)->isCrypto($symbol) ? 'crypto' : 'stock',
                'quantity' => $qty, 'price_per_unit' => $price, 'total_value' => $total, 'wallet_balance_after' => $wallet->fresh()->balance,
                'status' => 'filled', 'currency' => 'EUR', 'order_key' => $input['order_key'], 'quote' => $quote, 'realized_pnl' => $profit]);
        }, 5);
    }
}
