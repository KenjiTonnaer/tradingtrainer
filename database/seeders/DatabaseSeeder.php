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
        // Admin accounts eerst aanmaken
        $thijs = User::create([
            'name' => 'Thijs',
            'email' => 'thijs@gmail.com',
            'password' => Hash::make('password123'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        // Kenji account (admin)
        $kenji = User::create([
            'name' => 'Kenji',
            'email' => 'kenji@gmail.com',
            'password' => Hash::make('password123'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        // Seed overige (demo) users + wallets (ook voor admins die nog geen wallet hebben)
        $this->call(WalletSeeder::class);
    }
}
