<?php
namespace Modules\Orders\Providers;

use Illuminate\Support\ServiceProvider;

class OrdersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom([
            __DIR__ . '/../Infrastructure/Persistence/Migrations'
        ]);
    }

    public function register(): void
    {

    }
}
