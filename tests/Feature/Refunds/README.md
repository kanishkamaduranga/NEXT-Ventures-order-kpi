# Refunds Module Test Suite

## Overview

Comprehensive test suite for the Refunds module covering:
- Domain models
- Repository operations
- Payment gateway service
- Job processing with idempotency
- Artisan commands
- Event handling

## Test Files

### Unit Tests

1. **RefundModelTest.php** (10 tests)
   - Refund creation
   - Status checks (pending, processing, completed, failed)
   - Type checks (full, partial)
   - Type casting (decimal, datetime)
   - Order relationship

2. **RefundRepositoryTest.php** (14 tests)
   - Create refunds
   - Find by ID and refund_id
   - Update refunds and status
   - Find by order/customer ID
   - Status update with processed_at and failure_reason

3. **PaymentGatewayRefundServiceTest.php** (5 tests)
   - Refund processing
   - Success/failure handling
   - Different amounts and payment references

4. **ProcessRefundJobTest.php** (11 tests)
   - Refund record creation
   - Full and partial refunds
   - Event dispatching (RefundProcessed, RefundFailed)
   - Idempotency handling
   - Order validation
   - Amount validation
   - Payment reference handling

### Feature Tests

1. **RefundCommandsTest.php** (12 tests)
   - Process full/partial refunds via command
   - Validation (amount required, type validation)
   - Custom refund ID for idempotency
   - List refunds with filters
   - Combined filters
   - Limit option
   - Empty results handling

## Running Tests

### Run All Refunds Tests

```bash
# Using Laravel Sail
./vendor/bin/sail artisan test --filter=Refunds

# Or using PHPUnit directly
./vendor/bin/sail php vendor/bin/phpunit tests/Unit/Refunds
./vendor/bin/sail php vendor/bin/phpunit tests/Feature/Refunds
```

### Run Specific Test File

```bash
./vendor/bin/sail artisan test tests/Unit/Refunds/RefundModelTest.php
./vendor/bin/sail artisan test tests/Feature/Refunds/RefundCommandsTest.php
```

### Run Specific Test Method

```bash
./vendor/bin/sail artisan test --filter=test_it_handles_idempotency_when_refund_already_exists
```

## Test Coverage

- ✅ Domain model methods and relationships
- ✅ Repository CRUD operations
- ✅ Payment gateway service
- ✅ Job processing with idempotency
- ✅ Event dispatching
- ✅ Artisan commands
- ✅ Validation logic
- ✅ Error handling
- ✅ Edge cases (order not found, amount validation)

## Key Test Scenarios

### Idempotency Testing

The tests verify that:
- Re-processing a refund with the same `refund_id` is skipped
- If already completed, the event is re-dispatched for analytics
- No duplicate refunds are created

### Event Testing

Tests verify that:
- `RefundProcessed` event is dispatched on success
- `RefundFailed` event is dispatched on failure
- Events include correct refund and order data

### Command Testing

Tests verify:
- Full and partial refund processing
- Validation of required fields
- Filtering and listing functionality
- Error messages for invalid inputs

## Notes

- Tests use `RefreshDatabase` trait to ensure clean state
- Payment gateway is mocked to ensure deterministic results
- Events are faked to verify dispatching without side effects
- Queue is faked to test job dispatching without actual processing

