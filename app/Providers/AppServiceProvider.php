<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

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
        // Configure Horizon authorization
        // In production, you should implement proper authentication
        Horizon::auth(function ($request) {
            // For local development, allow access without authentication
            if (app()->environment('local')) {
                return true;
            }

            // For production, implement your authentication logic here
            // Example: return auth()->check() && auth()->user()->isAdmin();
            return true; // Change this to your production authorization logic
        });
    }
}
