<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
        'currency',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    /**
     * Get the user that owns the wallet
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Add funds to the wallet
     */
    public function deposit(float $amount): void
    {
        $this->increment('balance', $amount);
    }

    /**
     * Remove funds from the wallet
     */
    public function withdraw(float $amount): void
    {
        if ($amount <= 0 || ! static::whereKey($this->id)->where('balance', '>=', $amount)->decrement('balance', $amount)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['amount' => 'Onvoldoende saldo.']);
        }
        $this->refresh();
    }

    /**
     * Check if wallet has sufficient funds
     */
    public function hasSufficientFunds(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Get formatted balance
     */
    public function getFormattedBalanceAttribute(): string
    {
        return '€ ' . number_format($this->balance, 2, ',', '.');
    }
}
