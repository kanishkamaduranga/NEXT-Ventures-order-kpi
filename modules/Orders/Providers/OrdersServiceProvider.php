<?php
namespace Modules\Orders\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Orders\Application\Commands\ImportOrdersCommand;

class OrdersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom([
            __DIR__ . '/../Infrastructure/Persistence/Migrations'
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportOrdersCommand::class,
            ]);
        }
    }

    public function register(): void
    {

    }
}
