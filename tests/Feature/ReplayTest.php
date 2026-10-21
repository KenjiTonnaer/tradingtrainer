<?php

use App\Models\ReplaySession;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->wallet()->create(['balance' => 10000, 'currency' => 'EUR']);
    $this->actingAs($this->user);
});

function startReplay($test): array
{
    return $test->postJson('/replay', ['mode' => 'scenario', 'scenario' => 'trend'])->assertOk()->json();
}

test('replay only reveals visible candles and protects sessions between accounts', function () {
    $state = startReplay($this);
    expect(count($state['candles']))->toBe(13)->and($state['total'])->toBe(96);
    $this->patchJson('/replay/'.$state['id'], ['action' => 'advance', 'steps' => 5])->assertOk()->assertJsonCount(18, 'candles');
    $this->actingAs(User::factory()->create())->getJson('/replay/'.$state['id'])->assertNotFound();
    $this->patchJson('/replay/'.$state['id'], ['action' => 'finish'])->assertNotFound();
});

test('replay orders are idempotent and never alter the normal wallet', function () {
    $state = startReplay($this);
    $input = ['action' => 'buy', 'quantity' => 1, 'stop_loss' => 95, 'reason' => 'Trend zet door', 'request_id' => (string) Str::uuid()];
    $this->patchJson('/replay/'.$state['id'], $input)->assertOk()->assertJsonPath('quantity', 1);
    $this->patchJson('/replay/'.$state['id'], $input)->assertOk()->assertJsonCount(1, 'orders');
    $this->patchJson('/replay/'.$state['id'], array_merge($input, ['quantity' => 2]))->assertUnprocessable();
    expect((float) $this->user->wallet->fresh()->balance)->toBe(10000.0)->and($this->user->paperTrades()->count())->toBe(0);
    $this->patchJson('/replay/'.$state['id'], ['action' => 'finish'])->assertOk()->assertJsonPath('completed', true)->assertJsonPath('quantity', 0);
    $this->patchJson('/replay/'.$state['id'], ['action' => 'evaluate', 'evaluation' => 'Rustig mijn plan gevolgd.'])->assertOk()->assertJsonPath('evaluation', 'Rustig mijn plan gevolgd.');
    $this->patchJson('/replay/'.$state['id'], $input)->assertUnprocessable();
});

test('replay stops fill at the opening price on a gap and stop wins ambiguous candles', function () {
    $state = startReplay($this);
    $session = ReplaySession::find($state['id']);
    $bars = $session->candles;
    $bars[13] = ['time' => 13 * 900, 'open' => 90, 'high' => 120, 'low' => 85, 'close' => 100, 'volume' => 1];
    $session->update(['candles' => $bars]);
    $this->patchJson('/replay/'.$state['id'], ['action' => 'buy', 'quantity' => 1, 'stop_loss' => 95, 'take_profit' => 110, 'reason' => 'Uitbraak met stop', 'request_id' => (string) Str::uuid()])->assertOk();
    $result = $this->patchJson('/replay/'.$state['id'], ['action' => 'advance', 'steps' => 1])->assertOk();
    expect($result->json('orders.1.price'))->toBe(90)->and($result->json('orders.1.reason'))->toBe('Stop-loss')->and($result->json('quantity'))->toBe(0);
});

test('replay page renders and rejects missing plans or oversized orders', function () {
    $this->get('/replay')->assertOk()->assertSee('Oefen het beslissen.');
    $state = startReplay($this);
    $this->patchJson('/replay/'.$state['id'], ['action' => 'buy', 'quantity' => 1, 'request_id' => (string) Str::uuid()])->assertUnprocessable();
    $this->patchJson('/replay/'.$state['id'], ['action' => 'buy', 'quantity' => 999999, 'stop_loss' => 95, 'reason' => 'Te grote positie', 'request_id' => (string) Str::uuid()])->assertUnprocessable();
});

test('historical surprise days and future prices stay hidden until completion', function () {
    $market = Mockery::mock(\App\Services\MarketData::class)->makePartial();
    $market->shouldReceive('history')->once()->andReturn(['candles' => app(\App\Services\ReplayEngine::class)->scenario('trend'), 'status' => 'historical', 'source' => 'Test EUR source']);
    app()->instance(\App\Services\MarketData::class, $market);
    $state = $this->postJson('/replay', ['mode' => 'historical', 'symbol' => 'BTC'])->assertOk()->assertJsonPath('market_date', null)->assertJsonCount(13, 'candles')->json();
    expect($state)->not->toHaveKey('future_candles');
    $result = $this->patchJson('/replay/'.$state['id'], ['action' => 'finish'])->assertOk();
    expect($result->json('market_date'))->not->toBeNull();
});

test('repeated advance requests cannot accidentally skip more candles', function () {
    $state = startReplay($this);
    $input = ['action' => 'advance', 'steps' => 5, 'cursor' => 12];
    $this->patchJson('/replay/'.$state['id'], $input)->assertOk()->assertJsonPath('cursor', 17);
    $this->patchJson('/replay/'.$state['id'], $input)->assertUnprocessable();
});
