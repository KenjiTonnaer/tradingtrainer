<?php

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use App\Services\MarketData;
use Illuminate\Support\Str;

beforeEach(function () {
    config(['trading.mode' => 'simulated']);
    $this->user = User::factory()->create();
    $this->user->wallet()->create(['balance' => 10000, 'currency' => 'EUR']);
    $this->actingAs($this->user);
});

function orderInput(array $extra = []): array
{
    return array_merge(['symbol' => 'AAPL', 'quantity' => 1, 'order_key' => (string) Str::uuid()], $extra);
}

test('registration creates its own funded wallet', function () {
    $user = app(CreateNewUser::class)->create(['name' => 'Trader', 'email' => 'new@example.test', 'password' => 'password123', 'password_confirmation' => 'password123']);
    expect((float) $user->wallet->balance)->toBe(10000.0);
});

test('server ignores a tampered execution price and retries do not double fill', function () {
    $input = orderInput(['price' => .01]);
    $price = app(MarketData::class)->quote('AAPL')['price'];
    $this->postJson('/trades/buy', $input)->assertOk();
    $this->postJson('/trades/buy', $input)->assertOk();
    expect($this->user->paperTrades()->count())->toBe(1)
        ->and((float) $this->user->paperTrades()->first()->price_per_unit)->toBe($price)
        ->and((float) $this->user->wallet->fresh()->balance)->toBe(round(10000 - $price, 2));
});

test('insufficient funds and oversells leave the ledger unchanged', function () {
    $this->postJson('/trades/buy', orderInput(['quantity' => 100000]))->assertUnprocessable();
    $this->postJson('/trades/sell', orderInput())->assertUnprocessable();
    expect($this->user->paperTrades()->count())->toBe(0)
        ->and((float) $this->user->wallet->fresh()->balance)->toBe(10000.0);
});

test('invalid symbols fractional precision and zero cent orders are rejected', function () {
    foreach ([['symbol' => 'INVALID'], ['quantity' => .0000001], ['quantity' => .000001]] as $extra) {
        $this->postJson('/trades/buy', orderInput($extra))->assertUnprocessable();
    }
});

test('unavailable quotes cannot execute', function () {
    $market = Mockery::mock(MarketData::class)->makePartial();
    $market->shouldReceive('quote')->andReturn(['tradable' => false, 'price' => null]);
    app()->instance(MarketData::class, $market);
    $this->postJson('/trades/buy', orderInput())->assertUnprocessable();
});

test('partial fifo sales preserve the first lot and remaining position cost', function () {
    $market = Mockery::mock(MarketData::class)->makePartial();
    $market->shouldReceive('quote')->andReturnUsing(function () {
        static $prices = [100, 200, 300, 300];
        return ['price' => array_shift($prices), 'tradable' => true, 'as_of' => now()->toISOString()];
    });
    app()->instance(MarketData::class, $market);
    $this->postJson('/trades/buy', orderInput(['quantity' => 10]))->assertOk();
    $this->postJson('/trades/buy', orderInput(['quantity' => 10]))->assertOk();
    $this->postJson('/trades/sell', orderInput(['quantity' => 5]))->assertOk();
    $this->postJson('/trades/sell', orderInput(['quantity' => 5]))->assertOk();
    expect((float) $this->user->paperPositions()->first()->total_invested)->toBe(2000.0)
        ->and($this->user->paperTrades()->where('type', 'sell')->get()->map->pnl->all())->toBe([1000.0, 1000.0]);
});
