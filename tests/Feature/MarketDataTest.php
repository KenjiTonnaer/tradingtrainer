<?php

use App\Services\MarketData;
use Illuminate\Support\Facades\Http;

test('stock quotes retain the original dollar price and conversion evidence', function () {
    config(['services.alpaca.key' => 'test', 'services.alpaca.secret' => 'test']);
    Http::preventStrayRequests();
    Http::fake([
        '*trades/latest*' => Http::response(['trade' => ['p' => 200, 't' => now()->toISOString()]]),
        'api.frankfurter.dev/*' => Http::response(['rates' => ['EUR' => .9], 'date' => now()->toDateString()]),
    ]);
    $quote = app(MarketData::class)->quote('AAPL');
    expect($quote['price'])->toBe(180.0)->and($quote['original_price'])->toBe(200.0)->and($quote['currency'])->toBe('EUR')->and($quote['tradable'])->toBeTrue();
});

test('provider failure does not silently fabricate a market price', function () {
    Http::fake(['*' => Http::response([], 503)]);
    expect(app(MarketData::class)->quote('BTC')['status'])->toBe('unavailable');
});

test('simulation uses the selected timeframe and declares its source', function () {
    config(['trading.mode' => 'simulated']);
    $history = app(MarketData::class)->history('AAPL', '1D');
    expect($history['status'])->toBe('simulated')
        ->and(end($history['candles'])['time'] - $history['candles'][0]['time'])->toBe(86400);
});

test('an old provider timestamp remains visible but cannot execute', function () {
    Http::fake(['*' => Http::response(['lastPrice' => '50000', 'closeTime' => now()->subHour()->getTimestampMs()])]);
    $quote = app(MarketData::class)->quote('BTC');
    expect($quote['price'])->toBe(50000.0)->and($quote['status'])->toBe('delayed')->and($quote['tradable'])->toBeFalse();
});
