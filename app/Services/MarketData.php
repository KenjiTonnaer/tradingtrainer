<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class MarketData
{
    public function symbols(): array
    {
        return array_merge(config('trading.stocks'), config('trading.crypto'));
    }

    public function symbol(string $symbol): string
    {
        $symbol = strtoupper($symbol);
        if (! in_array($symbol, $this->symbols(), true)) {
            throw ValidationException::withMessages(['symbol' => 'Kies een ondersteund instrument.']);
        }
        return $symbol;
    }

    public function isCrypto(string $symbol): bool
    {
        return in_array($symbol, config('trading.crypto'), true);
    }

    public function simulated(string $symbol): bool
    {
        return config('trading.mode') === 'simulated' ||
            (! $this->isCrypto($symbol) && (! config('services.alpaca.key') || ! config('services.alpaca.secret') || config('services.alpaca.key') === 'your_alpaca_key_id'));
    }

    public function quote(string $symbol): array
    {
        $symbol = $this->symbol($symbol);
        return Cache::remember('quote:v2:'.config('trading.mode').':'.$symbol, 10, function () use ($symbol) {
            if ($this->simulated($symbol)) {
                return ['symbol' => $symbol, 'price' => $this->simulationPrice($symbol, intdiv(time(), 60)),
                    'currency' => 'EUR', 'source' => 'Oefensimulatie', 'status' => 'simulated',
                    'as_of' => now()->toISOString(), 'tradable' => true];
            }
            try {
                if ($this->isCrypto($symbol)) {
                    $data = Http::timeout(6)->get('https://api.binance.com/api/v3/ticker/24hr', ['symbol' => $symbol.'EUR'])->throw()->json();
                    $price = (float) ($data['lastPrice'] ?? 0);
                    $at = Carbon::createFromTimestampMs($data['closeTime']);
                    $extra = [];
                    $source = 'Binance · EUR';
                } else {
                    $data = $this->alpaca()->get(rtrim(config('services.alpaca.base_url'), '/').'/stocks/'.$symbol.'/trades/latest', ['feed' => 'iex'])->throw()->json('trade');
                    $fx = $this->exchangeRate();
                    $price = round((float) ($data['p'] ?? 0) * $fx['rate'], 4);
                    $at = Carbon::parse($data['t']);
                    $extra = ['original_price' => (float) $data['p'], 'original_currency' => 'USD', 'fx_rate' => $fx['rate'], 'fx_date' => $fx['date']];
                    $source = 'Alpaca IEX · USD → EUR (dagkoers)';
                }
                if (! is_finite($price) || $price <= 0) throw new \RuntimeException('Invalid quote');
                $fresh = $at->between(now()->subMinutes(3), now()->addMinute());
                return array_merge(['symbol' => $symbol, 'price' => $price, 'currency' => 'EUR', 'source' => $source,
                    'status' => $fresh ? 'live' : 'delayed', 'as_of' => $at->toISOString(), 'tradable' => $fresh], $extra);
            } catch (\Throwable $e) {
                return ['symbol' => $symbol, 'price' => null, 'currency' => 'EUR', 'source' => $this->isCrypto($symbol) ? 'Binance' : 'Alpaca IEX',
                    'status' => 'unavailable', 'as_of' => null, 'tradable' => false];
            }
        });
    }

    public function exchangeRate(): array
    {
        return Cache::remember('fx:usd-eur:v1', 3600, function () {
            $data = Http::timeout(6)->get('https://api.frankfurter.dev/v1/latest', ['base' => 'USD', 'symbols' => 'EUR'])->throw()->json();
            $rate = (float) ($data['rates']['EUR'] ?? 0);
            if ($rate <= 0 || Carbon::parse($data['date'])->lt(now()->subDays(7))) throw new \RuntimeException('Wisselkoers niet beschikbaar.');
            return ['rate' => $rate, 'date' => $data['date']];
        });
    }

    public function history(string $symbol, string $range = '1D', ?string $date = null): array
    {
        $symbol = $this->symbol($symbol);
        $ranges = ['1h' => [60, 1, '1Min', '1m'], '1D' => [1440, 15, '15Min', '15m'], '30D' => [43200, 1440, '1Day', '1d'], '6M' => [259200, 1440, '1Day', '1d'], '1Y' => [525600, 1440, '1Day', '1d']];
        [$lookback, $step, $tf, $interval] = $ranges[$range] ?? $ranges['1D'];
        $end = $date ? Carbon::parse($date, 'UTC')->endOfDay() : now('UTC');
        $start = $date ? Carbon::parse($date, 'UTC')->startOfDay() : $end->copy()->subMinutes($lookback);
        if ($this->simulated($symbol)) {
            if ($date) return ['candles' => [], 'source' => 'Geen historische provider ingesteld', 'status' => 'unavailable'];
            $candles = [];
            for ($minute = intdiv($start->timestamp, 60); $minute <= intdiv($end->timestamp, 60); $minute += $step) {
                $open = $this->simulationPrice($symbol, $minute - $step);
                $close = $this->simulationPrice($symbol, $minute);
                $candles[] = ['time' => $minute * 60, 'open' => $open, 'close' => $close, 'high' => max($open, $close) * 1.001, 'low' => min($open, $close) * .999, 'volume' => 0];
            }
            return ['candles' => $candles, 'source' => 'Oefensimulatie · EUR', 'status' => 'simulated'];
        }
        return Cache::remember('history:v2:'.$symbol.':'.$range.':'.$date, $date ? 86400 : 60, function () use ($symbol, $start, $end, $tf, $interval) {
            try {
                if ($this->isCrypto($symbol)) {
                    $rows = Http::timeout(8)->get('https://api.binance.com/api/v3/klines', ['symbol' => $symbol.'EUR', 'interval' => $interval, 'startTime' => $start->timestamp * 1000, 'endTime' => $end->timestamp * 1000, 'limit' => 1000])->throw()->json();
                    $candles = array_map(fn ($b) => ['time' => (int) ($b[0] / 1000), 'open' => (float) $b[1], 'high' => (float) $b[2], 'low' => (float) $b[3], 'close' => (float) $b[4], 'volume' => (float) $b[5]], $rows);
                    $source = 'Binance · EUR';
                } else {
                    $data = $this->alpaca()->get(rtrim(config('services.alpaca.base_url'), '/').'/stocks/'.$symbol.'/bars', ['timeframe' => $tf, 'start' => $start->toISOString(), 'end' => $end->toISOString(), 'limit' => 1000, 'feed' => 'iex', 'sort' => 'desc', 'adjustment' => 'split'])->throw()->json('bars') ?? [];
                    $fx = $this->exchangeRate()['rate'];
                    $candles = array_map(fn ($b) => ['time' => Carbon::parse($b['t'])->timestamp, 'open' => round($b['o'] * $fx, 4), 'high' => round($b['h'] * $fx, 4), 'low' => round($b['l'] * $fx, 4), 'close' => round($b['c'] * $fx, 4), 'volume' => (float) $b['v']], $data);
                    $source = 'Alpaca IEX · omgerekend met huidige EUR-dagkoers';
                }
                usort($candles, fn ($a, $b) => $a['time'] <=> $b['time']);
                return ['candles' => $candles, 'source' => $source, 'status' => 'historical'];
            } catch (\Throwable $e) {
                return ['candles' => [], 'source' => 'Marktdata tijdelijk niet beschikbaar', 'status' => 'unavailable'];
            }
        });
    }

    private function alpaca(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout(6)->withHeaders(['APCA-API-KEY-ID' => config('services.alpaca.key'), 'APCA-API-SECRET-KEY' => config('services.alpaca.secret')]);
    }

    private function simulationPrice(string $symbol, int $minute): float
    {
        $base = ['AAPL' => 180, 'MSFT' => 390, 'NVDA' => 115, 'TSLA' => 235, 'BTC' => 58000, 'ETH' => 2800, 'SOL' => 130][$symbol] ?? 100;
        $seed = crc32($symbol) % 1000;
        return round($base * (1 + .025 * sin($minute / 80 + $seed) + .008 * sin($minute / 9 + $seed)), 4);
    }
}
