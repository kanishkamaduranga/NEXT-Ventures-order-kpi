<?php

namespace Modules\Orders\Infrastructure\Services;

use Illuminate\Support\Facades\Log;
use Modules\Orders\Domain\Services\StockServiceInterface;

class StockReservationService implements StockServiceInterface
{
    public function reserveStock(string $productId, int $quantity, string $orderId): void
    {
        // Simulate stock reservation logic
        // In real implementation, this would call inventory service
        Log::info("Reserving stock", [
            'product_id' => $productId,
            'quantity' => $quantity,
            'order_id' => $orderId
        ]);

        // Simulate random failures for testing (10% failure rate)
        if (rand(1, 10) === 1) {
            throw new \Exception("Insufficient stock for product: {$productId}");
        }

        // Success - stock reserved
        sleep(1); // Simulate processing time
    }

    public function releaseStock(string $productId, int $quantity, string $orderId): void
    {
        // Release reserved stock
        Log::info("Releasing stock", [
            'product_id' => $productId,
            'quantity' => $quantity,
            'order_id' => $orderId
        ]);

        sleep(0.5); // Simulate processing time
    }
}

