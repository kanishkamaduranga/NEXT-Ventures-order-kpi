<?php

namespace Tests\Feature\Analytics;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Modules\Analytics\Application\UseCases\UpdateDailyKpisUseCase;
use Modules\Analytics\Application\UseCases\UpdateLeaderboardUseCase;
use Modules\Orders\Domain\Events\OrderCompleted;
use Modules\Orders\Domain\Models\Order;
use Tests\TestCase;

class AnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear Redis before each test if available
        try {
            Redis::flushdb();
        } catch (\Exception $e) {
            // Redis not available in test environment, skip
        }
    }

    public function test_it_returns_daily_report()
    {
        $date = now()->format('Y-m-d');

        // Mock the repositories
        $mockKpi = new \Modules\Analytics\Domain\Entities\DailyKpi(
            date: $date,
            totalRevenue: 300.00,
            orderCount: 2,
            averageOrderValue: 150.00,
            successfulOrders: 2,
            failedOrders: 0,
            refundAmount: 0.00,
            uniqueCustomers: 2
        );

        $mockAnalyticsRepo = \Mockery::mock(\Modules\Analytics\Domain\Repositories\AnalyticsRepositoryInterface::class);
        $mockAnalyticsRepo->shouldReceive('getDailyKpi')
            ->once()
            ->andReturn($mockKpi);

        $mockLeaderboardRepo = \Mockery::mock(\Modules\Analytics\Domain\Repositories\LeaderboardRepositoryInterface::class);
        $mockLeaderboardRepo->shouldReceive('getTopCustomers')
            ->once()
            ->andReturn(['1001' => 100.00, '1002' => 200.00]);

        $this->app->instance(\Modules\Analytics\Domain\Repositories\AnalyticsRepositoryInterface::class, $mockAnalyticsRepo);
        $this->app->instance(\Modules\Analytics\Domain\Repositories\LeaderboardRepositoryInterface::class, $mockLeaderboardRepo);

        $response = $this->getJson("/api/v1/analytics/daily/{$date}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'date',
                    'kpis' => [
                        'total_revenue',
                        'order_count',
                        'successful_orders',
                        'failed_orders',
                        'average_order_value',
                        'conversion_rate',
                        'unique_customers',
                        'refund_amount',
                    ],
                    'leaderboard',
                ],
            ]);
    }

    public function test_it_returns_leaderboard()
    {
        $date = now()->format('Y-m-d');

        // Mock the leaderboard repository
        $mockLeaderboardRepo = \Mockery::mock(\Modules\Analytics\Domain\Repositories\LeaderboardRepositoryInterface::class);
        $mockLeaderboardRepo->shouldReceive('getTopCustomers')
            ->once()
            ->with($date, 10)
            ->andReturn([
                '1001' => 300.00,
                '1002' => 200.00,
                '1003' => 100.00,
            ]);

        $this->app->instance(\Modules\Analytics\Domain\Repositories\LeaderboardRepositoryInterface::class, $mockLeaderboardRepo);

        $response = $this->getJson("/api/v1/analytics/leaderboard/{$date}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'date',
                    'leaderboard',
                ],
            ]);

        $leaderboard = $response->json('data.leaderboard');
        $this->assertNotEmpty($leaderboard);
        $this->assertCount(3, $leaderboard);
    }

    public function test_it_returns_kpis_for_date_range()
    {
        $yesterday = now()->subDay();
        $today = now();
        $from = $yesterday->format('Y-m-d');
        $to = $today->format('Y-m-d');

        // Mock the analytics repository
        $mockKpi1 = new \Modules\Analytics\Domain\Entities\DailyKpi(
            date: $from,
            totalRevenue: 100.00,
            orderCount: 1,
            averageOrderValue: 100.00,
            successfulOrders: 1,
            failedOrders: 0,
            refundAmount: 0.00,
            uniqueCustomers: 1
        );

        $mockKpi2 = new \Modules\Analytics\Domain\Entities\DailyKpi(
            date: $to,
            totalRevenue: 200.00,
            orderCount: 1,
            averageOrderValue: 200.00,
            successfulOrders: 1,
            failedOrders: 0,
            refundAmount: 0.00,
            uniqueCustomers: 1
        );

        $mockAnalyticsRepo = \Mockery::mock(\Modules\Analytics\Domain\Repositories\AnalyticsRepositoryInterface::class);
        $mockAnalyticsRepo->shouldReceive('getKpiDateRange')
            ->once()
            ->andReturn([$mockKpi1, $mockKpi2]);

        $this->app->instance(\Modules\Analytics\Domain\Repositories\AnalyticsRepositoryInterface::class, $mockAnalyticsRepo);

        $response = $this->getJson("/api/v1/analytics/kpis?start_date={$from}&end_date={$to}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'period',
                    'kpis',
                ],
            ]);
    }

    public function test_it_handles_invalid_date_format()
    {
        $response = $this->getJson('/api/v1/analytics/daily/invalid-date');

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Invalid date format. Expected YYYY-MM-DD'
            ]);
    }

    public function test_it_returns_empty_leaderboard_for_date_with_no_data()
    {
        $date = now()->addDay()->format('Y-m-d');

        // Mock the leaderboard repository to return empty array
        $mockLeaderboardRepo = \Mockery::mock(\Modules\Analytics\Domain\Repositories\LeaderboardRepositoryInterface::class);
        $mockLeaderboardRepo->shouldReceive('getTopCustomers')
            ->once()
            ->with($date, 10)
            ->andReturn([]);

        $this->app->instance(\Modules\Analytics\Domain\Repositories\LeaderboardRepositoryInterface::class, $mockLeaderboardRepo);

        $response = $this->getJson("/api/v1/analytics/leaderboard/{$date}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'date' => $date,
                    'leaderboard' => [],
                ],
            ]);
    }
}

