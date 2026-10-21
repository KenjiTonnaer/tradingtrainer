<?php

use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    config(['trading.mode' => 'simulated']);
    $this->user = User::factory()->create();
    $this->user->wallet()->create(['balance' => 10000, 'currency' => 'EUR']);
    $this->actingAs($this->user);
});

test('trade plans and position protection are stored and journal ownership is enforced', function () {
    $this->postJson('/trades/buy', ['symbol' => 'AAPL', 'quantity' => 2, 'order_key' => (string) Str::uuid(), 'stop_loss' => 100, 'take_profit' => 250, 'strategy' => 'Trend', 'entry_reason' => 'Een duidelijke uitbraak.'])->assertOk();
    $trade = $this->user->paperTrades()->first();
    expect($trade->strategy)->toBe('Trend')->and($trade->planned_risk)->not->toBeNull();
    $this->get('/journal')->assertOk()->assertSee('Een duidelijke uitbraak.');
    $this->patch('/journal/'.$trade->id, ['evaluation' => 'Mijn plan gevolgd.', 'discipline' => 5])->assertRedirect();
    expect($trade->fresh()->evaluation)->toBe('Mijn plan gevolgd.');
    $other = User::factory()->create();
    $this->actingAs($other)->patch('/journal/'.$trade->id, ['evaluation' => 'Inbraak'])->assertNotFound();
    $position = $this->user->paperPositions()->first();
    $this->patchJson('/portfolio/'.$position->id.'/protection', ['stop_loss' => 50])->assertNotFound();
});

test('position protection can be changed or removed', function () {
    $this->postJson('/trades/buy', ['symbol' => 'AAPL', 'quantity' => 1, 'order_key' => (string) Str::uuid()])->assertOk();
    $position = $this->user->paperPositions()->first();
    $this->patchJson('/portfolio/'.$position->id.'/protection', ['stop_loss' => 100, 'take_profit' => 250])->assertOk();
    expect((float) $position->fresh()->stop_loss)->toBe(100.0);
    $this->patchJson('/portfolio/'.$position->id.'/protection', ['stop_loss' => null, 'take_profit' => null])->assertOk();
    expect($position->fresh()->stop_loss)->toBeNull();
});

test('scheduled protection closes a triggered position using server prices', function () {
    $market = Mockery::mock(\App\Services\MarketData::class)->makePartial();
    $market->shouldReceive('quote')->andReturn(
        ['price' => 100, 'tradable' => true, 'as_of' => now()->toISOString()],
        ['price' => 89, 'tradable' => true, 'as_of' => now()->toISOString()]
    );
    app()->instance(\App\Services\MarketData::class, $market);
    $this->postJson('/trades/buy', ['symbol' => 'AAPL', 'quantity' => 10, 'stop_loss' => 90, 'order_key' => (string) Str::uuid(), 'strategy' => 'Trend'])->assertOk();
    $this->artisan('trading:check-protection')->assertSuccessful();
    expect($this->user->paperPositions()->count())->toBe(0)
        ->and($this->user->paperTrades()->where('type', 'sell')->first()->pnl)->toBe(-110.0)
        ->and($this->user->paperTrades()->where('type', 'sell')->first()->strategy)->toBe('Trend');
});

test('missing valuation does not create a zero-valued snapshot', function () {
    $this->user->paperPositions()->create(['symbol' => 'BTC', 'asset_type' => 'crypto', 'quantity' => 1, 'avg_buy_price' => 100, 'total_invested' => 100]);
    $market = Mockery::mock(\App\Services\MarketData::class)->makePartial();
    $market->shouldReceive('quote')->andReturn(['price' => null, 'tradable' => false]);
    app()->instance(\App\Services\MarketData::class, $market);
    $this->postJson('/account/snapshot')->assertOk()->assertJsonPath('complete', false);
    $this->assertDatabaseCount('portfolio_snapshots', 0);
});
