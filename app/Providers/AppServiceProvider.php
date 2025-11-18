<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Automatically create wallet when user is created
        User::created(function (User $user) {
            if (!$user->wallet) {
                Wallet::create([
                    'user_id' => $user->id,
                    'balance' => 10000.00,
                    'currency' => 'EUR',
                ]);
            }
        });
    }
}
