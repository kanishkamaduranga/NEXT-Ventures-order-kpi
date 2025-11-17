# Notifications Module

A comprehensive notification system for order events that sends notifications via multiple channels (email, log) and maintains a complete history of all notifications sent.

## Overview

This module provides:
- **Queued Notifications**: Non-blocking notification delivery using Laravel queues
- **Multiple Channels**: Email and log notification channels
- **Notification History**: Complete audit trail of all notifications sent
- **Event-Driven**: Automatically triggered by order events
- **Retry Logic**: Failed notifications can be retried via queue

## Features

### Notification Channels

1. **Email Channel**
   - Sends email notifications to customers
   - Currently logs email content (configure mail settings for actual sending)
   - Includes order details, status, and total amount

2. **Log Channel**
   - Logs notifications to Laravel log files
   - Useful for debugging and monitoring
   - Includes all order information

### Notification Types

- **Order Completed**: Sent when an order is successfully processed
- **Order Failed**: Sent when an order fails during processing

### Notification History

All notifications are stored in the `notifications` table with:
- Order ID and Customer ID
- Status and total amount
- Notification type and channel
- Sent status (pending, sent, failed)
- Timestamps and error messages

## Architecture

### Domain-Driven Design

The module follows DDD principles:

```
Notifications/
├── Application/              # Application layer
│   ├── DTOs/
│   │   └── NotificationData.php
│   └── Jobs/
│       └── SendOrderNotificationJob.php
├── Domain/                  # Domain layer
│   ├── Models/
│   │   └── Notification.php
│   └── Repositories/
│       └── NotificationRepositoryInterface.php
├── Infrastructure/          # Infrastructure layer
│   ├── Persistence/
│   │   ├── Migrations/
│   │   │   └── 2025_11_17_170000_create_notifications_table.php
│   │   └── Repositories/
│   │       └── NotificationRepository.php
│   ├── Services/
│   │   ├── EmailNotificationChannel.php
│   │   └── LogNotificationChannel.php
│   └── Listeners/
│       └── NotificationEventSubscriber.php
└── Interfaces/
    └── Console/
        └── Commands/
            └── ListNotificationsCommand.php
```

## Database Schema

### notifications Table

```sql
CREATE TABLE notifications (
    id BIGINT UNSIGNED PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(255) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    type VARCHAR(255) NOT NULL, -- order_completed, order_failed
    channel VARCHAR(255) NOT NULL, -- email, log
    status_sent ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    message TEXT NULL,
    error_message TEXT NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_order_id (order_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_type (type),
    INDEX idx_status_sent (status_sent),
    INDEX idx_created_at (created_at)
);
```

## Usage

### Seeding Sample Notifications

For testing purposes, you can seed sample notifications:

```bash
# Create sample notifications for existing orders (default: 20 orders)
php artisan notifications:seed

# Create notifications for specific number of orders
php artisan notifications:seed --count=10

# Clear existing notifications and create fresh sample data
php artisan notifications:seed --clear
```

This creates a realistic mix of:
- Order completed and failed notifications
- Email and log channels
- Sent, pending, and failed statuses
- Proper timestamps and messages

### Automatic Notifications

Notifications are automatically sent when:
- An order is completed (`OrderCompleted` event)
- An order fails (`OrderFailed` event)

Both events trigger notifications via:
- Email channel
- Log channel

### Viewing Notifications

#### List All Notifications

```bash
php artisan notifications:list
```

#### Filter by Order ID

```bash
php artisan notifications:list --order-id=123
```

#### Filter by Customer ID

```bash
php artisan notifications:list --customer-id=456
```

#### Filter by Type

```bash
# Completed orders only
php artisan notifications:list --type=order_completed

# Failed orders only
php artisan notifications:list --type=order_failed
```

#### Filter by Status

```bash
# Pending notifications
php artisan notifications:list --status=pending

# Sent notifications
php artisan notifications:list --status=sent

# Failed notifications
php artisan notifications:list --status=failed
```

#### Combine Filters

```bash
php artisan notifications:list --order-id=123 --type=order_completed --status=sent
```

## Event Integration

The module listens to events from the Orders module:

### OrderCompleted Event

