<?php

namespace Tests\Unit\Notifications;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Notifications\Application\DTOs\NotificationData;
use Modules\Notifications\Application\Jobs\SendOrderNotificationJob;
use Modules\Notifications\Domain\Models\Notification;
use Modules\Notifications\Domain\Repositories\NotificationRepositoryInterface;
use Modules\Notifications\Infrastructure\Services\EmailNotificationChannel;
use Modules\Notifications\Infrastructure\Services\LogNotificationChannel;
use Tests\TestCase;

class SendOrderNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_it_creates_notification_record_when_handled()
    {
        $data = new NotificationData(
            orderId: 123,
            customerId: 456,
            status: 'completed',
            totalAmount: 99.99,
            type: 'order_completed',
            channel: 'email'
        );

        $job = new SendOrderNotificationJob($data);
        
        // Mock the channels to return success
        $emailChannel = \Mockery::mock(EmailNotificationChannel::class);
        $emailChannel->shouldReceive('send')
            ->once()
            ->andReturn(true);
        
        $logChannel = \Mockery::mock(LogNotificationChannel::class);
        
        $this->app->instance(EmailNotificationChannel::class, $emailChannel);
        $this->app->instance(LogNotificationChannel::class, $logChannel);

        $job->handle(
            app(NotificationRepositoryInterface::class),
            $emailChannel,
            $logChannel
        );

        $notification = Notification::where('order_id', 123)
            ->where('channel', 'email')
            ->first();

        $this->assertNotNull($notification);
        $this->assertEquals(123, $notification->order_id);
        $this->assertEquals(456, $notification->customer_id);
        $this->assertEquals('order_completed', $notification->type);
        $this->assertEquals('email', $notification->channel);
    }

    public function test_it_marks_notification_as_sent_when_channel_succeeds()
    {
        $data = new NotificationData(
            orderId: 123,
            customerId: 456,
            status: 'completed',
            totalAmount: 99.99,
            type: 'order_completed',
            channel: 'email'
        );

        $job = new SendOrderNotificationJob($data);
        
        $emailChannel = \Mockery::mock(EmailNotificationChannel::class);
        $emailChannel->shouldReceive('send')
            ->once()
            ->andReturn(true);
        
        $logChannel = \Mockery::mock(LogNotificationChannel::class);
        
        $this->app->instance(EmailNotificationChannel::class, $emailChannel);
        $this->app->instance(LogNotificationChannel::class, $logChannel);

        $job->handle(
            app(NotificationRepositoryInterface::class),
            $emailChannel,
            $logChannel
        );

        $notification = Notification::where('order_id', 123)
            ->where('channel', 'email')
            ->first();

        $this->assertEquals('sent', $notification->status_sent);
        $this->assertTrue($notification->isSent());
        $this->assertNotNull($notification->sent_at);
    }

    public function test_it_marks_notification_as_failed_when_channel_returns_false()
    {
        $data = new NotificationData(
            orderId: 123,
            customerId: 456,
            status: 'completed',
            totalAmount: 99.99,
            type: 'order_completed',
            channel: 'email'
        );

        $job = new SendOrderNotificationJob($data);
        
        $emailChannel = \Mockery::mock(EmailNotificationChannel::class);
        $emailChannel->shouldReceive('send')
            ->once()
            ->andReturn(false);
        
        $logChannel = \Mockery::mock(LogNotificationChannel::class);
        
        $this->app->instance(EmailNotificationChannel::class, $emailChannel);
        $this->app->instance(LogNotificationChannel::class, $logChannel);

        $job->handle(
            app(NotificationRepositoryInterface::class),
            $emailChannel,
            $logChannel
        );

        $notification = Notification::where('order_id', 123)
            ->where('channel', 'email')
            ->first();

        $this->assertEquals('failed', $notification->status_sent);
        $this->assertTrue($notification->isFailed());
        $this->assertStringContainsString('returned false', $notification->error_message);
    }

    public function test_it_marks_notification_as_failed_when_exception_occurs()
    {
        $data = new NotificationData(
            orderId: 123,
            customerId: 456,
            status: 'completed',
            totalAmount: 99.99,
            type: 'order_completed',
            channel: 'email'
        );

        $job = new SendOrderNotificationJob($data);
        
        $emailChannel = \Mockery::mock(EmailNotificationChannel::class);
        $emailChannel->shouldReceive('send')
            ->once()
            ->andThrow(new \Exception('SMTP connection failed'));
        
        $logChannel = \Mockery::mock(LogNotificationChannel::class);
        
        $this->app->instance(EmailNotificationChannel::class, $emailChannel);
        $this->app->instance(LogNotificationChannel::class, $logChannel);

        try {
            $job->handle(
                app(NotificationRepositoryInterface::class),
                $emailChannel,
                $logChannel
            );
        } catch (\Exception $e) {
            // Exception is re-thrown
        }

        $notification = Notification::where('order_id', 123)
            ->where('channel', 'email')
            ->first();

        $this->assertEquals('failed', $notification->status_sent);
        $this->assertTrue($notification->isFailed());
        $this->assertStringContainsString('SMTP connection failed', $notification->error_message);
    }

    public function test_it_uses_log_channel_for_log_notifications()
    {
        $data = new NotificationData(
            orderId: 123,
            customerId: 456,
            status: 'completed',
            totalAmount: 99.99,
            type: 'order_completed',
            channel: 'log'
        );

        $job = new SendOrderNotificationJob($data);
        
        $emailChannel = \Mockery::mock(EmailNotificationChannel::class);
        
        $logChannel = \Mockery::mock(LogNotificationChannel::class);
        $logChannel->shouldReceive('send')
            ->once()
            ->andReturn(true);
        
        $this->app->instance(EmailNotificationChannel::class, $emailChannel);
        $this->app->instance(LogNotificationChannel::class, $logChannel);

        $job->handle(
            app(NotificationRepositoryInterface::class),
            $emailChannel,
            $logChannel
        );

        $notification = Notification::where('order_id', 123)
            ->where('channel', 'log')
            ->first();

        $this->assertNotNull($notification);
        $this->assertEquals('log', $notification->channel);
        $this->assertEquals('sent', $notification->status_sent);
    }

    public function test_it_handles_unknown_channel()
    {
        $data = new NotificationData(
            orderId: 123,
            customerId: 456,
            status: 'completed',
            totalAmount: 99.99,
            type: 'order_completed',
            channel: 'unknown'
        );

        $job = new SendOrderNotificationJob($data);
        
        $emailChannel = \Mockery::mock(EmailNotificationChannel::class);
        $logChannel = \Mockery::mock(LogNotificationChannel::class);
        
        $this->app->instance(EmailNotificationChannel::class, $emailChannel);
        $this->app->instance(LogNotificationChannel::class, $logChannel);

        $job->handle(
            app(NotificationRepositoryInterface::class),
            $emailChannel,
            $logChannel
        );

        $notification = Notification::where('order_id', 123)
            ->where('channel', 'unknown')
            ->first();

        $this->assertEquals('failed', $notification->status_sent);
        $this->assertStringContainsString('returned false', $notification->error_message);
    }
}

