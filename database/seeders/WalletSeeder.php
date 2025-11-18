<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Faker\Factory as Faker;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Eventueel extra demo gebruikers toevoegen (20 stuks)
        User::factory()->count(20)->create();

        // Geef elke user precies één wallet met een random bedrag
        User::whereDoesntHave('wallet')->each(function ($user) use ($faker) {
            Wallet::create([
                'user_id' => $user->id,
                'balance' => $faker->randomFloat(2, 100, 1000000),
                'currency' => 'EUR',
            ]);
        });
    }
}
