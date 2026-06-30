<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaperPosition extends Model
{
    protected $fillable = [
        'user_id', 'symbol', 'asset_type',
        'quantity', 'avg_buy_price', 'total_invested',
    ];

    protected $casts = [
        'quantity'       => 'decimal:6',
        'avg_buy_price'  => 'decimal:4',
        'total_invested' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Huidige waarde op basis van live prijs */
    public function getCurrentValue(float $currentPrice): float
    {
        return round((float)$this->quantity * $currentPrice, 2);
    }

    /** Winst/verlies in euros */
    public function getUnrealizedPnl(float $currentPrice): float
    {
        return round($this->getCurrentValue($currentPrice) - (float)$this->total_invested, 2);
    }

    /** Winst/verlies in % */
    public function getUnrealizedPnlPercent(float $currentPrice): float
    {
        if ($this->total_invested == 0) return 0;
        return round(($this->getUnrealizedPnl($currentPrice) / (float)$this->total_invested) * 100, 2);
    }
}