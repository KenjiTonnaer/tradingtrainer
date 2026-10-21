<?php

use App\Models\User;

it('calculates pnl on a fully closed position using the actual trade history', function () {
    $user = User::factory()->create();
    $user->wallet()->create([
        'balance' => 10000,
        'currency' => 'EUR',
    ]);

    $this->actingAs($user)
        ->withSession(['_token' => 'test-token']);

    $market = Mockery::mock(\App\Services\MarketData::class)->makePartial();
    $market->shouldReceive('quote')->andReturn(
        ['price' => 100, 'tradable' => true, 'as_of' => now()->toISOString()],
        ['price' => 120, 'tradable' => true, 'as_of' => now()->toISOString()],
    );
    app()->instance(\App\Services\MarketData::class, $market);

    $this->postJson('/trades/buy', [
        'symbol' => 'AAPL',
        'order_key' => (string) \Illuminate\Support\Str::uuid(),
        'quantity' => 10,
        'price' => 100,
        'asset_type' => 'stock',
        '_token' => 'test-token',
    ])->assertOk();

    $this->postJson('/trades/sell', [
        'order_key' => (string) \Illuminate\Support\Str::uuid(),
        'symbol' => 'AAPL',
        'quantity' => 10,
        'price' => 120,
        '_token' => 'test-token',
    ])->assertOk();

    $sellTrade = $user->paperTrades()->where('type', 'sell')->firstOrFail();

    expect($sellTrade->pnl)->toBe(200.0)
        ->and($user->paperTrades()->count())->toBe(2)
        ->and($user->paperPositions()->count())->toBe(0);
});
