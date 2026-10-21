<?php

namespace App\Services;

use App\Models\PortfolioSnapshot;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PortfolioValue
{
    public function snapshot(User $user): array
    {
        // Never hold the account lock while waiting on a remote provider.
        $quotes = [];
        foreach ($user->paperPositions()->pluck('symbol') as $symbol) {
            try {
                $quotes[$symbol] = app(MarketData::class)->quote($symbol);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return ['complete' => false, 'message' => 'Niet alle posities hebben een ondersteunde koers.'];
            }
        }

        return DB::transaction(function () use ($user, $quotes) {
            // Share the same account lock as order execution for a coherent valuation.
            $wallet = $user->wallet()->lockForUpdate()->first();
            $cash = (float) ($wallet?->balance ?? 0);
            $value = 0;
            $simulated = false;
            foreach ($user->paperPositions()->get() as $position) {
                $quote = $quotes[$position->symbol] ?? null;
                if (! $quote || ! $quote['price'] || ! $quote['tradable']) {
                    return ['complete' => false, 'message' => 'Geen complete actuele waardering beschikbaar.'];
                }
                $simulated = $simulated || $quote['status'] === 'simulated';
                $value += (float) $position->quantity * $quote['price'];
            }
            PortfolioSnapshot::updateOrCreate(['user_id' => $user->id, 'date' => now()->toDateString()],
                ['equity' => round($cash + $value, 2), 'cash' => $cash, 'data_status' => $simulated ? 'simulated' : 'live']);

            return ['complete' => true, 'history' => PortfolioSnapshot::where('user_id', $user->id)->orderBy('date')->get(['date', 'equity', 'data_status'])];
        });
    }
}
