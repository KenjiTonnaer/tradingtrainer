<?php

namespace App\Services;

use App\Models\PaperTrade;

class TradeLedger
{
    public function calculate(iterable $trades): array
    {
        $lots = [];
        $profits = [];
        foreach ($trades as $trade) {
            $symbol = $trade->symbol;
            if ($trade->type === 'buy') {
                $lots[$symbol][] = ['quantity' => (float) $trade->quantity, 'cost' => (float) $trade->total_value];
                continue;
            }
            $remaining = (float) $trade->quantity;
            $cost = 0;
            while ($remaining > .0000001 && ! empty($lots[$symbol])) {
                $lot = &$lots[$symbol][0];
                $used = min($remaining, $lot['quantity']);
                $part = $lot['cost'] * $used / $lot['quantity'];
                $cost += $part;
                $lot['quantity'] -= $used;
                $lot['cost'] -= $part;
                $remaining -= $used;
                if ($lot['quantity'] < .0000001) array_shift($lots[$symbol]);
                unset($lot);
            }
            $profits[$trade->id] = round((float) $trade->total_value - $cost, 2);
        }
        return ['lots' => $lots, 'profits' => $profits];
    }

    public function forUser(int $userId): array
    {
        return $this->calculate(PaperTrade::where('user_id', $userId)->orderBy('created_at')->orderBy('id')->get());
    }
}
