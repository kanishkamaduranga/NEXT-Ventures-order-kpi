<?php

namespace Tests\Feature\Notifications;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Notifications\Application\Jobs\SendOrderNotificationJob;
use Modules\Orders\Domain\Events\OrderCompleted;
use Modules\Orders\Domain\Events\OrderFailed;
use Modules\Orders\Domain\Models\Order;
use Tests\TestCase;

class NotificationEventSubscriberTest extends TestCase
{
    use RefreshDatabase;

    protected $analyticsMock;
    protected $leaderboardMock;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        
        // Mock analytics repositories to prevent Redis calls
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

    public function test_it_dispatches_notifications_when_order_completed()
    {
        // Set expectations for analytics (called by AnalyticsEventSubscriber)
        $this->analyticsMock->shouldReceive('incrementOrderMetrics')
            ->once()
            ->with(\Mockery::any(), \Mockery::any(), true, \Mockery::any());
        
        $this->leaderboardMock->shouldReceive('updateCustomerSpending')
            ->once()
            ->with(\Mockery::any(), \Mockery::any(), \Mockery::any());

        $order = Order::create([
            'customer_id' => 456,
            'order_number' => 'ORD-001',
            'status' => 'completed',
            'total_amount' => 99.99,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => ['name' => 'Test', 'email' => 'test@example.com'],
            'created_at' => now(),
        ]);

        event(new OrderCompleted($order));

        // Should dispatch 2 jobs: one for email, one for log
        Queue::assertPushed(SendOrderNotificationJob::class, 2);
        
        Queue::assertPushed(SendOrderNotificationJob::class, function ($job) use ($order) {
            return $job->notificationData->orderId === $order->id &&
                   $job->notificationData->customerId === 456 &&
                   $job->notificationData->type === 'order_completed' &&
                   $job->notificationData->channel === 'email';
        });

        Queue::assertPushed(SendOrderNotificationJob::class, function ($job) use ($order) {
            return $job->notificationData->orderId === $order->id &&
                   $job->notificationData->channel === 'log';
        });
    }

    public function test_it_dispatches_notifications_when_order_failed()
    {
        // Set expectations for analytics (called by AnalyticsEventSubscriber)
        $this->analyticsMock->shouldReceive('incrementOrderMetrics')
            ->once()
            ->with(\Mockery::any(), 0, false, \Mockery::any());

        $order = Order::create([
            'customer_id' => 456,
            'order_number' => 'ORD-002',
            'status' => 'cancelled',
            'total_amount' => 50.00,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => ['name' => 'Test', 'email' => 'test@example.com'],
            'created_at' => now(),
        ]);

        event(new OrderFailed($order, 'Payment declined'));

        // Should dispatch 2 jobs: one for email, one for log
        Queue::assertPushed(SendOrderNotificationJob::class, 2);
        
        Queue::assertPushed(SendOrderNotificationJob::class, function ($job) use ($order) {
            return $job->notificationData->orderId === $order->id &&
                   $job->notificationData->type === 'order_failed' &&
                   $job->notificationData->channel === 'email' &&
                   $job->notificationData->failureReason === 'Payment declined';
        });

        Queue::assertPushed(SendOrderNotificationJob::class, function ($job) use ($order) {
            return $job->notificationData->orderId === $order->id &&
                   $job->notificationData->channel === 'log' &&
                   $job->notificationData->failureReason === 'Payment declined';
        });
    }

    public function test_it_includes_correct_order_data_in_notification()
    {
        // Set expectations for analytics
        $this->analyticsMock->shouldReceive('incrementOrderMetrics')
            ->once()
            ->with(\Mockery::any(), \Mockery::any(), true, \Mockery::any());
        
        $this->leaderboardMock->shouldReceive('updateCustomerSpending')
            ->once()
            ->with(\Mockery::any(), \Mockery::any(), \Mockery::any());

        $order = Order::create([
            'customer_id' => 789,
            'order_number' => 'ORD-003',
            'status' => 'completed',
            'total_amount' => 152.50,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => ['name' => 'Test', 'email' => 'test@example.com'],
            'created_at' => now(),
        ]);

        event(new OrderCompleted($order));

        Queue::assertPushed(SendOrderNotificationJob::class, function ($job) use ($order) {
            return $job->notificationData->orderId === $order->id &&
                   $job->notificationData->customerId === 789 &&
                   $job->notificationData->status === 'completed' &&
                   $job->notificationData->totalAmount === 152.50;
        });
    }

    public function test_it_handles_multiple_orders()
    {
        // Set expectations for analytics (called twice)
        $this->analyticsMock->shouldReceive('incrementOrderMetrics')
            ->times(2)
            ->with(\Mockery::any(), \Mockery::any(), true, \Mockery::any());
        
        $this->leaderboardMock->shouldReceive('updateCustomerSpending')
            ->times(2)
            ->with(\Mockery::any(), \Mockery::any(), \Mockery::any());

        $order1 = Order::create([
            'customer_id' => 100,
            'order_number' => 'ORD-001',
            'status' => 'completed',
            'total_amount' => 100.00,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => ['name' => 'Test', 'email' => 'test@example.com'],
            'created_at' => now(),
        ]);

        $order2 = Order::create([
            'customer_id' => 200,
            'order_number' => 'ORD-002',
            'status' => 'completed',
            'total_amount' => 200.00,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => ['name' => 'Test', 'email' => 'test@example.com'],
            'created_at' => now(),
        ]);

        event(new OrderCompleted($order1));
        event(new OrderCompleted($order2));

        // Should dispatch 4 jobs total (2 per order)
        Queue::assertPushed(SendOrderNotificationJob::class, 4);
    }
}

