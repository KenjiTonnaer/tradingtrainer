<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MarketController extends Controller
{
    /**
     * Show the trading page for a given symbol.
     */
    public function show(string $symbol)
    {
        return view('markets.show', [
            'symbol' => strtoupper($symbol),
        ]);
    }

    /**
     * Return (simulated) price series for a symbol (line data) and advance the random walk.
     */
    public function prices(string $symbol): JsonResponse
    {
        $symbol = strtoupper($symbol);
        $historyKey = "market:{$symbol}:history";
        $priceKey = "market:{$symbol}:price";

        $history = Cache::get($historyKey, []);
        $lastPrice = Cache::get($priceKey);

        if ($lastPrice === null) {
            // Seed initial price
            $lastPrice = match ($symbol) {
                'AAPL' => 180.00,
                'TSLA' => 250.00,
                'MSFT' => 400.00,
                default => 100.00,
            };
        }

        // Generate next price via bounded random walk
        $volatility = 0.002; // ~0.2% step
        $delta = $lastPrice * $volatility * (mt_rand(-100, 100) / 100.0);
        $nextPrice = max(0.01, $lastPrice + $delta);

        $point = [
            'time' => time(), // unix seconds
            'value' => round($nextPrice, 2),
        ];
        $history[] = $point;

        // Keep last 300 points
        if (count($history) > 300) {
            $history = array_slice($history, -300);
        }

        Cache::put($historyKey, $history, 3600);
        Cache::put($priceKey, $nextPrice, 3600);

        return response()->json([
            'symbol' => $symbol,
            'data' => $history,
            'last' => $point,
        ]);
    }
}
