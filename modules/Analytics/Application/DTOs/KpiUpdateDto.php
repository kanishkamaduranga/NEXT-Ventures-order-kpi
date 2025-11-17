<?php
namespace Modules\Analytics\Application\DTOs;

class KpiUpdateDto
{
    public function __construct(
        public string $date,
        public float $amount,
        public string $customerId,
        public bool $successful = true,
        public ?string $orderId = null
    ) {}
}
