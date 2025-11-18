<?php

namespace Modules\Notifications\Application\DTOs;

class NotificationData
{
    public function __construct(
        public int $orderId,
        public int $customerId,
        public string $status,
        public float $totalAmount,
        public string $type, // order_completed, order_failed
        public string $channel, // email, log
        public ?string $failureReason = null
    ) {}
}

