<?php

namespace App\Console\Commands;

use App\Models\PaperPosition;
use App\Services\ExecuteTrade;
use App\Services\MarketData;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CheckPositionProtection extends Command
{
    protected $signature = 'trading:check-protection';

    protected $description = 'Controleer paper stop-loss en take-profit tegen recente serverkoersen';

    public function handle(MarketData $market, ExecuteTrade $execute): int
    {
        PaperPosition::with('user')->where(fn ($q) => $q->whereNotNull('stop_loss')->orWhereNotNull('take_profit'))->chunkById(100, function ($positions) use ($market, $execute) {
            foreach ($positions as $position) {
                try {
                    $quote = $market->quote($position->symbol);
                    if (! $quote['tradable']) {
                        continue;
                    }
                    $stop = $position->stop_loss && $quote['price'] <= $position->stop_loss;
                    $take = $position->take_profit && $quote['price'] >= $position->take_profit;
                    if (! $stop && ! $take) {
                        continue;
                    }
                    $execute->execute($position->user, 'sell', ['symbol' => $position->symbol, 'quantity' => $position->quantity,
                        'order_key' => (string) Str::uuid(), 'protection_position_id' => $position->id,
                        'entry_reason' => $stop ? 'Automatische stop-loss' : 'Automatische take-profit']);
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $this->warn('Positie '.$position->id.': '.collect($e->errors())->flatten()->first());
                }
            }
        });
        \Illuminate\Support\Facades\Cache::put('trading:protection:last_run', now()->timestamp, 180);

        return self::SUCCESS;
    }
}
