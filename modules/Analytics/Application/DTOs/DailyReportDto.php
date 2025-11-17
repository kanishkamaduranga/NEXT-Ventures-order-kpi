<?php
namespace Modules\Analytics\Application\DTOs;

class DailyReportDto
{
    public function __construct(
        public string $date,
        public float $totalRevenue,
        public int $orderCount,
        public float $averageOrderValue,
        public array $topCustomers,
        public float $conversionRate
    ) {}
}
