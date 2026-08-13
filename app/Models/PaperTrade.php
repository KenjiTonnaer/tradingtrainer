<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaperTrade extends Model
{
    protected $fillable = [
        'user_id', 'symbol', 'type', 'asset_type',
        'quantity', 'price_per_unit', 'total_value',
        'wallet_balance_after', 'status', 'notes',
    ];

    protected $casts = [
        'quantity'             => 'decimal:6',
        'price_per_unit'       => 'decimal:4',
        'total_value'          => 'decimal:2',
        'wallet_balance_after' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Winst/verlies berekenen bij een SELL trade op basis van de werkelijke trade historie */
    public function getPnlAttribute(): ?float
    {
        if ($this->type !== 'sell') {
            return null;
        }

        $trades = $this->user()
            ->getQuery()
            ->join('paper_trades as pt', 'pt.user_id', '=', 'users.id')
            ->where('pt.symbol', $this->symbol)
            ->where('pt.created_at', '<=', $this->created_at)
            ->orderBy('pt.created_at')
            ->orderBy('pt.id')
            ->select('pt.*')
            ->get();

        $lots = [];

        foreach ($trades as $trade) {
            if ($trade->type === 'buy') {
                $lots[] = [
                    'quantity' => (float) $trade->quantity,
                    'price'    => (float) $trade->price_per_unit,
                ];
                continue;
            }

            if ($trade->type !== 'sell') {
                continue;
            }

            $remaining = (float) $trade->quantity;
            $costBasis = 0.0;

            while ($remaining > 0 && ! empty($lots)) {
                $lot = array_shift($lots);
                $used = min((float) $lot['quantity'], $remaining);
                $costBasis += $used * (float) $lot['price'];
                $remaining -= $used;

                if ((float) $lot['quantity'] > $used) {
                    $lots[] = [
                        'quantity' => (float) $lot['quantity'] - $used,
                        'price'    => (float) $lot['price'],
                    ];
                }
            }

            if ($trade->id === $this->id) {
                return round((float) $trade->total_value - $costBasis, 2);
            }
        }

        return 0.0;
    }
}