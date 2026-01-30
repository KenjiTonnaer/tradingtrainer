<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Livewire\Component;

class StockChart extends Component
{
    public string $symbol = 'AAPL';
    public string $timeframe = '1D';

    // Mapping voor Alpaca timeframes en terugkijk window
    protected array $timeframeMap = [
        '1m' => ['tf' => '1Min', 'lookback' => ['days' => 1], 'limit' => 1000],
        '5m' => ['tf' => '5Min', 'lookback' => ['days' => 5], 'limit' => 2000],
        '15m' => ['tf' => '15Min', 'lookback' => ['days' => 10], 'limit' => 4000],
        '30m' => ['tf' => '30Min', 'lookback' => ['days' => 20], 'limit' => 4000],
        '1h' => ['tf' => '1Hour', 'lookback' => ['days' => 60], 'limit' => 2000],
        // Voor 6h/12h gebruiken we 15Min bars met beperkt lookback
        '6h' => ['tf' => '15Min', 'lookback' => ['hours' => 24], 'limit' => 2000],
        '12h' => ['tf' => '15Min', 'lookback' => ['days' => 3], 'limit' => 2000],
        // Dagelijkse tijdvakken
        '1D' => ['tf' => '1Day', 'lookback' => ['years' => 1], 'limit' => 2000],
        '30D' => ['tf' => '1Day', 'lookback' => ['years' => 3], 'limit' => 2000],
        '6M' => ['tf' => '1Day', 'lookback' => ['years' => 5], 'limit' => 2000],
        '1Y' => ['tf' => '1Day', 'lookback' => ['years' => 10], 'limit' => 2000],
        'ALL' => ['tf' => '1Day', 'lookback' => ['years' => 20], 'limit' => 5000],
    ];

    public function mount(string $symbol = 'AAPL', string $timeframe = '1D')
    {
        $this->symbol = strtoupper($symbol);
        $this->timeframe = $timeframe;
    }

    public function changeTimeframe(string $timeframe)
    {
        $this->timeframe = $timeframe;
    }

    public function changeSymbol(string $symbol)
    {
        $this->symbol = strtoupper($symbol);
    }

