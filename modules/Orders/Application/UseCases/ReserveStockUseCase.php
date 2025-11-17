<?php
namespace Modules\Orders\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\Orders\Domain\Events\StockReserved;
use Modules\Orders\Domain\Events\StockReservationFailed;
use Modules\Orders\Domain\Models\Order;
use Modules\Orders\Domain\Repositories\OrderRepositoryInterface;
use Modules\Orders\Domain\ValueObjects\OrderStatus;
use Modules\Orders\Domain\Services\StockServiceInterface;

class ReserveStockUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private StockServiceInterface $stockService
    ) {}

    public function execute(string $orderId): void
    {
        $order = $this->orderRepository->findById($orderId);

        if (!$order) {
            throw new \Exception("Order not found: {$orderId}");
        }

        DB::transaction(function () use ($order) {
            // Update order status
            $order->update([
                'status' => OrderStatus::RESERVING_STOCK->value
            ]);

            try {
                // Reserve stock for all items
                foreach ($order->items as $item) {
                    $this->stockService->reserveStock(
                        $item['product_id'],
                        $item['quantity'],
                        $order->id
                    );
                }

                // Update order status to stock reserved
                $order->update([
                    'status' => OrderStatus::STOCK_RESERVED->value,
                    'reserved_at' => now()
                ]);

                // Dispatch event
                event(new StockReserved($order));

            } catch (\Exception $e) {
                // Update order status to failed
                $order->update([
                    'status' => OrderStatus::STOCK_RESERVATION_FAILED->value,
                    'failure_reason' => $e->getMessage()
                ]);

                // Dispatch failure event
                event(new StockReservationFailed($order, $e->getMessage()));

                throw $e;
            }
        });
    }
}
