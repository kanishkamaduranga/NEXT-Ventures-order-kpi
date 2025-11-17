# Testing the Notifications Module

This guide will help you test the Notifications module to ensure it's working correctly.

## Prerequisites

1. **Queue Worker Running**: Notifications are queued, so you need a queue worker running
2. **Orders in Database**: You need at least one order to process
3. **Database Migrated**: The notifications table must exist

## Step 1: Start Queue Worker

Notifications are queued, so you need a queue worker running. Choose one:

### Option A: Using Laravel Horizon (Recommended)

```bash
./vendor/bin/sail artisan horizon
```

### Option B: Using Traditional Queue Worker

```bash
./vendor/bin/sail artisan queue:work
```

Keep this running in a separate terminal.

## Step 2: Seed Sample Notifications (Quick Test)

The easiest way to test is to seed sample notifications:

```bash
# Create sample notifications for existing orders
./vendor/bin/sail artisan notifications:seed

# Create notifications for specific number of orders
./vendor/bin/sail artisan notifications:seed --count=10

# Clear existing and create fresh sample data
./vendor/bin/sail artisan notifications:seed --clear
```

This will create:
- Notifications for both `order_completed` and `order_failed` types
- Both `email` and `log` channels
- Mix of `sent`, `pending`, and `failed` statuses
- Realistic timestamps and messages

Then view them:
```bash
./vendor/bin/sail artisan notifications:list
```

## Step 2 Alternative: Check Current State

If you want to test with real notifications from order processing:

```bash
./vendor/bin/sail artisan notifications:list
```

You should see "No notifications found" if this is a fresh setup.

## Step 3: Test Order Completion Notification

### 3.1. Create or Find an Order

If you don't have orders, generate sample data:

```bash
# Generate sample orders CSV
./vendor/bin/sail artisan orders:generate-sample

# Import orders (this will create orders in pending status)
./vendor/bin/sail artisan orders:import storage/app/orders.csv
```

### 3.2. Process an Order to Completion

Process an order through the workflow. This will trigger the `OrderCompleted` event:

```bash
# Get an order ID from the database or use one you know
./vendor/bin/sail artisan orders:process {order_id}
```

For example:
```bash
./vendor/bin/sail artisan orders:process 1
```

### 3.3. Wait for Queue Processing

Wait a few seconds for the queue worker to process the notification jobs.

### 3.4. Check Notifications

```bash
# List all notifications
./vendor/bin/sail artisan notifications:list

# Or filter by the order you just processed
./vendor/bin/sail artisan notifications:list --order-id=1
```

You should see:
- 2 notifications (one for email, one for log)
- Type: `order_completed`
- Status: `sent` (if successful)
- Channel: `email` and `log`

## Step 4: Test Order Failure Notification

### 4.1. Process an Order That Will Fail

Orders can fail due to:
- Stock reservation failure (10% chance)
- Payment failure (15% chance)

Keep processing orders until one fails, or manually update an order status to trigger failure.

Alternatively, you can check existing failed orders:

```bash
# In Laravel Tinker
./vendor/bin/sail artisan tinker
```

```php
// Find a failed order
$failedOrder = \Modules\Orders\Domain\Models\Order::where('status', 'cancelled')->first();

// If no failed orders, create a test scenario
// You can manually trigger the OrderFailed event
$order = \Modules\Orders\Domain\Models\Order::first();
event(new \Modules\Orders\Domain\Events\OrderFailed($order, 'Test failure reason'));
```

### 4.2. Check Notifications for Failed Order

```bash
./vendor/bin/sail artisan notifications:list --type=order_failed
```

You should see:
- Type: `order_failed`
- Status includes failure reason
- Both email and log channels

## Step 5: Verify Log Notifications

Check the Laravel log file to see log notifications:

```bash
# View recent log entries
tail -f storage/logs/laravel.log | grep "Order Notification"
```

Or in a separate terminal:

```bash
./vendor/bin/sail artisan tinker
```

```php
// Check log file
file_get_contents(storage_path('logs/laravel.log'));
```

## Step 6: Verify Database Records

Check the notifications table directly:

```bash
./vendor/bin/sail artisan tinker
```

```php
// Count notifications
\Modules\Notifications\Domain\Models\Notification::count();

// Get all notifications
\Modules\Notifications\Domain\Models\Notification::all();

// Get notifications for a specific order
\Modules\Notifications\Domain\Models\Notification::where('order_id', 1)->get();

// Get sent notifications
\Modules\Notifications\Domain\Models\Notification::where('status_sent', 'sent')->get();

// Get failed notifications
\Modules\Notifications\Domain\Models\Notification::where('status_sent', 'failed')->get();
```

## Step 7: Test Filtering Options

Test all the filtering options:

