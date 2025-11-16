<?php
namespace Modules\Orders\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\Orders\Domain\Events\OrderFailed;
use Modules\Orders\Domain\Models\Order;
use Modules\Orders\Domain\Repositories\OrderRepositoryInterface;
use Modules\Orders\Domain\Services\StockServiceInterface;
use Modules\Orders\Domain\ValueObjects\OrderStatus;

class RollbackOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private StockServiceInterface $stockService
    ) {}

    public function execute(string $orderId, string $reason): void
    {
        $order = $this->orderRepository->findById($orderId);

        if (!$order) {
            throw new \Exception("Order not found: {$orderId}");
        }

        DB::transaction(function () use ($order, $reason) {
            // Release reserved stock if needed
            if (in_array($order->status, [
                OrderStatus::STOCK_RESERVED->value,
                OrderStatus::PROCESSING_PAYMENT->value,
                OrderStatus::PAYMENT_FAILED->value
            ])) {
                foreach ($order->items as $item) {
                    $this->stockService->releaseStock(
                        $item['product_id'],
                        $item['quantity'],
                        $order->id
                    );
                }
            }

            // Update order status
            $order->update([
                'status' => OrderStatus::CANCELLED->value,
                'failed_at' => now(),
                'failure_reason' => $reason
            ]);

            // Dispatch order failed event
            event(new OrderFailed($order, $reason));
        });
    }
}
