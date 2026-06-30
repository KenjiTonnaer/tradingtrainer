<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Wallet;
use App\Services\PiecesService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PiecesService::class, function ($app) {
            return new PiecesService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Wallet creatie gebeurt nu expliciet in de seeders om dubbele inserts te voorkomen.
    }
}
