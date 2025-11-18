<?php

namespace Tests\Feature\Analytics;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Modules\Analytics\Application\UseCases\UpdateDailyKpisUseCase;
use Modules\Analytics\Application\UseCases\UpdateLeaderboardUseCase;
use Modules\Orders\Domain\Events\OrderCompleted;
use Modules\Orders\Domain\Events\OrderFailed;
use Modules\Orders\Domain\Models\Order;
use Tests\TestCase;

class AnalyticsEventSubscriberTest extends TestCase
{
    use RefreshDatabase;

    protected $analyticsMock;
    protected $leaderboardMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create singleton mocks that will be reused
        $this->analyticsMock = \Mockery::mock(\Modules\Analytics\Domain\Repositories\AnalyticsRepositoryInterface::class);
        $this->leaderboardMock = \Mockery::mock(\Modules\Analytics\Domain\Repositories\LeaderboardRepositoryInterface::class);
        
        $this->app->instance(
            \Modules\Analytics\Domain\Repositories\AnalyticsRepositoryInterface::class,
            $this->analyticsMock
        );
        
        $this->app->instance(
            \Modules\Analytics\Domain\Repositories\LeaderboardRepositoryInterface::class,
            $this->leaderboardMock
        );
    }

    public function test_it_updates_kpis_when_order_completed()
    {
        $order = Order::create([
            'customer_id' => 1001,
            'order_number' => 'ORD-TEST-001',
            'status' => 'completed',
            'total_amount' => 99.99,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => ['name' => 'Test', 'email' => 'test@example.com'],
            'created_at' => now(),
        ]);

        // Set expectations on the mocks (both are called for OrderCompleted)
        $this->analyticsMock->shouldReceive('incrementOrderMetrics')
            ->once()
            ->with(\Mockery::type(\Modules\Analytics\Domain\ValueObjects\KpiDate::class), 99.99, true, '1001');
        
        $this->leaderboardMock->shouldReceive('updateCustomerSpending')
            ->once()
            ->with('1001', now()->format('Y-m-d'), 99.99);

        $event = new OrderCompleted($order);
        event($event);

        // Verify the repository method was called
        $this->assertTrue(true);
    }

    public function test_it_updates_leaderboard_when_order_completed()
    {
        $order = Order::create([
            'customer_id' => 1001,
            'order_number' => 'ORD-TEST-001',
            'status' => 'completed',
            'total_amount' => 150.00,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => ['name' => 'Test', 'email' => 'test@example.com'],
            'created_at' => now(),
        ]);

        // Set expectations on the mocks (both are called for OrderCompleted)
        $this->analyticsMock->shouldReceive('incrementOrderMetrics')
            ->once()
            ->with(\Mockery::type(\Modules\Analytics\Domain\ValueObjects\KpiDate::class), 150.00, true, '1001');
        
        $this->leaderboardMock->shouldReceive('updateCustomerSpending')
            ->once()
            ->with('1001', now()->format('Y-m-d'), 150.00);

        $event = new OrderCompleted($order);
        event($event);

        // Verify the repository method was called
        $this->assertTrue(true);
    }

    public function test_it_updates_failed_orders_count()
    {
        $order = Order::create([
            'customer_id' => 1001,
            'order_number' => 'ORD-TEST-001',
            'status' => 'cancelled',
            'total_amount' => 99.99,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => ['name' => 'Test', 'email' => 'test@example.com'],
            'created_at' => now(),
        ]);

        // Set expectations on the mock
        $this->analyticsMock->shouldReceive('incrementOrderMetrics')
            ->once()
            ->with(\Mockery::type(\Modules\Analytics\Domain\ValueObjects\KpiDate::class), 0, false, null);

        $event = new OrderFailed($order, 'Test failure reason');
        event($event);

        // Verify the repository method was called
        $this->assertTrue(true);
    }

    public function test_it_tracks_unique_customers()
    {
        // Set expectations on the mocks (both are called for OrderCompleted)
        $this->analyticsMock->shouldReceive('incrementOrderMetrics')
            ->times(3)
            ->with(\Mockery::type(\Modules\Analytics\Domain\ValueObjects\KpiDate::class), \Mockery::any(), true, '1001');
        
        $this->leaderboardMock->shouldReceive('updateCustomerSpending')
            ->times(3)
            ->with('1001', now()->format('Y-m-d'), \Mockery::any());

        // Create multiple orders from same customer
        for ($i = 1; $i <= 3; $i++) {
            $order = Order::create([
                'customer_id' => 1001,
                'order_number' => "ORD-TEST-00{$i}",
                'status' => 'completed',
                'total_amount' => 50.00 * $i,
                'currency' => 'USD',
                'items' => [],
                'customer_details' => ['name' => 'Test', 'email' => 'test@example.com'],
                'created_at' => now(),
            ]);

            event(new OrderCompleted($order));
        }

        // Verify the repository method was called 3 times with same customer ID
        $this->assertTrue(true);
    }

    public function test_it_handles_multiple_customers()
    {
        // Set expectations on the mocks (both are called for OrderCompleted)
        $this->analyticsMock->shouldReceive('incrementOrderMetrics')
            ->once()
            ->with(\Mockery::type(\Modules\Analytics\Domain\ValueObjects\KpiDate::class), 100.00, true, '1001');
        $this->analyticsMock->shouldReceive('incrementOrderMetrics')
            ->once()
            ->with(\Mockery::type(\Modules\Analytics\Domain\ValueObjects\KpiDate::class), 100.00, true, '1002');
        $this->analyticsMock->shouldReceive('incrementOrderMetrics')
            ->once()
            ->with(\Mockery::type(\Modules\Analytics\Domain\ValueObjects\KpiDate::class), 100.00, true, '1003');
        
        $this->leaderboardMock->shouldReceive('updateCustomerSpending')
            ->times(3)
            ->with(\Mockery::any(), now()->format('Y-m-d'), 100.00);

        // Create orders from different customers
        for ($i = 1; $i <= 3; $i++) {
            $order = Order::create([
                'customer_id' => 1000 + $i,
                'order_number' => "ORD-TEST-00{$i}",
                'status' => 'completed',
                'total_amount' => 100.00,
                'currency' => 'USD',
                'items' => [],
                'customer_details' => ['name' => "Customer {$i}", 'email' => "customer{$i}@example.com"],
                'created_at' => now(),
            ]);

            event(new OrderCompleted($order));
        }

        // Verify the repository method was called for each customer
        $this->assertTrue(true);
    }
}

