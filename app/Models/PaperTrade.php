<?php

namespace App\Models;

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

    /** Winst/verlies berekenen bij een SELL trade */
    public function getPnlAttribute(): ?float
    {
        if ($this->type !== 'sell') return null;
        // Haal gemiddelde aankoopprijs op uit positie (of via trades)
        $position = PaperPosition::where('user_id', $this->user_id)
            ->where('symbol', $this->symbol)
            ->first();
        if (!$position) return null;
        return ($this->price_per_unit - $position->avg_buy_price) * $this->quantity;
    }
}