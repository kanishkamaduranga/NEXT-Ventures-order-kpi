<?php

namespace Modules\Notifications\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Notifications\Domain\Repositories\NotificationRepositoryInterface;
use Modules\Notifications\Infrastructure\Listeners\NotificationEventSubscriber;
use Modules\Notifications\Infrastructure\Persistence\Repositories\NotificationRepository;

class NotificationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom([
            __DIR__ . '/../Infrastructure/Persistence/Migrations'
        ]);

        // Register event subscriber
        \Illuminate\Support\Facades\Event::subscribe(NotificationEventSubscriber::class);

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Notifications\Interfaces\Console\Commands\ListNotificationsCommand::class,
                \Modules\Notifications\Interfaces\Console\Commands\SeedNotificationsCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        // Register repositories
        $this->app->bind(
            NotificationRepositoryInterface::class,
            NotificationRepository::class
        );
    }
}

