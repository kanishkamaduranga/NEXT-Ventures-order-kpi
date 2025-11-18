# Refunds Module

## Overview

The Refunds module handles order refunds (full or partial) with asynchronous processing, real-time analytics updates, and idempotency guarantees. Refunds are processed through queued jobs to avoid blocking the main application flow.

## Features

- ✅ **Full and Partial Refunds**: Support for both full order refunds and partial refunds
- ✅ **Asynchronous Processing**: Refunds are processed via queued jobs
- ✅ **Idempotency**: Re-running a refund request with the same `refund_id` won't double-count or break data
- ✅ **Real-time Analytics Updates**: Automatically updates KPIs and leaderboard when refunds are processed
- ✅ **Payment Gateway Integration**: Simulated payment gateway refund processing
- ✅ **Comprehensive Logging**: All refund operations are logged for audit purposes

## Module Structure

```
modules/Refunds/
├── Application/
│   ├── DTOs/
│   │   └── RefundRequest.php          # DTO for refund requests
│   └── Jobs/
│       └── ProcessRefundJob.php       # Queued job for processing refunds
├── Domain/
│   ├── Events/
│   │   ├── RefundProcessed.php         # Event dispatched when refund succeeds
│   │   └── RefundFailed.php            # Event dispatched when refund fails
│   ├── Models/
│   │   └── Refund.php                  # Eloquent model for refunds
│   └── Repositories/
│       └── RefundRepositoryInterface.php
├── Infrastructure/
│   ├── Persistence/
│   │   ├── Migrations/
│   │   │   └── 2025_11_18_120000_create_refunds_table.php
│   │   └── Repositories/
│   │       └── RefundRepository.php
│   └── Services/
│       └── PaymentGatewayRefundService.php  # Simulated payment gateway
├── Interfaces/
│   └── Console/
│       └── Commands/
│           ├── ProcessRefundCommand.php     # Artisan command to process refunds
│           └── ListRefundsCommand.php        # Artisan command to list refunds
└── Providers/
    └── RefundsServiceProvider.php
```

## Database Schema

### `refunds` Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `order_id` | bigint | Foreign key to orders table |
| `customer_id` | bigint | Customer ID |
| `refund_id` | string (unique) | **Unique identifier for idempotency** |
| `amount` | decimal(10,2) | Refund amount |
| `type` | enum | `full` or `partial` |
| `status` | enum | `pending`, `processing`, `completed`, `failed` |
| `reason` | string | Reason for refund |
| `failure_reason` | text | Failure reason if refund failed |
| `payment_reference` | string | Payment gateway refund reference |
| `processed_at` | timestamp | When refund was processed |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Update timestamp |

**Indexes:**
- `order_id`
- `customer_id`
- `refund_id` (unique)
- `status`
- `created_at`

## Idempotency

The module ensures idempotency through the `refund_id` field:

1. **Unique Constraint**: The `refund_id` column has a unique constraint in the database
2. **Duplicate Check**: Before processing, the job checks if a refund with the same `refund_id` already exists
3. **Re-processing Protection**: If a refund already exists and is completed, the event is re-dispatched for analytics updates without re-processing the refund

### Example

```php
// First request
ProcessRefundJob::dispatch(new RefundRequest(
    orderId: 123,
    amount: 100.00,
    type: 'full',
    refundId: 'REF-UNIQUE-123'
));

// Second request with same refund_id (idempotent)
ProcessRefundJob::dispatch(new RefundRequest(
    orderId: 123,
    amount: 100.00,
    type: 'full',
    refundId: 'REF-UNIQUE-123'  // Same ID
));
// This will be skipped if already processed, or re-dispatched event if completed
```

## Usage

### Process a Refund

#### Via Artisan Command

```bash
# Full refund
php artisan refunds:process {order_id}

# Partial refund
php artisan refunds:process {order_id} --amount=50.00 --type=partial

# With reason
php artisan refunds:process {order_id} --reason="Customer requested refund"

# With custom refund ID for idempotency
php artisan refunds:process {order_id} --refund-id=REF-UNIQUE-123

# Process synchronously (not queued)
php artisan refunds:process {order_id} --sync
```

#### Via Code

```php
use Modules\Refunds\Application\DTOs\RefundRequest;
use Modules\Refunds\Application\Jobs\ProcessRefundJob;

// Full refund
$refundRequest = new RefundRequest(
    orderId: 123,
    amount: 0, // Will be set to order total for full refunds
    type: 'full',
    reason: 'Customer requested refund',
    refundId: 'REF-UNIQUE-123' // Optional: for idempotency
);

ProcessRefundJob::dispatch($refundRequest);

// Partial refund
$refundRequest = new RefundRequest(
    orderId: 123,
    amount: 50.00,
    type: 'partial',
    reason: 'Partial refund for damaged item',
    refundId: 'REF-PARTIAL-456'
);

ProcessRefundJob::dispatch($refundRequest);
```

### List Refunds

