<?php

namespace Tests\Unit\Notifications;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Notifications\Domain\Models\Notification;
use Tests\TestCase;

class NotificationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_notification()
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

        $this->assertNotNull($notification->id);
        $this->assertEquals(123, $notification->order_id);
        $this->assertEquals(456, $notification->customer_id);
        $this->assertEquals('completed', $notification->status);
        $this->assertEquals(99.99, $notification->total_amount);
        $this->assertEquals('order_completed', $notification->type);
        $this->assertEquals('email', $notification->channel);
        $this->assertEquals('pending', $notification->status_sent);
    }

    public function test_it_can_mark_as_sent()
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

        $notification->markAsSent('Email sent successfully');

        $this->assertEquals('sent', $notification->status_sent);
        $this->assertNotNull($notification->sent_at);
        $this->assertEquals('Email sent successfully', $notification->message);
        $this->assertTrue($notification->isSent());
        $this->assertFalse($notification->isPending());
        $this->assertFalse($notification->isFailed());
    }

    public function test_it_can_mark_as_failed()
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

        $notification->markAsFailed('SMTP connection failed');

        $this->assertEquals('failed', $notification->status_sent);
        $this->assertEquals('SMTP connection failed', $notification->error_message);
        $this->assertTrue($notification->isFailed());
        $this->assertFalse($notification->isSent());
        $this->assertFalse($notification->isPending());
    }

    public function test_it_checks_pending_status()
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

        $this->assertTrue($notification->isPending());
        $this->assertFalse($notification->isSent());
        $this->assertFalse($notification->isFailed());
    }

    public function test_it_casts_total_amount_to_decimal()
    {
        $notification = Notification::create([
            'order_id' => 123,
            'customer_id' => 456,
            'status' => 'completed',
            'total_amount' => '99.99',
            'type' => 'order_completed',
            'channel' => 'email',
            'status_sent' => 'pending',
        ]);

        // Decimal cast returns a string representation
        $this->assertIsString($notification->total_amount);
        $this->assertEquals('99.99', $notification->total_amount);
    }

    public function test_it_casts_sent_at_to_datetime()
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

        $notification->markAsSent();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $notification->sent_at);
    }
}

