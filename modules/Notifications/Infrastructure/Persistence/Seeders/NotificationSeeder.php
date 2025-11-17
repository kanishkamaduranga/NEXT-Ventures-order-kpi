<?php

namespace Modules\Notifications\Infrastructure\Persistence\Seeders;

use Illuminate\Database\Seeder;
use Modules\Notifications\Domain\Models\Notification;
use Modules\Orders\Domain\Models\Order;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing orders or create sample data
        $orders = Order::take(20)->get();

        if ($orders->isEmpty()) {
            $this->command->warn('No orders found. Please import orders first using: php artisan orders:import');
            return;
        }

        $this->command->info('Creating sample notifications...');

        $channels = ['email', 'log'];
        $types = ['order_completed', 'order_failed'];
        $statuses = ['pending', 'sent', 'failed'];

        $notificationCount = 0;

        foreach ($orders as $order) {
            // Create notifications for each order
            // Mix of completed and failed notifications
            $type = fake()->randomElement($types);
            
            // Determine notification status based on type
            $statusSent = match($type) {
                'order_completed' => fake()->randomElement(['sent', 'sent', 'sent', 'pending']), // Mostly sent
                'order_failed' => fake()->randomElement(['sent', 'sent', 'failed', 'pending']), // Mix
                default => 'sent',
            };

            // Create notification for each channel
            foreach ($channels as $channel) {
                $notification = Notification::create([
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'status' => $type === 'order_completed' ? 'completed' : 'cancelled',
                    'total_amount' => $order->total_amount,
                    'type' => $type,
                    'channel' => $channel,
                    'status_sent' => $statusSent,
                    'message' => $this->generateMessage($type, $order),
                    'error_message' => $statusSent === 'failed' ? 'Failed to send notification: Connection timeout' : null,
                    'sent_at' => $statusSent === 'sent' ? fake()->dateTimeBetween($order->created_at, 'now') : null,
                    'created_at' => fake()->dateTimeBetween($order->created_at, 'now'),
                    'updated_at' => now(),
                ]);

                $notificationCount++;
            }
        }

        $this->command->info("Created {$notificationCount} sample notifications!");
        $this->command->info("Breakdown:");
        $this->command->info("  - Order Completed: " . Notification::where('type', 'order_completed')->count());
        $this->command->info("  - Order Failed: " . Notification::where('type', 'order_failed')->count());
        $this->command->info("  - Email Channel: " . Notification::where('channel', 'email')->count());
        $this->command->info("  - Log Channel: " . Notification::where('channel', 'log')->count());
        $this->command->info("  - Sent: " . Notification::where('status_sent', 'sent')->count());
        $this->command->info("  - Pending: " . Notification::where('status_sent', 'pending')->count());
        $this->command->info("  - Failed: " . Notification::where('status_sent', 'failed')->count());
    }

    private function generateMessage(string $type, Order $order): string
    {
        if ($type === 'order_completed') {
            return sprintf(
                "Order #%d completed successfully for customer #%d. Total: $%.2f",
                $order->id,
                $order->customer_id,
                $order->total_amount
            );
        } else {
            $reasons = [
                'Payment declined by bank',
                'Insufficient stock',
                'Payment gateway timeout',
                'Stock reservation failed',
                'Order validation failed',
            ];

            return sprintf(
                "Order #%d failed for customer #%d. Reason: %s",
                $order->id,
                $order->customer_id,
                fake()->randomElement($reasons)
            );
        }
    }
}