    public function getHistoricalData()
    {
        // Detect crypto symbols and route to CoinGecko
        if ($this->isCryptoSymbol($this->symbol)) {
            return $this->getCryptoHistoricalData();
        }

        $key = config('services.alpaca.key');
        $secret = config('services.alpaca.secret');

        if (!$key || !$secret || $key === 'your_alpaca_key_id') {
            return $this->getSimulatedData();
        }

        $cfg = $this->timeframeMap[$this->timeframe] ?? $this->timeframeMap['1D'];
        $start = now();
        // Pas lookback toe
        foreach ($cfg['lookback'] as $unit => $amount) {
            $start = $start->copy()->sub($unit, $amount);
        }

        try {
            $response = Http::withHeaders([
                'APCA-API-KEY-ID' => $key,
                'APCA-API-SECRET-KEY' => $secret,
            ])->timeout(15)->get(rtrim(config('services.alpaca.base_url'), '/') . '/stocks/' . urlencode($this->symbol) . '/bars', [
                'timeframe' => $cfg['tf'],
                'start' => $start->toISOString(),
                'limit' => $cfg['limit'] ?? 2000,
                // 'adjustment' => 'raw',
                // 'feed' => 'iex', // optioneel, standaard goed voor free tier
            ]);

            if ($response->successful()) {
                $json = $response->json();
                $bars = $json['bars'] ?? ($json['results'] ?? []);
                $candles = [];
                foreach ($bars as $bar) {
                    $ts = isset($bar['t']) ? Carbon::parse($bar['t'])->timestamp : null;
                    if (!$ts) continue;
                    $candles[] = [
                        'time' => $ts,
                        'open' => (float)($bar['o'] ?? 0),
                        'high' => (float)($bar['h'] ?? 0),
                        'low' => (float)($bar['l'] ?? 0),
                        'close' => (float)($bar['c'] ?? 0),
                        'volume' => (int)($bar['v'] ?? 0),
                    ];
                }

                if (!empty($candles)) {
                    return $candles;
                }
            } else {
                Log::warning('Alpaca bars error: ' . $response->status() . ' ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Alpaca API error: ' . $e->getMessage());
        }

        return $this->getSimulatedData();
    }

    protected function getSimulatedData(): array
    {
        // Bepaal aantal candles op basis van timeframe
        $candleCount = match($this->timeframe) {
            '1m' => 390, // ~6.5 uur
            '5m' => 390,
            '15m' => 200,
            '30m' => 200,
            '1h' => 180,
            '6h' => 120,
            '12h' => 60,
            '1D' => 365,
            '30D' => 36,
            '6M' => 10,
            '1Y' => 12,
            'ALL' => 60,
            default => 100,
        };

        $candles = [];
        $basePrice = 150.0;
        $currentPrice = $basePrice;
        $now = now();

        for ($i = $candleCount; $i >= 0; $i--) {
            $time = match($this->timeframe) {
                '1m' => $now->copy()->subMinutes($i)->timestamp,
                '5m' => $now->copy()->subMinutes($i * 5)->timestamp,
                '15m' => $now->copy()->subMinutes($i * 15)->timestamp,
                '30m' => $now->copy()->subMinutes($i * 30)->timestamp,
                '1h' => $now->copy()->subHours($i)->timestamp,
                '6h' => $now->copy()->subHours($i * 6)->timestamp,
                '12h' => $now->copy()->subHours($i * 12)->timestamp,
                '1D' => $now->copy()->subDays($i)->timestamp,
                '30D' => $now->copy()->subMonths($i)->timestamp,
                '6M' => $now->copy()->subMonths($i * 6)->timestamp,
                '1Y' => $now->copy()->subYears($i)->timestamp,
                'ALL' => $now->copy()->subYears($i * 5)->timestamp,
                default => $now->copy()->subDays($i)->timestamp,
            };

            $volatility = 0.02;
            $open = $currentPrice;
            $change = ($currentPrice * $volatility * (mt_rand(-100, 100) / 100.0));
            $close = max(0.01, $currentPrice + $change);
            $high = max($open, $close) * (1 + mt_rand(0, 50) / 1000);
            $low = min($open, $close) * (1 - mt_rand(0, 50) / 1000);

            $candles[] = [
                'time' => $time,
                'open' => round($open, 2),
                'high' => round($high, 2),
                'low' => round($low, 2),
                'close' => round($close, 2),
                'volume' => mt_rand(1000000, 5000000),
            ];

            $currentPrice = $close;
        }

        return $candles;
    }

    public function getCurrentPrice()
    {
        // Crypto current price via CoinGecko
        if ($this->isCryptoSymbol($this->symbol)) {
            return $this->getCryptoCurrentPrice();
        }

        $key = config('services.alpaca.key');
        $secret = config('services.alpaca.secret');

        if (!$key || !$secret || $key === 'your_alpaca_key_id') {
            return ['c' => 150.0, 'dp' => 0.0, 'pc' => 150.0];
        }

        try {
            // Latest trade price
            $tradeResp = Http::withHeaders([
                'APCA-API-KEY-ID' => $key,
                'APCA-API-SECRET-KEY' => $secret,
            ])->timeout(10)->get(rtrim(config('services.alpaca.base_url'), '/') . '/stocks/' . urlencode($this->symbol) . '/trades/latest');

            $current = 0.0;
            if ($tradeResp->successful()) {
                $current = (float)($tradeResp->json('trade.p') ?? 0);
            }

            // Previous close from daily bars
            $barsResp = Http::withHeaders([
                'APCA-API-KEY-ID' => $key,
                'APCA-API-SECRET-KEY' => $secret,
            ])->timeout(10)->get(rtrim(config('services.alpaca.base_url'), '/') . '/stocks/' . urlencode($this->symbol) . '/bars', [
                'timeframe' => '1Day',
                'limit' => 2,
            ]);

            $pc = 0.0;
            if ($barsResp->successful()) {
                $bars = $barsResp->json('bars') ?? $barsResp->json('results') ?? [];
                if (count($bars) >= 1) {
                    $pc = (float)($bars[count($bars) - 2]['c'] ?? $bars[0]['c'] ?? 0);
                }
            }

            $dp = ($pc > 0 && $current > 0) ? (($current - $pc) / $pc) * 100.0 : 0.0;

            return [
                'c' => $current ?: $pc,
                'dp' => round($dp, 2),
                'pc' => $pc,
            ];
        } catch (\Exception $e) {
            Log::error('Alpaca quote error: ' . $e->getMessage());
        }

        return ['c' => 150.0, 'dp' => 0.0, 'pc' => 150.0];
    }

    protected function getCryptoCurrentPrice(): array
    {
        $id = $this->cryptoIdFromSymbol($this->symbol);
        if (!$id) {
            return ['c' => 0.0, 'dp' => 0.0, 'pc' => 0.0];
        }

        try {
            $resp = Http::timeout(10)->get('https://api.coingecko.com/api/v3/simple/price', [
                'ids' => $id,
                'vs_currencies' => 'usd',
                'include_24hr_change' => 'true',
            ]);

            if ($resp->successful()) {
                $data = $resp->json($id);
                $current = (float)($data['usd'] ?? 0);
                $change24h = (float)($data['usd_24h_change'] ?? 0);
                $previousClose = $current / (1 + ($change24h / 100));

                return [
                    'c' => $current,
                    'dp' => round($change24h, 2),
                    'pc' => $previousClose,
                ];
            }
        } catch (\Exception $e) {
            Log::error('CoinGecko price error: ' . $e->getMessage());
        }

        return ['c' => 0.0, 'dp' => 0.0, 'pc' => 0.0];
    }

    protected function isCryptoSymbol(string $symbol): bool
    {
        $s = strtoupper($symbol);
        return preg_match('/^(BTC|ETH|SOL|XRP|ADA|DOGE|BNB|TRX|LINK|DOT|MATIC|LTC|AVAX|ATOM|NEAR|XLM|UNI|ETC|AAVE|SUI|APT|ARB|OP|PEPE|SHIB)([-\/]?)(USD|USDT)?$/', $s) === 1;
    }

    protected function cryptoIdFromSymbol(string $symbol): ?string
    {
        $map = [
            'BTC' => 'bitcoin', 'ETH' => 'ethereum', 'SOL' => 'solana',
            'XRP' => 'ripple', 'ADA' => 'cardano', 'DOGE' => 'dogecoin',
            'BNB' => 'binancecoin', 'TRX' => 'tron', 'LINK' => 'chainlink',
            'DOT' => 'polkadot', 'MATIC' => 'polygon-pos', 'LTC' => 'litecoin',
            'AVAX' => 'avalanche-2', 'ATOM' => 'cosmos', 'NEAR' => 'near',
            'XLM' => 'stellar', 'UNI' => 'uniswap', 'ETC' => 'ethereum-classic',
            'AAVE' => 'aave', 'SUI' => 'sui', 'APT' => 'aptos',
            'ARB' => 'arbitrum', 'OP' => 'optimism', 'PEPE' => 'pepe', 'SHIB' => 'shiba-inu',
        ];
        $base = strtoupper($symbol);
        $base = preg_replace('/[^A-Z]/', '', $base);
        $base = preg_replace('/(USD|USDT)$/', '', $base);
        return $map[$base] ?? null;
    }

    protected function getCryptoHistoricalData(): array
    {
        $id = $this->cryptoIdFromSymbol($this->symbol);
        if (!$id) {
            return $this->getSimulatedData();
        }

        // Map UI timeframe to days parameter
        $days = match($this->timeframe) {
            '1m', '5m', '15m', '30m' => 1,
            '1h', '6h', '12h' => 7,
            '1D' => 30,
            '30D' => 90,
            '6M' => 180,
            '1Y' => 365,
            'ALL' => 'max',
            default => 30,
        };

        try {
            $resp = Http::timeout(15)->get('https://api.coingecko.com/api/v3/coins/' . $id . '/market_chart', [
                'vs_currency' => 'usd',
                'days' => $days,
                'interval' => $days === 1 ? 'minute' : ($days <= 7 ? 'hourly' : 'daily'),
            ]);

            if ($resp->successful()) {
                $prices = $resp->json('prices') ?? [];
                $volumes = $resp->json('total_volumes') ?? [];
                $candles = [];

                foreach ($prices as $idx => $pricePoint) {
                    $tsMs = $pricePoint[0] ?? null;
                    $price = (float)($pricePoint[1] ?? 0);
                    $vol = isset($volumes[$idx]) ? (float)($volumes[$idx][1] ?? 0) : 0;

                    if ($tsMs && $price > 0) {
                        $candles[] = [
                            'time' => intval($tsMs / 1000),
                            'open' => $price,
                            'high' => $price * 1.002,
                            'low' => $price * 0.998,
                            'close' => $price,
                            'volume' => intval($vol),
                        ];
                    }
                }

                if (!empty($candles)) {
                    return $candles;
                }
            } else {
                Log::warning('CoinGecko historical error: ' . $resp->status() . ' ' . $resp->body());
            }
        } catch (\Exception $e) {
            Log::error('CoinGecko API error: ' . $e->getMessage());
        }

        return $this->getSimulatedData();
    }

    public function render()
    {
        $historicalData = $this->getHistoricalData();
        $currentQuote = $this->getCurrentPrice();

        return view('livewire.stock-chart', [
            'historicalData' => $historicalData,
            'currentQuote' => $currentQuote,
        ]);
    }
}
