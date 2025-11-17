<?php

namespace Modules\Orders\Domain\Repositories;

use Modules\Orders\Domain\Models\Order;

interface OrderRepositoryInterface
{
    /**
     * Find order by ID
     */
    public function findById(string $orderId): ?Order;

    /**
     * Find order by order number
     */
    public function findByOrderNumber(string $orderNumber): ?Order;

    /**
     * Save order
     */
    public function save(Order $order): Order;
}

