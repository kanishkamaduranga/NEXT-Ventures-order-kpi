<?php
namespace Modules\Orders\Application\Services;

use Modules\Orders\Application\UseCases\FinalizeOrderUseCase;
use Modules\Orders\Application\UseCases\ReserveStockUseCase;
use Modules\Orders\Application\UseCases\RollbackOrderUseCase;
use Modules\Orders\Application\UseCases\SimulatePaymentUseCase;
use Modules\Orders\Domain\Events\StockReservationFailed;
use Modules\Orders\Domain\Events\PaymentProcessed;
use Modules\Orders\Domain\Events\StockReserved;

class OrderWorkflowCoordinator
{
    public function __construct(
        private ReserveStockUseCase $reserveStockUseCase,
        private SimulatePaymentUseCase $simulatePaymentUseCase,
        private FinalizeOrderUseCase $finalizeOrderUseCase,
        private RollbackOrderUseCase $rollbackOrderUseCase
    ) {}

    public function startOrderProcessing(string $orderId): void
    {
        try {
            // Step 1: Reserve Stock
            $this->reserveStockUseCase->execute($orderId);

        } catch (\Exception $e) {
            // Stock reservation failed, rollback
            $this->rollbackOrderUseCase->execute($orderId, "Stock reservation failed: " . $e->getMessage());
        }
    }

    public function handleStockReserved(StockReserved $event): void
    {
        try {
            // Step 2: Process Payment
            $this->simulatePaymentUseCase->execute($event->order->id);

        } catch (\Exception $e) {
            // Payment processing failed, rollback
            $this->rollbackOrderUseCase->execute($event->order->id, "Payment processing failed: " . $e->getMessage());
        }
    }

    public function handlePaymentProcessed(PaymentProcessed $event): void
    {
        if ($event->success) {
            try {
                // Step 3: Finalize Order
                $this->finalizeOrderUseCase->execute($event->order->id);
            } catch (\Exception $e) {
                // Finalization failed, rollback
                $this->rollbackOrderUseCase->execute($event->order->id, "Order finalization failed: " . $e->getMessage());
            }
        } else {
            // Payment failed, rollback
            $this->rollbackOrderUseCase->execute($event->order->id, "Payment failed: " . $event->failureReason);
        }
    }

    public function handleStockReservationFailed(StockReservationFailed $event): void
    {
        // Already handled in startOrderProcessing, but here for completeness
        $this->rollbackOrderUseCase->execute($event->order->id, $event->reason);
    }
}
