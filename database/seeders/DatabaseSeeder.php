<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use App\Models\PaperPosition;
use App\Models\PaperTrade;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin accounts eerst aanmaken
        User::firstOrCreate(['email' => 'thijs@gmail.com'], [
            'name' => 'Thijs',
            'password' => Hash::make('password123'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        // Kenji account (admin)
        $kenji = User::firstOrCreate(['email' => 'kenji@gmail.com'], [
            'name' => 'Kenji',
            'password' => Hash::make('password123'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        // Seed overige (demo) users + wallets (ook voor admins die nog geen wallet hebben)
        $this->call(WalletSeeder::class);

        $this->seedKenjiPortfolio($kenji);
    }

    private function seedKenjiPortfolio(User $kenji): void
    {
        if ($kenji->paperTrades()->exists() || $kenji->paperPositions()->exists()) {
            return;
        }

        $startingBalance = 10000.00;
        $wallet = Wallet::updateOrCreate(
            ['user_id' => $kenji->id],
            ['balance' => $startingBalance, 'currency' => 'EUR']
        );

        $positions = [
            ['symbol' => 'AAPL', 'asset_type' => 'stock',  'quantity' => 8,     'price' => 182.50, 'days_ago' => 42],
            ['symbol' => 'MSFT', 'asset_type' => 'stock',  'quantity' => 3,     'price' => 398.00, 'days_ago' => 36],
            ['symbol' => 'NVDA', 'asset_type' => 'stock',  'quantity' => 10,    'price' => 108.00, 'days_ago' => 28],
            ['symbol' => 'TSLA', 'asset_type' => 'stock',  'quantity' => 3,     'price' => 238.00, 'days_ago' => 21],
            ['symbol' => 'BTC',  'asset_type' => 'crypto', 'quantity' => 0.012, 'price' => 58500,  'days_ago' => 18],
            ['symbol' => 'ETH',  'asset_type' => 'crypto', 'quantity' => 0.25,  'price' => 2850,   'days_ago' => 12],
            ['symbol' => 'SOL',  'asset_type' => 'crypto', 'quantity' => 3,     'price' => 132,    'days_ago' => 6],
        ];

        DB::transaction(function () use ($kenji, $wallet, $positions): void {
            $balance = $wallet->balance;

            foreach ($positions as $positionData) {
                $total = round($positionData['quantity'] * $positionData['price'], 2);
                $balance = round($balance - $total, 2);
                $timestamp = now()->subDays($positionData['days_ago']);

                PaperTrade::create([
                    'user_id' => $kenji->id,
                    'symbol' => $positionData['symbol'],
                    'type' => 'buy',
                    'asset_type' => $positionData['asset_type'],
                    'quantity' => $positionData['quantity'],
                    'price_per_unit' => $positionData['price'],
                    'total_value' => $total,
                    'wallet_balance_after' => $balance,
                    'status' => 'filled',
                    'notes' => 'Demo-aankoop',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

                PaperPosition::create([
                    'user_id' => $kenji->id,
                    'symbol' => $positionData['symbol'],
                    'asset_type' => $positionData['asset_type'],
                    'quantity' => $positionData['quantity'],
                    'avg_buy_price' => $positionData['price'],
                    'total_invested' => $total,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }

            $wallet->update(['balance' => $balance]);
        });
    }
}
