<?php

namespace Tests\Unit\Analytics;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Analytics\Application\UseCases\UpdateDailyKpisUseCase;
use Modules\Analytics\Domain\Entities\DailyKpi;
use Modules\Analytics\Domain\Repositories\AnalyticsRepositoryInterface;
use Modules\Analytics\Domain\ValueObjects\KpiDate;
use Tests\TestCase;

class UpdateDailyKpisUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the analytics repository
        $mockRepository = \Mockery::mock(AnalyticsRepositoryInterface::class);
        $this->app->instance(AnalyticsRepositoryInterface::class, $mockRepository);
    }

    public function test_it_increments_order_metrics_for_successful_order()
    {
        $date = now()->format('Y-m-d');
        $kpiDate = new KpiDate($date);

        $mockRepository = \Mockery::mock(AnalyticsRepositoryInterface::class);
        $mockRepository->shouldReceive('incrementOrderMetrics')
            ->once()
            ->with(\Mockery::type(KpiDate::class), 100.00, true, '1001');

        $mockRepository->shouldReceive('getDailyKpi')
            ->once()
            ->with(\Mockery::type(KpiDate::class))
            ->andReturn(new DailyKpi(
                date: $date,
                totalRevenue: 100.00,
                orderCount: 1,
                averageOrderValue: 100.00,
                successfulOrders: 1,
                failedOrders: 0,
                refundAmount: 0.00,
                uniqueCustomers: 1
            ));

        $this->app->instance(AnalyticsRepositoryInterface::class, $mockRepository);

        $useCase = app(UpdateDailyKpisUseCase::class);
        $useCase->execute($date, 100.00, true, '1001');

        $kpi = $useCase->recalculateDailyKpi($date);

        $this->assertNotNull($kpi);
        $this->assertEquals(100.00, $kpi->totalRevenue);
        $this->assertEquals(1, $kpi->orderCount);
        $this->assertEquals(1, $kpi->successfulOrders);
        $this->assertEquals(0, $kpi->failedOrders);
        $this->assertEquals(1, $kpi->uniqueCustomers);
    }

    public function test_it_increments_order_metrics_for_failed_order()
    {
        $date = now()->format('Y-m-d');
        $kpiDate = new KpiDate($date);

        $mockRepository = \Mockery::mock(AnalyticsRepositoryInterface::class);
        $mockRepository->shouldReceive('incrementOrderMetrics')
            ->once()
            ->with(\Mockery::type(KpiDate::class), 0, false, null);

        $mockRepository->shouldReceive('getDailyKpi')
            ->once()
            ->with(\Mockery::type(KpiDate::class))
            ->andReturn(new DailyKpi(
                date: $date,
                totalRevenue: 0.00,
                orderCount: 1,
                averageOrderValue: 0.00,
                successfulOrders: 0,
                failedOrders: 1,
                refundAmount: 0.00,
                uniqueCustomers: 0
            ));

        $this->app->instance(AnalyticsRepositoryInterface::class, $mockRepository);

        $useCase = app(UpdateDailyKpisUseCase::class);
        $useCase->execute($date, 0, false);

        $kpi = $useCase->recalculateDailyKpi($date);

        $this->assertNotNull($kpi);
        $this->assertEquals(0, $kpi->totalRevenue);
        $this->assertEquals(1, $kpi->orderCount);
        $this->assertEquals(0, $kpi->successfulOrders);
        $this->assertEquals(1, $kpi->failedOrders);
    }

    public function test_it_handles_refund()
    {
        $date = now()->format('Y-m-d');
        $kpiDate = new KpiDate($date);

        $mockRepository = \Mockery::mock(AnalyticsRepositoryInterface::class);
        $mockRepository->shouldReceive('incrementOrderMetrics')
            ->once()
            ->with(\Mockery::type(KpiDate::class), 100.00, true, '1001');

        $mockRepository->shouldReceive('decrementOrderMetrics')
            ->once()
            ->with(\Mockery::type(KpiDate::class), 50.00, true);

        $mockRepository->shouldReceive('getDailyKpi')
            ->once()
            ->with(\Mockery::type(KpiDate::class))
            ->andReturn(new DailyKpi(
                date: $date,
                totalRevenue: 50.00,
                orderCount: 1,
                averageOrderValue: 50.00,
                successfulOrders: 1,
                failedOrders: 0,
                refundAmount: 50.00,
                uniqueCustomers: 1
            ));

        $this->app->instance(AnalyticsRepositoryInterface::class, $mockRepository);

        $useCase = app(UpdateDailyKpisUseCase::class);
        $useCase->execute($date, 100.00, true, '1001');
        $useCase->handleRefund($date, 50.00, true);

        $kpi = $useCase->recalculateDailyKpi($date);

        $this->assertNotNull($kpi);
        $this->assertEquals(50.00, $kpi->totalRevenue);
        $this->assertEquals(50.00, $kpi->refundAmount);
    }

    public function test_it_tracks_multiple_orders()
    {
        $date = now()->format('Y-m-d');
        $kpiDate = new KpiDate($date);

        $mockRepository = \Mockery::mock(AnalyticsRepositoryInterface::class);
        $mockRepository->shouldReceive('incrementOrderMetrics')
            ->times(3)
            ->with(\Mockery::type(KpiDate::class), \Mockery::any(), \Mockery::any(), \Mockery::any());

        $mockRepository->shouldReceive('getDailyKpi')
            ->once()
            ->with(\Mockery::type(KpiDate::class))
            ->andReturn(new DailyKpi(
                date: $date,
                totalRevenue: 300.00,
                orderCount: 3,
                averageOrderValue: 150.00,
                successfulOrders: 2,
                failedOrders: 1,
                refundAmount: 0.00,
                uniqueCustomers: 2
            ));

        $this->app->instance(AnalyticsRepositoryInterface::class, $mockRepository);

        $useCase = app(UpdateDailyKpisUseCase::class);
        $useCase->execute($date, 100.00, true, '1001');
        $useCase->execute($date, 200.00, true, '1002');
        $useCase->execute($date, 50.00, false);

        $kpi = $useCase->recalculateDailyKpi($date);

        $this->assertNotNull($kpi);
        $this->assertEquals(300.00, $kpi->totalRevenue);
        $this->assertEquals(3, $kpi->orderCount);
        $this->assertEquals(2, $kpi->successfulOrders);
        $this->assertEquals(1, $kpi->failedOrders);
        $this->assertEquals(2, $kpi->uniqueCustomers);
    }

    public function test_it_calculates_average_order_value()
    {
        $date = now()->format('Y-m-d');
        $kpiDate = new KpiDate($date);

        $mockRepository = \Mockery::mock(AnalyticsRepositoryInterface::class);
        $mockRepository->shouldReceive('incrementOrderMetrics')
            ->times(3)
            ->with(\Mockery::type(KpiDate::class), \Mockery::any(), true, \Mockery::any());

        $mockRepository->shouldReceive('getDailyKpi')
            ->once()
            ->with(\Mockery::type(KpiDate::class))
            ->andReturn(new DailyKpi(
                date: $date,
                totalRevenue: 600.00,
                orderCount: 3,
                averageOrderValue: 200.00,
                successfulOrders: 3,
                failedOrders: 0,
                refundAmount: 0.00,
                uniqueCustomers: 3
            ));

        $this->app->instance(AnalyticsRepositoryInterface::class, $mockRepository);

        $useCase = app(UpdateDailyKpisUseCase::class);
        $useCase->execute($date, 100.00, true, '1001');
        $useCase->execute($date, 200.00, true, '1002');
        $useCase->execute($date, 300.00, true, '1003');

        $kpi = $useCase->recalculateDailyKpi($date);

        $this->assertNotNull($kpi);
        $this->assertEquals(200.00, $kpi->averageOrderValue);
    }
}

