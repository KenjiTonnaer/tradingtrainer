<?php

namespace App\Services;

use App\Models\ReplaySession;
use Illuminate\Validation\ValidationException;

class ReplayEngine
{
    public function scenario(string $name): array
    {
        $bars = [];
        $last = 100;
        // Deterministic teaching scenarios, explicitly not historical prices.
        for ($i = 0; $i < 96; $i++) {
            $drift = match ($name) {
                'omkeer' => $i < 45 ? .25 : -.32,
                'volatiliteit' => 1.1 * sin($i * 1.7),
                default => .12,
            };
            $close = max(1, $last + $drift + .35 * sin($i * .8));
            $bars[] = ['time' => $i * 900, 'open' => round($last, 4), 'high' => round(max($last, $close) + .3, 4), 'low' => round(min($last, $close) - .3, 4), 'close' => round($close, 4), 'volume' => 1000 + $i * 13];
            $last = $close;
        }

        return $bars;
    }

    public function state(ReplaySession $session): array
    {
        $visible = array_slice($session->candles, 0, $session->cursor + 1);
        // Do not expose the original date or any future candle to the browser.
        foreach ($visible as $i => &$bar) {
            $bar['time'] = 946684800 + $i * 900;
        }
        unset($bar);
        $price = (float) end($visible)['close'];
        $equity = round((float) $session->cash + (float) $session->quantity * $price, 2);
        $buys = collect($session->orders)->where('type', 'buy');
        $disciplined = $buys->filter(fn ($o) => $o['risk_pct'] <= 1 && $o['allocation_pct'] <= 20 && ! empty($o['reason']))->count();

        return ['id' => $session->id, 'symbol' => $session->symbol, 'source' => $session->source,
            'candles' => $visible, 'cursor' => $session->cursor, 'total' => count($session->candles),
            'cash' => (float) $session->cash, 'quantity' => (float) $session->quantity, 'price' => $price,
            'equity' => $equity, 'pnl' => round($equity - 10000, 2), 'orders' => $session->orders,
            'stop_loss' => $session->stop_loss, 'take_profit' => $session->take_profit,
            'curve' => $session->curve, 'max_drawdown' => (float) $session->max_drawdown,
            'completed' => $session->completed, 'evaluation' => $session->evaluation,
            'discipline' => $buys->count() ? round($disciplined / $buys->count() * 100) : null,
            'market_date' => $session->completed ? $session->market_date : null,
            'benchmark' => $session->completed ? round(($price / $session->candles[12]['close'] - 1) * 100, 2) : null];
    }

    public function action(ReplaySession $s, array $input): void
    {
        if ($s->completed) {
            throw ValidationException::withMessages(['replay' => 'Deze oefening is afgerond. Start een nieuwe sessie.']);
        }
        $action = $input['action'];
        if (in_array($action, ['buy', 'sell'])) {
            if ($existing = collect($s->orders)->firstWhere('request_id', $input['request_id'])) {
                if ($existing['type'] !== $action || round($existing['quantity'] - (float) $input['quantity'], 6) !== 0.0) {
                    throw ValidationException::withMessages(['request_id' => 'Dit ordernummer is al gebruikt. Hervat de sessie voordat je een andere order plaatst.']);
                }

                return;
            }
            $price = (float) $s->candles[$s->cursor]['close'];
            $this->fill($s, $action, (float) $input['quantity'], $price, $input);
        } else {
            if (isset($input['cursor']) && $input['cursor'] !== $s->cursor) {
                throw ValidationException::withMessages(['cursor' => 'Deze oefening is al verder afgespeeld. Hervat de sessie om te synchroniseren.']);
            }
            $end = $action === 'finish' ? count($s->candles) - 1 : min(count($s->candles) - 1, $s->cursor + $input['steps']);
            while ($s->cursor < $end) {
                $s->cursor++;
                $bar = $s->candles[$s->cursor];
                if ($s->quantity > 0) {
                    // A gap fills at the open; if both levels are touched, stop wins conservatively.
                    $stop = $s->stop_loss && $bar['low'] <= $s->stop_loss;
                    $take = $s->take_profit && $bar['high'] >= $s->take_profit;
                    if ($stop || $take) {
                        $price = $stop ? min($bar['open'], (float) $s->stop_loss) : max($bar['open'], (float) $s->take_profit);
                        $this->fill($s, 'sell', (float) $s->quantity, $price, ['reason' => $stop ? 'Stop-loss' : 'Take-profit']);
                    }
                }
                $this->mark($s);
            }
            if ($s->cursor === count($s->candles) - 1) {
                if ($s->quantity > 0) {
                    $this->fill($s, 'sell', (float) $s->quantity, (float) $s->candles[$s->cursor]['close'], ['reason' => 'Einde oefening']);
                }
                $s->completed = true;
            }
        }
        $this->mark($s);
        $s->save();
    }

    private function fill(ReplaySession $s, string $side, float $qty, float $price, array $input): void
    {
        $total = round($qty * $price, 2);
        if ($qty <= 0 || $total < .01) {
            throw ValidationException::withMessages(['quantity' => 'Minimale orderwaarde: € 0,01.']);
        }
        $equity = (float) $s->cash + (float) $s->quantity * $price;
        if ($side === 'buy') {
            if ($total > $s->cash) {
                throw ValidationException::withMessages(['quantity' => 'Onvoldoende oefensaldo.']);
            }
            if (empty($input['stop_loss']) || $input['stop_loss'] >= $price || (! empty($input['take_profit']) && $input['take_profit'] <= $price)) {
                throw ValidationException::withMessages(['stop_loss' => 'Plan een stop onder de instapprijs en een doel erboven.']);
            }
            $s->cash = round($s->cash - $total, 2);
            $s->quantity = round($s->quantity + $qty, 6);
            $s->stop_loss = $input['stop_loss'];
            $s->take_profit = $input['take_profit'] ?? null;
        } else {
            if ($qty > (float) $s->quantity) {
                throw ValidationException::withMessages(['quantity' => 'Je bezit onvoldoende in deze oefening.']);
            }
            $s->cash = round($s->cash + $total, 2);
            $s->quantity = round($s->quantity - $qty, 6);
            if ($s->quantity == 0) {
                $s->stop_loss = $s->take_profit = null;
            }
        }
        $orders = $s->orders;
        $orders[] = ['type' => $side, 'quantity' => $qty, 'price' => $price, 'total' => $total, 'step' => $s->cursor,
            'reason' => $input['reason'] ?? '', 'request_id' => $input['request_id'] ?? null,
            'risk_pct' => $side === 'buy' ? round(max(0, ($price - $input['stop_loss']) * (float) $s->quantity) / $equity * 100, 2) : null,
            'allocation_pct' => $side === 'buy' ? round((float) $s->quantity * $price / $equity * 100, 2) : null];
        $s->orders = $orders;
    }

    private function mark(ReplaySession $s): void
    {
        $equity = round($s->cash + $s->quantity * $s->candles[$s->cursor]['close'], 2);
        $s->peak_equity = max((float) $s->peak_equity, $equity);
        $s->max_drawdown = max((float) $s->max_drawdown, ($s->peak_equity - $equity) / $s->peak_equity * 100);
        $curve = $s->curve;
        $curve[$s->cursor] = ['step' => $s->cursor, 'equity' => $equity];
        $s->curve = $curve;
    }
}
