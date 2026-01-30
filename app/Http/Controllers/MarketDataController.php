<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

class MarketDataController extends Controller
{
    public function latestBar(Request $request, string $symbol): JsonResponse
    {
        $key = config('services.alpaca.key');
        $secret = config('services.alpaca.secret');
        // We only require Alpaca keys for stock symbols; crypto path doesn't need them.

        $timeframe = $request->query('timeframe', '1Min');

        // Detect crypto symbols like BTC, BTCUSD, BTC-USD, ETH, etc.
        $isCrypto = $this->isCryptoSymbol($symbol);
        if ($isCrypto) {
            return $this->cryptoLatestBar($symbol, $timeframe);
        }

        if (!$key || !$secret || $key === 'your_alpaca_key_id') {
            // Return simulated data when Alpaca keys not configured
            return response()->json($this->getSimulatedBar(), 200);
        }

        // Choose a sensible lookback window per timeframe so that
        // we reliably get at least one bar even outside market hours.
        $now = Carbon::now();
        switch ($timeframe) {
            case '1Day':
                $start = $now->copy()->subMonths(6)->toISOString();
                break;
            case '1Hour':
                $start = $now->copy()->subDays(15)->toISOString();
                break;
            case '15Min':
                $start = $now->copy()->subDays(5)->toISOString();
                break;
            case '5Min':
                $start = $now->copy()->subDays(2)->toISOString();
                break;
            case '1Min':
            default:
                $start = $now->copy()->subHours(12)->toISOString();
                break;
        }

        try {
            $resp = Http::withHeaders([
                'APCA-API-KEY-ID' => $key,
                'APCA-API-SECRET-KEY' => $secret,
            ])->timeout(10)->get(rtrim(config('services.alpaca.base_url'), '/') . '/stocks/' . urlencode($symbol) . '/bars', [
                'timeframe' => $timeframe,
                'start' => $start,
                'limit' => 1,
                'feed' => 'iex',
                // 'adjustment' => 'raw',
            ]);

            if ($resp->successful()) {
                $bars = $resp->json('bars') ?? $resp->json('results') ?? [];
                if (!empty($bars)) {
                    $bar = $bars[0];
                    $ts = isset($bar['t']) ? Carbon::parse($bar['t'])->timestamp : null;
                    return response()->json([
                        'time' => $ts,
                        'open' => (float)($bar['o'] ?? 0),
                        'high' => (float)($bar['h'] ?? 0),
                        'low' => (float)($bar['l'] ?? 0),
                        'close' => (float)($bar['c'] ?? 0),
                        'volume' => (int)($bar['v'] ?? 0),
                    ]);
                }
            } else {
                Log::warning('Alpaca latestBar error: ' . $resp->status() . ' ' . $resp->body());
            }
        } catch (\Exception $e) {
            Log::error('Alpaca latestBar exception: ' . $e->getMessage());
        }

        // No bar found; return empty with 204 to indicate no content gracefully
        return response()->json(['error' => 'No data'], 204);
    }

    private function isCryptoSymbol(string $symbol): bool
    {
        $s = strtoupper($symbol);
        return preg_match('/^(BTC|ETH|SOL|XRP|ADA|DOGE|BNB|TRX|LINK|DOT|MATIC|LTC|AVAX|ATOM|NEAR|XLM|UNI|ETC|AAVE|SUI|APT|ARB|OP|PEPE|SHIB)([-\/]?)(USD|USDT)?$/', $s) === 1;
    }

    private function cryptoIdFromSymbol(string $symbol): ?string
    {
        $map = [
            'BTC' => 'bitcoin',
            'ETH' => 'ethereum',
            'SOL' => 'solana',
            'XRP' => 'ripple',
            'ADA' => 'cardano',
            'DOGE' => 'dogecoin',
            'BNB' => 'binancecoin',
            'TRX' => 'tron',
            'LINK' => 'chainlink',
            'DOT' => 'polkadot',
            'MATIC' => 'polygon-pos',
            'LTC' => 'litecoin',
            'AVAX' => 'avalanche-2',
            'ATOM' => 'cosmos',
            'NEAR' => 'near',
            'XLM' => 'stellar',
            'UNI' => 'uniswap',
            'ETC' => 'ethereum-classic',
            'AAVE' => 'aave',
            'SUI' => 'sui',
            'APT' => 'aptos',
            'ARB' => 'arbitrum',
            'OP' => 'optimism',
            'PEPE' => 'pepe',
            'SHIB' => 'shiba-inu',
        ];
        $base = strtoupper($symbol);
        $base = preg_replace('/[^A-Z]/', '', $base);
        $base = preg_replace('/(USD|USDT)$/', '', $base);
        return $map[$base] ?? null;
    }

    private function cryptoLatestBar(string $symbol, string $timeframe): JsonResponse
    {
        $id = $this->cryptoIdFromSymbol($symbol);
        if (!$id) {
            return response()->json(['error' => 'Unknown crypto symbol'], 400);
        }

        try {
            $resp = Http::timeout(10)->get('https://api.coingecko.com/api/v3/coins/' . $id . '/market_chart', [
                'vs_currency' => 'usd',
                'days' => 1,
                'interval' => 'minute',
            ]);

            if ($resp->successful()) {
                $prices = $resp->json('prices') ?? [];
                $volumes = $resp->json('total_volumes') ?? [];
                if (!empty($prices)) {
                    $last = end($prices);
                    $tsMs = $last[0] ?? null;
                    $price = (float)($last[1] ?? 0);
                    $vol = 0;
                    if (!empty($volumes)) {
                        $vlast = end($volumes);
                        $vol = (float)($vlast[1] ?? 0);
                    }
                    return response()->json([
                        'time' => $tsMs ? intval($tsMs / 1000) : null,
                        'open' => $price,
                        'high' => $price,
                        'low' => $price,
                        'close' => $price,
                        'volume' => $vol,
                    ]);
                }
            } else {
                Log::warning('CoinGecko latestBar error: ' . $resp->status() . ' ' . $resp->body());
            }
        } catch (\Exception $e) {
            Log::error('CoinGecko latestBar exception: ' . $e->getMessage());
        }

        return response()->json(['error' => 'No data'], 204);
    }

    private function getSimulatedBar(): array
    {
        static $lastPrice = 150.0;
        $volatility = 0.002;
        $delta = $lastPrice * $volatility * (mt_rand(-100, 100) / 100.0);
        $newPrice = max(0.01, $lastPrice + $delta);

        $bar = [
            'time' => time(),
            'open' => round($lastPrice, 2),
            'high' => round(max($lastPrice, $newPrice) * 1.001, 2),
            'low' => round(min($lastPrice, $newPrice) * 0.999, 2),
            'close' => round($newPrice, 2),
            'volume' => mt_rand(1000000, 5000000),
        ];

        $lastPrice = $newPrice;
        return $bar;
    }
}
