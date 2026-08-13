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

    $this->postJson('/trades/buy', [
        'symbol' => 'AAPL',
        'quantity' => 10,
        'price' => 100,
        'asset_type' => 'stock',
        '_token' => 'test-token',
    ])->assertOk();

    $this->postJson('/trades/sell', [
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
