<?php

namespace Modules\Orders\Domain\Events;

use Modules\Orders\Domain\Models\Order;

class OrderCompleted
{
    public function __construct(
        public Order $order
    ) {}
}
