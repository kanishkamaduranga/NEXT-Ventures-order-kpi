<?php

namespace Tests\Unit\Analytics;

use Modules\Analytics\Domain\Entities\DailyKpi;
use Tests\TestCase;

class DailyKpiEntityTest extends TestCase
{
    public function test_it_can_create_daily_kpi()
    {
        $kpi = new DailyKpi(
            date: '2025-11-18',
            totalRevenue: 1000.00,
            orderCount: 10,
            averageOrderValue: 100.00,
            successfulOrders: 8,
            failedOrders: 2,
            refundAmount: 50.00,
            uniqueCustomers: 5
        );

        $this->assertEquals('2025-11-18', $kpi->date);
        $this->assertEquals(1000.00, $kpi->totalRevenue);
        $this->assertEquals(10, $kpi->orderCount);
        $this->assertEquals(100.00, $kpi->averageOrderValue);
        $this->assertEquals(8, $kpi->successfulOrders);
        $this->assertEquals(2, $kpi->failedOrders);
        $this->assertEquals(50.00, $kpi->refundAmount);
        $this->assertEquals(5, $kpi->uniqueCustomers);
    }

    public function test_it_calculates_conversion_rate()
    {
        $kpi = new DailyKpi(
            date: '2025-11-18',
            totalRevenue: 1000.00,
            orderCount: 10,
            averageOrderValue: 100.00,
            successfulOrders: 8,
            failedOrders: 2,
            refundAmount: 0.00,
            uniqueCustomers: 5
        );

        $this->assertEquals(80.0, $kpi->getConversionRate()); // 8/10 * 100
    }

    public function test_it_handles_zero_orders()
    {
        $kpi = new DailyKpi(
            date: '2025-11-18',
            totalRevenue: 0.00,
            orderCount: 0,
            averageOrderValue: 0.00,
            successfulOrders: 0,
            failedOrders: 0,
            refundAmount: 0.00,
            uniqueCustomers: 0
        );

        $this->assertEquals(0.0, $kpi->getConversionRate());
    }

    public function test_it_handles_all_failed_orders()
    {
        $kpi = new DailyKpi(
            date: '2025-11-18',
            totalRevenue: 0.00,
            orderCount: 10,
            averageOrderValue: 0.00,
            successfulOrders: 0,
            failedOrders: 10,
            refundAmount: 0.00,
            uniqueCustomers: 0
        );

        $this->assertEquals(0.0, $kpi->getConversionRate());
    }
}

