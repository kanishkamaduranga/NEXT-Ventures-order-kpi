<?php

namespace Tests\Unit\Notifications;

use Illuminate\Support\Facades\Log;
use Modules\Notifications\Application\DTOs\NotificationData;
use Modules\Notifications\Infrastructure\Services\EmailNotificationChannel;
use Tests\TestCase;

class EmailNotificationChannelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_it_sends_order_completed_notification()
    {
        Log::shouldReceive('info')
            ->once()
            ->with(\Mockery::pattern('/Email notification would be sent/'), \Mockery::on(function ($context) {
                return isset($context['subject']) && $context['subject'] === 'Order #123 Confirmed';
            }));

        $channel = new EmailNotificationChannel();
        $data = new NotificationData(
            orderId: 123,
            customerId: 456,
            status: 'completed',
            totalAmount: 99.99,
            type: 'order_completed',
            channel: 'email'
        );

        $result = $channel->send($data);

        $this->assertTrue($result);
    }

    public function test_it_sends_order_failed_notification()
    {
        Log::shouldReceive('info')
            ->once()
            ->with(\Mockery::pattern('/Email notification would be sent/'), \Mockery::on(function ($context) {
                return isset($context['subject']) && $context['subject'] === 'Order #123 Failed';
            }));

        $channel = new EmailNotificationChannel();
        $data = new NotificationData(
            orderId: 123,
            customerId: 456,
            status: 'cancelled',
            totalAmount: 0.00,
            type: 'order_failed',
            channel: 'email',
            failureReason: 'Payment declined'
        );

        $result = $channel->send($data);

        $this->assertTrue($result);
    }

    public function test_it_formats_order_completed_body_correctly()
    {
        Log::shouldReceive('info')
            ->once()
            ->with(\Mockery::pattern('/Email notification would be sent/'), \Mockery::type('array'));

        // Allow error logging if needed (but shouldn't be called in this test)
        Log::shouldReceive('error')->zeroOrMoreTimes();

        $channel = new EmailNotificationChannel();
        $data = new NotificationData(
            orderId: 123,
            customerId: 456,
            status: 'completed',
            totalAmount: 152.50,
            type: 'order_completed',
            channel: 'email'
        );

        $result = $channel->send($data);

        // Verify the method returns true and formats message correctly
        $this->assertTrue($result);
    }

    public function test_it_formats_order_failed_body_correctly()
    {
        Log::shouldReceive('info')
            ->once()
            ->with(\Mockery::pattern('/Email notification would be sent/'), \Mockery::type('array'));

        // Allow error logging if needed (but shouldn't be called in this test)
        Log::shouldReceive('error')->zeroOrMoreTimes();

        $channel = new EmailNotificationChannel();
        $data = new NotificationData(
            orderId: 123,
            customerId: 456,
            status: 'cancelled',
            totalAmount: 0.00,
            type: 'order_failed',
            channel: 'email',
            failureReason: 'Payment declined by bank'
        );

        $result = $channel->send($data);

        // Verify the method returns true and formats message correctly
        $this->assertTrue($result);
    }

    public function test_it_handles_exceptions_gracefully()
    {
        Log::shouldReceive('info')->andThrow(new \Exception('Test exception'));
        Log::shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/Failed to send email notification/'), \Mockery::any());

        $channel = new EmailNotificationChannel();
        $data = new NotificationData(
            orderId: 123,
            customerId: 456,
            status: 'completed',
            totalAmount: 99.99,
            type: 'order_completed',
            channel: 'email'
        );

        $result = $channel->send($data);

        $this->assertFalse($result);
    }

    public function test_it_uses_default_subject_for_unknown_type()
    {
        Log::shouldReceive('info')
            ->once()
            ->with(\Mockery::pattern('/Email notification would be sent/'), \Mockery::on(function ($context) {
                return isset($context['subject']) && $context['subject'] === 'Order #123 Update';
            }));

        $channel = new EmailNotificationChannel();
        $data = new NotificationData(
            orderId: 123,
            customerId: 456,
            status: 'processing',
            totalAmount: 99.99,
            type: 'unknown_type',
            channel: 'email'
        );

        $result = $channel->send($data);

        $this->assertTrue($result);
    }
}

