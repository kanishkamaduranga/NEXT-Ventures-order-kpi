<?php

namespace Tests\Feature\Notifications;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Notifications\Domain\Models\Notification;
use Tests\TestCase;

class NotificationCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_all_notifications()
    {
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
            'customer_id' => 789,
            'status' => 'cancelled',
            'total_amount' => 50.00,
            'type' => 'order_failed',
            'channel' => 'log',
            'status_sent' => 'pending',
        ]);

        $this->artisan('notifications:list')
            ->expectsOutputToContain('Found 2 notification(s):')
            ->assertExitCode(0);
    }

    public function test_it_filters_by_order_id()
    {
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
            'customer_id' => 789,
            'status' => 'completed',
            'total_amount' => 50.00,
            'type' => 'order_completed',
            'channel' => 'email',
            'status_sent' => 'sent',
        ]);

        $this->artisan('notifications:list --order-id=123')
            ->expectsOutputToContain('Found 1 notification(s):')
            ->assertExitCode(0);
    }

    public function test_it_filters_by_customer_id()
    {
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
            'customer_id' => 789,
            'status' => 'completed',
            'total_amount' => 50.00,
            'type' => 'order_completed',
            'channel' => 'email',
            'status_sent' => 'sent',
        ]);

        $this->artisan('notifications:list --customer-id=456')
            ->expectsOutputToContain('Found 1 notification(s):')
            ->assertExitCode(0);
    }

    public function test_it_filters_by_type()
    {
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
            'customer_id' => 789,
            'status' => 'cancelled',
            'total_amount' => 50.00,
            'type' => 'order_failed',
            'channel' => 'email',
            'status_sent' => 'sent',
        ]);

        $this->artisan('notifications:list --type=order_completed')
            ->expectsOutputToContain('Found 1 notification(s):')
            ->assertExitCode(0);
    }

    public function test_it_filters_by_status()
    {
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
            'customer_id' => 789,
            'status' => 'completed',
            'total_amount' => 50.00,
            'type' => 'order_completed',
            'channel' => 'email',
            'status_sent' => 'pending',
        ]);

        $this->artisan('notifications:list --status=sent')
            ->expectsOutputToContain('Found 1 notification(s):')
            ->assertExitCode(0);
    }

    public function test_it_combines_multiple_filters()
    {
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
            'status_sent' => 'pending',
        ]);

        Notification::create([
            'order_id' => 124,
            'customer_id' => 456,
            'status' => 'cancelled',
            'total_amount' => 50.00,
            'type' => 'order_failed',
            'channel' => 'email',
            'status_sent' => 'sent',
        ]);

        $this->artisan('notifications:list --order-id=123 --type=order_completed --status=sent')
            ->expectsOutputToContain('Found 1 notification(s):')
            ->assertExitCode(0);
    }

    public function test_it_respects_limit_option()
    {
        // Create 10 notifications
        for ($i = 1; $i <= 10; $i++) {
            Notification::create([
                'order_id' => 100 + $i,
                'customer_id' => 456,
                'status' => 'completed',
                'total_amount' => 99.99,
                'type' => 'order_completed',
                'channel' => 'email',
                'status_sent' => 'sent',
            ]);
        }

        $this->artisan('notifications:list --limit=5')
            ->expectsOutputToContain('Found 5 notification(s):')
            ->assertExitCode(0);
    }

    public function test_it_shows_message_when_no_notifications_found()
    {
        $this->artisan('notifications:list')
            ->expectsOutput('No notifications found.')
            ->assertExitCode(0);
    }

    public function test_it_shows_message_when_filter_returns_no_results()
    {
        Notification::create([
            'order_id' => 123,
            'customer_id' => 456,
            'status' => 'completed',
            'total_amount' => 99.99,
            'type' => 'order_completed',
            'channel' => 'email',
            'status_sent' => 'sent',
        ]);

        $this->artisan('notifications:list --order-id=999')
            ->expectsOutput('No notifications found.')
            ->assertExitCode(0);
    }
}

