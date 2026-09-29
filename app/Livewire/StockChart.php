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
        '6h' => ['tf' => '1Hour', 'lookback' => ['hours' => 24], 'limit' => 2000],
        '12h' => ['tf' => '1Hour', 'lookback' => ['days' => 3], 'limit' => 2000],
        // Dagelijkse tijdvakken
        '1D' => ['tf' => '1Hour', 'lookback' => ['days' => 1], 'limit' => 2000],
        '30D' => ['tf' => '1Day', 'lookback' => ['days' => 30], 'limit' => 2000],
        '6M' => ['tf' => '1Day', 'lookback' => ['months' => 6], 'limit' => 2000],
        '1Y' => ['tf' => '1Day', 'lookback' => ['years' => 1], 'limit' => 2000],
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
            '6h' => 24,
            '12h' => 72,
            '1D' => 24,
            '30D' => 30,
            '6M' => 180,
            '1Y' => 365,
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
        $pair = $this->cryptoPairFromSymbol($this->symbol);
        if (!$pair) {
            return ['c' => 0.0, 'dp' => 0.0, 'pc' => 0.0];
        }

        try {
            $resp = Http::timeout(10)->get('https://api.binance.com/api/v3/ticker/24hr', [
                'symbol' => $pair,
            ]);

            if ($resp->successful()) {
                $data = $resp->json();
                $current = (float)($data['lastPrice'] ?? 0);
                $change24h = (float)($data['priceChangePercent'] ?? 0);
                $previousClose = (float)($data['prevClosePrice'] ?? 0);

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
        $pair = $this->cryptoPairFromSymbol($this->symbol);
        if (!$pair) {
            return $this->getSimulatedData();
        }

        $config = match($this->timeframe) {
            '1m' => ['interval' => '1m', 'limit' => 1000],
            '5m' => ['interval' => '5m', 'limit' => 1000],
            '15m' => ['interval' => '15m', 'limit' => 1000],
            '30m' => ['interval' => '30m', 'limit' => 1000],
            '1h' => ['interval' => '1h', 'limit' => 1000],
            '6h' => ['interval' => '1h', 'limit' => 24],
            '12h' => ['interval' => '1h', 'limit' => 72],
            '1D' => ['interval' => '1h', 'limit' => 24],
            '30D' => ['interval' => '1d', 'limit' => 30],
            '6M' => ['interval' => '1d', 'limit' => 180],
            '1Y' => ['interval' => '1d', 'limit' => 365],
            'ALL' => ['interval' => '1d', 'limit' => 1000],
            default => ['interval' => '1d', 'limit' => 365],
        };

        try {
            $resp = Http::timeout(15)->get('https://api.binance.com/api/v3/klines', [
                'symbol' => $pair,
                'interval' => $config['interval'],
                'limit' => $config['limit'],
            ]);

            if ($resp->successful()) {
                $candles = [];

                foreach ($resp->json() as $bar) {
                    if (!empty($bar[0]) && (float)($bar[4] ?? 0) > 0) {
                        $candles[] = [
                            'time' => intval($bar[0] / 1000),
                            'open' => (float) $bar[1],
                            'high' => (float) $bar[2],
                            'low' => (float) $bar[3],
                            'close' => (float) $bar[4],
                            'volume' => (int) $bar[5],
                        ];
                    }
                }

                if (!empty($candles)) {
                    return $candles;
                }
            } else {
                Log::warning('Binance historical error: ' . $resp->status() . ' ' . $resp->body());
            }
        } catch (\Exception $e) {
            Log::error('Binance crypto API error: ' . $e->getMessage());
        }

        return $this->getSimulatedData();
    }

    protected function cryptoPairFromSymbol(string $symbol): ?string
    {
        $base = strtoupper($symbol);
        $base = preg_replace('/[^A-Z]/', '', $base);
        $base = preg_replace('/(USD|USDT)$/', '', $base);

        return $this->isCryptoSymbol($base) ? $base . 'USDT' : null;
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
