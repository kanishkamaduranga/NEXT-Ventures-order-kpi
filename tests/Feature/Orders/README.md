# Orders Module Test Suite

## Overview

Comprehensive test suite for the Orders module covering:
- CSV Import functionality
- Order processing workflow
- Stock reservation
- Payment processing
- Order finalization and rollback
- Artisan commands

## Test Files

### Feature Tests

1. **ImportOrdersTest.php**
   - Tests CSV import functionality
   - Tests chunk processing
   - Tests duplicate order handling
   - Tests error handling

2. **OrderProcessingWorkflowTest.php**
   - Tests complete order workflow
   - Tests stock reservation (success and failure)
   - Tests payment processing
   - Tests order finalization
   - Tests order rollback

3. **OrderCommandsTest.php**
   - Tests `orders:import` command
   - Tests `orders:process` command
   - Tests command validation

### Unit Tests

1. **StockReservationServiceTest.php**
   - Tests stock reservation service
   - Tests stock release
   - Tests logging

2. **PaymentGatewayServiceTest.php**
   - Tests payment processing
   - Tests refund processing
   - Tests failure rate simulation

3. **OrderModelTest.php**
   - Tests Order model methods
   - Tests relationships
   - Tests status checks
   - Tests data casting

## Running Tests

### Run All Orders Tests

```bash
# Using Laravel Sail
./vendor/bin/sail artisan test --testsuite=Feature --filter=Orders
./vendor/bin/sail artisan test --testsuite=Unit --filter=Orders

# Or using PHPUnit directly
./vendor/bin/sail php vendor/bin/phpunit tests/Feature/Orders
./vendor/bin/sail php vendor/bin/phpunit tests/Unit/Orders
```

### Run Specific Test File

```bash
./vendor/bin/sail artisan test tests/Feature/Orders/ImportOrdersTest.php
./vendor/bin/sail artisan test tests/Unit/Orders/OrderModelTest.php
```

### Run Specific Test Method

```bash
./vendor/bin/sail artisan test --filter=it_can_import_orders_from_csv
```

## Test Coverage

- ✅ CSV Import
- ✅ Order Creation
- ✅ Stock Reservation
- ✅ Payment Processing
- ✅ Order Finalization
- ✅ Order Rollback
- ✅ Event Dispatching
- ✅ Command Validation
- ✅ Model Relationships
- ✅ Status Management

## Notes

- Tests use `RefreshDatabase` trait to ensure clean state
- Queue is faked in feature tests to avoid actual job processing
- Events are faked to verify event dispatching
- Tests create temporary CSV files for import testing

