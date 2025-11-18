<?php

namespace Tests\Unit\Notifications;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Notifications\Domain\Models\Notification;
use Modules\Notifications\Domain\Repositories\NotificationRepositoryInterface;
use Tests\TestCase;

class NotificationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(NotificationRepositoryInterface::class);
    }

    public function test_it_can_create_notification()
    {
        $data = [
            'order_id' => 123,
            'customer_id' => 456,
            'status' => 'completed',
            'total_amount' => 99.99,
            'type' => 'order_completed',
            'channel' => 'email',
            'status_sent' => 'pending',
        ];

        $notification = $this->repository->create($data);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertNotNull($notification->id);
        $this->assertEquals(123, $notification->order_id);
        $this->assertEquals(456, $notification->customer_id);
    }

    public function test_it_can_find_notification_by_id()
    {
        $notification = Notification::create([
            'order_id' => 123,
            'customer_id' => 456,
            'status' => 'completed',
            'total_amount' => 99.99,
            'type' => 'order_completed',
            'channel' => 'email',
            'status_sent' => 'pending',
        ]);

        $found = $this->repository->findById($notification->id);

        $this->assertInstanceOf(Notification::class, $found);
        $this->assertEquals($notification->id, $found->id);
        $this->assertEquals(123, $found->order_id);
    }

    public function test_it_returns_null_when_notification_not_found()
    {
        $found = $this->repository->findById(99999);

        $this->assertNull($found);
    }

    public function test_it_can_find_notifications_by_order_id()
    {
        // Create multiple notifications for the same order
        Notification::create([
            'order_id' => 123,
            'customer_id' => 456,
            'status' => 'completed',
            'total_amount' => 99.99,
            'type' => 'order_completed',
            'channel' => 'email',
            'status_sent' => 'sent',
        ]);

        Notification::create([
            'order_id' => 123,
            'customer_id' => 456,
            'status' => 'completed',
            'total_amount' => 99.99,
            'type' => 'order_completed',
            'channel' => 'log',
            'status_sent' => 'sent',
        ]);

        // Create notification for different order
        Notification::create([
            'order_id' => 456,
            'customer_id' => 789,
            'status' => 'completed',
            'total_amount' => 50.00,
            'type' => 'order_completed',
            'channel' => 'email',
            'status_sent' => 'sent',
        ]);

        $notifications = $this->repository->findByOrderId(123);

        $this->assertIsArray($notifications);
        $this->assertCount(2, $notifications);
        $this->assertEquals(123, $notifications[0]['order_id']);
        $this->assertEquals(123, $notifications[1]['order_id']);
    }

    public function test_it_returns_empty_array_when_no_notifications_for_order()
    {
        $notifications = $this->repository->findByOrderId(99999);

        $this->assertIsArray($notifications);
        $this->assertEmpty($notifications);
    }

    public function test_it_can_find_notifications_by_customer_id()
    {
        // Create multiple notifications for the same customer
        Notification::create([
            'order_id' => 123,
            'customer_id' => 456,
            'status' => 'completed',
            'total_amount' => 99.99,
            'type' => 'order_completed',
            'channel' => 'email',
            'status_sent' => 'sent',
        ]);

        Notification::create([
            'order_id' => 124,
            'customer_id' => 456,
            'status' => 'completed',
            'total_amount' => 150.00,
            'type' => 'order_completed',
            'channel' => 'email',
            'status_sent' => 'sent',
        ]);

        // Create notification for different customer
        Notification::create([
            'order_id' => 125,
            'customer_id' => 789,
            'status' => 'completed',
            'total_amount' => 50.00,
            'type' => 'order_completed',
            'channel' => 'email',
            'status_sent' => 'sent',
        ]);

        $notifications = $this->repository->findByCustomerId(456);

        $this->assertIsArray($notifications);
        $this->assertCount(2, $notifications);
        $this->assertEquals(456, $notifications[0]['customer_id']);
        $this->assertEquals(456, $notifications[1]['customer_id']);
    }

    public function test_it_returns_empty_array_when_no_notifications_for_customer()
    {
        $notifications = $this->repository->findByCustomerId(99999);

        $this->assertIsArray($notifications);
        $this->assertEmpty($notifications);
    }

    public function test_it_orders_notifications_by_created_at_desc()
    {
        // Create first notification
        $first = Notification::create([
            'order_id' => 123,
            'customer_id' => 456,
            'status' => 'completed',
            'total_amount' => 99.99,
            'type' => 'order_completed',
            'channel' => 'email',
            'status_sent' => 'sent',
        ]);

        // Wait to ensure different timestamp
        sleep(1);

        // Create second notification (should appear first in desc order)
        $second = Notification::create([
            'order_id' => 123,
            'customer_id' => 456,
            'status' => 'completed',
            'total_amount' => 99.99,
            'type' => 'order_completed',
            'channel' => 'log',
            'status_sent' => 'sent',
        ]);

        $notifications = $this->repository->findByOrderId(123);

        $this->assertCount(2, $notifications);
        
        // Most recent should be first (second notification was created later)
        // Verify that the second notification (created later) is first in the array
        $this->assertEquals($second->id, $notifications[0]['id'], 
            'Most recent notification should be first in desc order');
        $this->assertEquals($first->id, $notifications[1]['id'], 
            'Older notification should be second in desc order');
    }
}

