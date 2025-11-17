<?php
namespace Modules\Analytics\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Analytics\Domain\Repositories\AnalyticsRepositoryInterface;
use Modules\Analytics\Domain\Repositories\LeaderboardRepositoryInterface;
use Modules\Analytics\Infrastructure\Repositories\RedisAnalyticsRepository;
use Modules\Analytics\Infrastructure\Repositories\RedisLeaderboardRepository;

class AnalyticsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom([
            __DIR__ . '/../Infrastructure/Persistence/Migrations'
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Analytics\Interfaces\Console\Commands\GenerateDailyReportCommand::class,
                \Modules\Analytics\Interfaces\Console\Commands\BackfillKpisCommand::class,
            ]);
        }

        // Register event subscribers
        Event::subscribe(\Modules\Analytics\Infrastructure\Listeners\AnalyticsEventSubscriber::class);
    }

    public function register(): void
    {
        $this->app->bind(AnalyticsRepositoryInterface::class, RedisAnalyticsRepository::class);
        $this->app->bind(LeaderboardRepositoryInterface::class, RedisLeaderboardRepository::class);
    }
}
