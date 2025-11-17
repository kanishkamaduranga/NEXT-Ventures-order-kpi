<?php
namespace Modules\Orders\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Orders\Application\Commands\ImportOrdersCommand;
use Modules\Orders\Application\Commands\ProcessOrderCommand;
use Modules\Orders\Domain\Repositories\OrderRepositoryInterface;
use Modules\Orders\Domain\Services\PaymentGatewayInterface;
use Modules\Orders\Domain\Services\StockServiceInterface;
use Modules\Orders\Infrastructure\Persistence\Repositories\OrderRepository;
use Modules\Orders\Infrastructure\Queue\Listeners\OrderEventSubscriber;
use Modules\Orders\Infrastructure\Services\SimulatedPaymentGateway;
use Modules\Orders\Infrastructure\Services\StockReservationService;

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
                ProcessOrderCommand::class,
            ]);
        }

        // Register event subscribers
        Event::subscribe(OrderEventSubscriber::class);
    }

    public function register(): void
    {
        // Register repositories
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);

        // Register services
        $this->app->bind(StockServiceInterface::class, StockReservationService::class);
        $this->app->bind(PaymentGatewayInterface::class, SimulatedPaymentGateway::class);
    }
}
