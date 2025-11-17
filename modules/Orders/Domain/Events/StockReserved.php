<?php
namespace Modules\Orders\Domain\Events;

use Modules\Orders\Domain\Models\Order;

class StockReserved
{
    public function __construct(
        public Order $order
    ) {}
}
