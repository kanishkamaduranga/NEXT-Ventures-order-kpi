<?php

namespace Modules\Orders\Infrastructure\Persistence\Repositories;

use Modules\Orders\Domain\Models\Order;
use Modules\Orders\Domain\Repositories\OrderRepositoryInterface;

class OrderRepository implements OrderRepositoryInterface
{
    public function findById(string $orderId): ?Order
    {
        return Order::find($orderId);
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return Order::where('order_number', $orderNumber)->first();
    }

    public function save(Order $order): Order
    {
        $order->save();
        return $order;
    }
}

