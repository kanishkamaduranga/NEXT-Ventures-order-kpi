<?php

namespace Modules\Orders\Domain\Events;

use Modules\Orders\Domain\Models\Order;

class StockReservationFailed
{
    public function __construct(
        public Order $order,
        public string $reason
    ) {}
}
