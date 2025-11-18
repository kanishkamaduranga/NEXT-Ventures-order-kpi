<?php

namespace Modules\Refunds\Domain\Events;

use Modules\Refunds\Domain\Models\Refund;
use Modules\Orders\Domain\Models\Order;

class RefundProcessed
{
    public function __construct(
        public Refund $refund,
        public Order $order
    ) {}
}

