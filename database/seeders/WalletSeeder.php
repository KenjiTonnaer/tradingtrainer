<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Maak 20 gebruikers met wallets
        User::factory()->count(20)->create()->each(function ($user) {
            // Creëer een wallet met een random bedrag tussen €100 en €1.000.000
            Wallet::create([
                'user_id' => $user->id,
                'balance' => fake()->randomFloat(2, 100, 1000000),
                'currency' => 'EUR',
            ]);
        });
    }
}
