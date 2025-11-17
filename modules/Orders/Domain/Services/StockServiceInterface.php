<?php

namespace Modules\Orders\Domain\Services;

interface StockServiceInterface
{
    /**
     * Reserve stock for an order
     */
    public function reserveStock(string $productId, int $quantity, string $orderId): void;

    /**
     * Release reserved stock
     */
    public function releaseStock(string $productId, int $quantity, string $orderId): void;
}