```bash
# Filter by order ID
./vendor/bin/sail artisan notifications:list --order-id=1

# Filter by customer ID
./vendor/bin/sail artisan notifications:list --customer-id=1001

# Filter by type
./vendor/bin/sail artisan notifications:list --type=order_completed
./vendor/bin/sail artisan notifications:list --type=order_failed

# Filter by status
./vendor/bin/sail artisan notifications:list --status=pending
./vendor/bin/sail artisan notifications:list --status=sent
./vendor/bin/sail artisan notifications:list --status=failed

# Combine filters
./vendor/bin/sail artisan notifications:list --order-id=1 --type=order_completed --status=sent

# Limit results
./vendor/bin/sail artisan notifications:list --limit=10
```

## Step 8: Check Queue Status

If using Horizon, check the dashboard:

1. Open browser: http://localhost:8088/horizon
2. Look for `SendOrderNotificationJob` in the jobs list
3. Check completed and failed jobs

Or check queue status via command:

```bash
# Check failed jobs
./vendor/bin/sail artisan queue:failed

# Retry failed jobs
./vendor/bin/sail artisan queue:retry all
```

## Step 9: Test Email Channel (Optional)

Email notifications are currently logged. To test actual email sending:

1. Configure mail in `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

2. Uncomment the `Mail::raw()` code in:
   `modules/Notifications/Infrastructure/Services/EmailNotificationChannel.php`

3. Process an order again and check your mail inbox.

## Quick Test Script

Here's a quick script to test everything at once:

```bash
#!/bin/bash

echo "=== Testing Notifications Module ==="

# 1. Check queue worker is running (manual check required)
echo "1. Make sure queue worker is running: ./vendor/bin/sail artisan horizon"

# 2. Generate and import orders
echo "2. Generating sample orders..."
./vendor/bin/sail artisan orders:generate-sample
./vendor/bin/sail artisan orders:import storage/app/orders.csv

# 3. Get first order ID
ORDER_ID=$(./vendor/bin/sail artisan tinker --execute="echo \Modules\Orders\Domain\Models\Order::first()->id;")

echo "3. Processing order ID: $ORDER_ID"
./vendor/bin/sail artisan orders:process $ORDER_ID

# 4. Wait for queue processing
echo "4. Waiting 5 seconds for queue processing..."
sleep 5

# 5. Check notifications
echo "5. Checking notifications..."
./vendor/bin/sail artisan notifications:list --order-id=$ORDER_ID

echo "=== Test Complete ==="
```

## Expected Results

After processing an order successfully, you should see:

1. **In Database** (`notifications` table):
   - 2 records (email + log)
   - `order_id`: The processed order ID
   - `type`: `order_completed`
   - `channel`: `email` and `log`
   - `status_sent`: `sent`
   - `sent_at`: Timestamp

2. **In Log File** (`storage/logs/laravel.log`):
   - Log entry with "Order Notification"
   - Contains order_id, customer_id, status, total_amount

3. **In Command Output**:
   - Table showing notification details
   - Status: `sent`
   - Channel: `email` and `log`

## Troubleshooting

### No Notifications Created

1. Check if events are being dispatched:
```bash
./vendor/bin/sail artisan tinker
```
```php
// Manually trigger event to test
$order = \Modules\Orders\Domain\Models\Order::first();
event(new \Modules\Orders\Domain\Events\OrderCompleted($order));
```

2. Check event subscriber is registered:
```bash
./vendor/bin/sail artisan tinker
```
```php
\Illuminate\Support\Facades\Event::getListeners(\Modules\Orders\Domain\Events\OrderCompleted::class);
```

### Notifications Stuck in "pending"

1. Check queue worker is running
2. Check queue connection in `.env`: `QUEUE_CONNECTION=database` or `redis`
3. Check failed jobs: `./vendor/bin/sail artisan queue:failed`

### Notifications Marked as "failed"

1. Check error message in database:
```bash
./vendor/bin/sail artisan tinker
```
```php
\Modules\Notifications\Domain\Models\Notification::where('status_sent', 'failed')->first()->error_message;
```

2. Check logs for detailed errors:
```bash
tail -f storage/logs/laravel.log
```

## Manual Testing via Tinker

You can also test manually using Laravel Tinker:

```bash
./vendor/bin/sail artisan tinker
```

```php
// Get an order
$order = \Modules\Orders\Domain\Models\Order::first();

// Manually trigger OrderCompleted event
event(new \Modules\Orders\Domain\Events\OrderCompleted($order));

// Or trigger OrderFailed event
event(new \Modules\Orders\Domain\Events\OrderFailed($order, 'Manual test failure'));

// Wait a moment, then check notifications
sleep(2);
\Modules\Notifications\Domain\Models\Notification::latest()->get();
```

## Summary

The notification module should:
✅ Create notification records when orders complete/fail
✅ Queue notification jobs (non-blocking)
✅ Send notifications via email and log channels
✅ Store complete history in database
✅ Update status (pending → sent/failed)
✅ Include all required fields (order_id, customer_id, status, total)

If all these work, your notification module is functioning correctly!

