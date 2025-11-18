<?php

namespace Modules\Refunds\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Refunds\Domain\Repositories\RefundRepositoryInterface;
use Modules\Refunds\Infrastructure\Persistence\Repositories\RefundRepository;

class RefundsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom([
            __DIR__ . '/../Infrastructure/Persistence/Migrations'
        ]);

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Refunds\Interfaces\Console\Commands\ProcessRefundCommand::class,
                \Modules\Refunds\Interfaces\Console\Commands\ListRefundsCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        // Register repositories
        $this->app->bind(
            RefundRepositoryInterface::class,
            RefundRepository::class
        );
    }
}