```bash
# List all refunds
php artisan refunds:list

# Filter by order
php artisan refunds:list --order-id=123

# Filter by customer
php artisan refunds:list --customer-id=1001

# Filter by status
php artisan refunds:list --status=completed

# Filter by type
php artisan refunds:list --type=partial

# Limit results
php artisan refunds:list --limit=50
```

## Workflow

1. **Refund Request Created**: A refund request is created with status `pending`
2. **Job Queued**: `ProcessRefundJob` is dispatched to the `refunds` queue
3. **Idempotency Check**: Job checks if refund with same `refund_id` already exists
4. **Status Updated**: Refund status changed to `processing`
5. **Payment Gateway**: Refund processed through payment gateway service
6. **Status Updated**: 
   - If successful: status → `completed`, `processed_at` set
   - If failed: status → `failed`, `failure_reason` set
7. **Event Dispatched**: 
   - `RefundProcessed` event (if successful)
   - `RefundFailed` event (if failed)
8. **Analytics Updated**: Analytics module listens to events and updates KPIs/leaderboard

## Events

### RefundProcessed

Dispatched when a refund is successfully processed.

**Properties:**
- `Refund $refund`: The refund model
- `Order $order`: The associated order

**Listeners:**
- `AnalyticsEventSubscriber`: Updates KPIs and leaderboard

### RefundFailed

Dispatched when a refund processing fails.

**Properties:**
- `Refund $refund`: The refund model
- `Order $order`: The associated order
- `string $reason`: Failure reason

## Integration with Analytics

The Refunds module integrates with the Analytics module through events:

### When Refund is Processed

1. **KPIs Updated**:
   - Total revenue is decremented by refund amount
   - Refund amount is incremented
   - Average order value is recalculated

2. **Leaderboard Updated**:
   - Customer spending is decremented by refund amount
   - Customer rank may change

### Example

```php
// Process refund
$refund = ProcessRefundJob::dispatch($refundRequest);

// Analytics automatically updated via RefundProcessed event:
// - Daily KPIs: revenue -= refund_amount, refund_amount += refund_amount
// - Leaderboard: customer_spending -= refund_amount
```

## Queue Configuration

Refunds are processed on the `refunds` queue. Make sure your queue worker is configured:

```bash
# Process refunds queue
php artisan queue:work --queue=refunds

# Or with Horizon
# Horizon automatically processes all queues including 'refunds'
```

## Commands Reference

| Command | Description |
|---------|-------------|
| `refunds:process {order_id} [options]` | Process a refund for an order |
| `refunds:list [options]` | List refunds with optional filters |

### Process Refund Options

- `--amount`: Refund amount (required for partial refunds)
- `--type`: Refund type (`full` or `partial`, default: `full`)
- `--reason`: Reason for refund
- `--refund-id`: Unique refund ID for idempotency
- `--sync`: Process synchronously instead of queuing

### List Refunds Options

- `--order-id`: Filter by order ID
- `--customer-id`: Filter by customer ID
- `--status`: Filter by status (`pending`, `processing`, `completed`, `failed`)
- `--type`: Filter by type (`full`, `partial`)
- `--limit`: Number of records to display (default: 20)

## Testing

### Test Full Refund

```bash
# 1. Find a completed order
php artisan orders:list --status=completed --limit=1

# 2. Process full refund
php artisan refunds:process {order_id} --reason="Test refund"

# 3. Check refund status
php artisan refunds:list --order-id={order_id}

# 4. Verify analytics updated
php artisan analytics:report --date=$(date +%Y-%m-%d)
```

### Test Partial Refund

```bash
# Process partial refund
php artisan refunds:process {order_id} --amount=50.00 --type=partial --reason="Partial refund"

# Verify
php artisan refunds:list --order-id={order_id}
```

### Test Idempotency

```bash
# First request
php artisan refunds:process {order_id} --refund-id=TEST-REF-123

# Wait for processing...

# Second request with same refund_id (should be idempotent)
php artisan refunds:process {order_id} --refund-id=TEST-REF-123

# Check logs - should see "Refund already processed (idempotency check)"
```

## Troubleshooting

### Refund Not Processing

1. **Check Queue Worker**: Ensure queue worker is running
   ```bash
   php artisan queue:work --queue=refunds
   ```

2. **Check Job Status**: View failed jobs
   ```bash
   php artisan queue:failed
   ```

3. **Check Logs**: Review Laravel logs for errors
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Refund Failed

1. **Check Failure Reason**: 
   ```bash
   php artisan refunds:list --status=failed
   ```

2. **Retry Failed Refund**: 
   - Fix the underlying issue
   - Create a new refund request with a new `refund_id`

### Analytics Not Updating

1. **Check Events**: Ensure `RefundProcessed` event is being dispatched
2. **Check Analytics Listener**: Verify `AnalyticsEventSubscriber` is registered
3. **Check Redis**: Ensure Redis is running and accessible

## Future Enhancements

- [ ] Webhook support for external refund notifications
- [ ] Refund approval workflow
- [ ] Bulk refund processing
- [ ] Refund analytics dashboard
- [ ] Email notifications for refunds
- [ ] Refund history API endpoints

