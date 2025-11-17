<?php
namespace Modules\Analytics\Domain\Entities;

class DailyKpi
{
    public function __construct(
        public string $date,
        public float  $totalRevenue,
        public int    $orderCount,
        public float  $averageOrderValue,
        public int    $successfulOrders,
        public int    $failedOrders,
        public float  $refundAmount,
        public int    $uniqueCustomers
    )
    {
    }

    public function getConversionRate(): float
    {
        if ($this->orderCount === 0) {
            return 0.0;
        }

        return ($this->successfulOrders / $this->orderCount) * 100;
    }

    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'total_revenue' => $this->totalRevenue,
            'order_count' => $this->orderCount,
            'average_order_value' => $this->averageOrderValue,
            'successful_orders' => $this->successfulOrders,
            'failed_orders' => $this->failedOrders,
            'refund_amount' => $this->refundAmount,
            'unique_customers' => $this->uniqueCustomers,
            'conversion_rate' => $this->getConversionRate(),
        ];
    }
}
