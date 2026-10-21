<?php

use App\Livewire\StockChart;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('authenticated trading screens render without depending on a remote market provider', function () {
    config(['trading.mode' => 'simulated']);
    Http::preventStrayRequests();
    $this->actingAs(User::factory()->create());
    foreach (['/dashboard', '/markets/AAPL', '/markets/BTC', '/journal', '/replay', '/settings/profile', '/settings/appearance'] as $path) {
        $this->get($path)->assertOk()->assertSee('TradingTrainer');
    }
    Livewire::test(StockChart::class, ['symbol' => 'AAPL'])->call('changeTimeframe', '1h')->assertSet('timeframe', '1h')->assertSee('Oefensimulatie');
});

test('all account and training endpoints require authentication', function () {
    foreach (['/dashboard', '/markets/AAPL', '/journal', '/replay', '/portfolio', '/trades'] as $path) {
        $this->get($path)->assertRedirect('/login');
    }
});

test('journal filters exclude other instrument types and users', function () {
    config(['trading.mode' => 'simulated']);
    $user = User::factory()->create();
    $this->actingAs($user);
    foreach (['AAPL', 'BTC'] as $symbol) {
        $this->postJson('/trades/buy', ['symbol' => $symbol, 'quantity' => .01, 'order_key' => (string) \Illuminate\Support\Str::uuid(), 'entry_reason' => $symbol.' reason'])->assertOk();
    }
    $this->get('/journal?asset=stock')->assertOk()->assertSee('AAPL reason')->assertDontSee('BTC reason');
    $this->actingAs(User::factory()->create())->get('/journal')->assertOk()->assertDontSee('AAPL reason');
});

test('order idempotency keys cannot be reused for a different instruction', function () {
    config(['trading.mode' => 'simulated']);
    $this->actingAs(User::factory()->create());
    $key = (string) \Illuminate\Support\Str::uuid();
    $this->postJson('/trades/buy', ['symbol' => 'AAPL', 'quantity' => 1, 'order_key' => $key])->assertOk();
    $this->postJson('/trades/buy', ['symbol' => 'AAPL', 'quantity' => 2, 'order_key' => $key])->assertUnprocessable();
});
