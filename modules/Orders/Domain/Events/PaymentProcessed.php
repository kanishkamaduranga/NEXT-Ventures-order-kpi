<?php
namespace Modules\Orders\Domain\Events;

use Modules\Orders\Domain\Models\Order;

class PaymentProcessed
{
    public function __construct(
        public Order $order,
        public bool $success,
        public string $paymentReference,
        public ?string $failureReason = null
    ) {}
}
