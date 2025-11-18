<?php

namespace Modules\Refunds\Application\DTOs;

class RefundRequest
{
    public function __construct(
        public int $orderId,
        public float $amount,
        public string $type, // 'full' or 'partial'
        public ?string $reason = null,
        public ?string $refundId = null // For idempotency - if provided, will check if refund already exists
    ) {}
}

