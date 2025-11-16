<?php
namespace Modules\Orders\Infrastructure\Queue\Listeners;

use Illuminate\Events\Dispatcher;
use Modules\Orders\Application\Services\OrderWorkflowCoordinator;
use Modules\Orders\Domain\Events\StockReserved;
use Modules\Orders\Domain\Events\StockReservationFailed;
use Modules\Orders\Domain\Events\PaymentProcessed;

class OrderEventSubscriber
{
    public function __construct(
        private OrderWorkflowCoordinator $workflowCoordinator
    ) {}

    public function handleStockReserved(StockReserved $event): void
    {
        $this->workflowCoordinator->handleStockReserved($event);
    }

    public function handlePaymentProcessed(PaymentProcessed $event): void
    {
        $this->workflowCoordinator->handlePaymentProcessed($event);
    }

    public function handleStockReservationFailed(StockReservationFailed $event): void
    {
        $this->workflowCoordinator->handleStockReservationFailed($event);
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            StockReserved::class,
            [OrderEventSubscriber::class, 'handleStockReserved']
        );

        $events->listen(
            PaymentProcessed::class,
            [OrderEventSubscriber::class, 'handlePaymentProcessed']
        );

        $events->listen(
            StockReservationFailed::class,
            [OrderEventSubscriber::class, 'handleStockReservationFailed']
        );
    }
}
