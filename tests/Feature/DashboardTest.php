<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
});

test('dashboard shows capital exposure and largest position', function () {
    $user = User::factory()->create();
    $user->wallet()->create([
        'balance' => 5000,
        'currency' => 'EUR',
    ]);
    $user->paperPositions()->create([
        'symbol' => 'AAPL',
        'asset_type' => 'stock',
        'quantity' => 10,
        'avg_buy_price' => 400,
        'total_invested' => 4000,
    ]);
    $user->paperPositions()->create([
        'symbol' => 'MSFT',
        'asset_type' => 'stock',
        'quantity' => 5,
        'avg_buy_price' => 200,
        'total_invested' => 1000,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Kapitaalblootstelling')
        ->assertSee('50,0%')
        ->assertSee('Grootste positie: AAPL (80,0% van belegd vermogen)');
});