<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed users with wallets
        $this->call(WalletSeeder::class);

        // Create Thijs account
        $thijs = User::create([
            'name' => 'Thijs',
            'email' => 'thijs@gmail.com',
            'password' => Hash::make('password123'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        Wallet::create([
            'user_id' => $thijs->id,
            'balance' => 100000.00,
            'currency' => 'EUR',
        ]);

        // Create Kenji account (admin)
        $kenji = User::create([
            'name' => 'Kenji',
            'email' => 'kenji@gmail.com',
            'password' => Hash::make('password123'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        Wallet::create([
            'user_id' => $kenji->id,
            'balance' => 100000.00,
            'currency' => 'EUR',
        ]);
    }
}