When an order is successfully completed:
1. Creates notification records for email and log channels
2. Queues `SendOrderNotificationJob` for each channel
3. Updates notification status when sent

### OrderFailed Event

When an order fails:
1. Creates notification records with failure reason
2. Queues `SendOrderNotificationJob` for each channel
3. Includes failure reason in notification content

## Notification Content

### Order Completed Email

```
Subject: Order #123 Confirmed

Your order #123 has been successfully processed.

Status: completed
Total Amount: $152.50

Thank you for your purchase!
```

### Order Failed Email

```
Subject: Order #123 Failed

Your order #123 could not be processed.

Status: cancelled
Reason: Payment declined by bank

Please contact support if you need assistance.
```

## Configuration

### Email Configuration

To enable actual email sending, configure Laravel mail settings in `.env`:

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

Then uncomment the Mail::raw() code in `EmailNotificationChannel.php`.

### Queue Configuration

Notifications are queued by default. Make sure your queue worker is running:

```bash
# Using Horizon (recommended)
php artisan horizon

# Or traditional queue worker
php artisan queue:work
```

## Programmatic Usage

### Send Notification Manually

```php
use Modules\Notifications\Application\DTOs\NotificationData;
use Modules\Notifications\Application\Jobs\SendOrderNotificationJob;

// Send email notification
SendOrderNotificationJob::dispatch(
    new NotificationData(
        orderId: 123,
        customerId: 456,
        status: 'completed',
        totalAmount: 152.50,
        type: 'order_completed',
        channel: 'email'
    )
);
```

### Query Notifications

```php
use Modules\Notifications\Domain\Repositories\NotificationRepositoryInterface;

$repository = app(NotificationRepositoryInterface::class);

// Get notifications for an order
$notifications = $repository->findByOrderId(123);

// Get notifications for a customer
$notifications = $repository->findByCustomerId(456);
```

## Extending the Module

### Adding New Channels

1. Create a new channel class in `Infrastructure/Services/`:

```php
namespace Modules\Notifications\Infrastructure\Services;

use Modules\Notifications\Application\DTOs\NotificationData;

class SmsNotificationChannel
{
    public function send(NotificationData $data): bool
    {
        // Implement SMS sending logic
        return true;
    }
}
```

2. Update `SendOrderNotificationJob` to handle the new channel:

```php
$success = match ($this->notificationData->channel) {
    'email' => $emailChannel->send($this->notificationData),
    'log' => $logChannel->send($this->notificationData),
    'sms' => $smsChannel->send($this->notificationData), // Add this
    default => false,
};
```

### Adding New Notification Types

1. Update the `type` field in the migration to include new types
2. Add handling in `NotificationEventSubscriber` for new events
3. Update channel services to format new notification types

## Commands Reference

| Command | Description |
|---------|-------------|
| `notifications:list [options]` | List notifications with optional filters |
| `notifications:seed [--count=N] [--clear]` | Seed sample notifications for testing |

## Troubleshooting

### Notifications Not Being Sent

1. **Check Queue Worker**: Make sure queue worker is running
   ```bash
   php artisan queue:work
   ```

2. **Check Failed Jobs**: 
   ```bash
   php artisan queue:failed
   ```

3. **Check Logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Email Not Sending

1. Verify mail configuration in `.env`
2. Check that `Mail::raw()` is uncommented in `EmailNotificationChannel.php`
3. Test mail configuration:
   ```bash
   php artisan tinker
   >>> Mail::raw('Test', function($m) { $m->to('test@example.com')->subject('Test'); });
   ```

### Notification History Missing

1. Verify migration ran:
   ```bash
   php artisan migrate:status
   ```

2. Check database table exists:
   ```bash
   php artisan tinker
   >>> \Modules\Notifications\Domain\Models\Notification::count()
   ```

## Dependencies

- Laravel 12.x
- Orders Module (for events)
- Queue System (Redis or Database)

## Notes

- Notifications are queued to avoid blocking the order workflow
- Both email and log notifications are sent for each event
- Notification history is maintained for audit purposes
- Failed notifications can be retried via queue retry mechanism
- Email sending is currently logged; configure mail settings for actual sending

