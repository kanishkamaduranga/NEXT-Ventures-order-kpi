<?php
namespace Modules\Orders\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\Orders\Domain\Events\OrderCompleted;
use Modules\Orders\Domain\Models\Order;
use Modules\Orders\Domain\Repositories\OrderRepositoryInterface;
use Modules\Orders\Domain\ValueObjects\OrderStatus;

class FinalizeOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {}

    public function execute(string $orderId): void
    {
        $order = $this->orderRepository->findById($orderId);

        if (!$order) {
            throw new \Exception("Order not found: {$orderId}");
        }

        DB::transaction(function () use ($order) {
            $order->update([
                'status' => OrderStatus::COMPLETED->value
            ]);

            // Dispatch order completed event
            event(new OrderCompleted($order));
        });
    }
}
