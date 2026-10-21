<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaperTrade extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'decimal:6', 'price_per_unit' => 'decimal:4',
        'total_value' => 'decimal:2', 'wallet_balance_after' => 'decimal:2',
        'quote' => 'array', 'realized_pnl' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPnlAttribute(): ?float
    {
        if ($this->type !== 'sell') return null;
        if ($this->realized_pnl !== null) return (float) $this->realized_pnl;
        return app(\App\Services\TradeLedger::class)->forUser($this->user_id)['profits'][$this->id] ?? 0.0;
    }
}
