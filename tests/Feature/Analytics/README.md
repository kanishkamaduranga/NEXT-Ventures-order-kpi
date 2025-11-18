# Analytics Module Test Suite

## Overview

Comprehensive test suite for the Analytics module covering:
- Event-driven KPI updates
- Leaderboard management
- HTTP API endpoints
- Use cases
- Domain entities

## Test Files

### Feature Tests

1. **AnalyticsEventSubscriberTest.php**
   - Tests KPI updates when orders are completed
   - Tests leaderboard updates
   - Tests failed order handling
   - Tests unique customer tracking

2. **AnalyticsControllerTest.php**
   - Tests daily report endpoint
   - Tests leaderboard endpoint
   - Tests date range KPI endpoint
   - Tests error handling

### Unit Tests

1. **UpdateDailyKpisUseCaseTest.php**
   - Tests KPI increment for successful orders
   - Tests KPI increment for failed orders
   - Tests refund handling
   - Tests multiple orders tracking
   - Tests average order value calculation

2. **UpdateLeaderboardUseCaseTest.php**
   - Tests customer spending updates
   - Tests multiple customers
   - Tests refund handling
   - Tests customer ranking
   - Tests top customers limit

3. **DailyKpiEntityTest.php**
   - Tests DailyKpi entity creation
   - Tests conversion rate calculation
   - Tests edge cases (zero orders, all failed)

4. **KpiDateValueObjectTest.php**
   - Tests KpiDate value object
   - Tests date validation
   - Tests date comparison

## Running Tests

### Run All Analytics Tests

```bash
# Using Laravel Sail
./vendor/bin/sail artisan test --filter=Analytics

# Or using PHPUnit directly
./vendor/bin/sail php vendor/bin/phpunit tests/Feature/Analytics
./vendor/bin/sail php vendor/bin/phpunit tests/Unit/Analytics
```

### Run Specific Test File

```bash
./vendor/bin/sail artisan test tests/Feature/Analytics/AnalyticsEventSubscriberTest.php
./vendor/bin/sail artisan test tests/Unit/Analytics/UpdateDailyKpisUseCaseTest.php
```

### Run Specific Test Method

```bash
./vendor/bin/sail artisan test --filter=test_it_updates_kpis_when_order_completed
```

## Test Requirements

### Redis Configuration

The Analytics module uses Redis for storage. For tests to run:

1. **Redis must be running** in the Docker environment
2. **Redis connection** must be configured in `.env` or `phpunit.xml`

If Redis is not available, some tests will skip Redis operations gracefully.

### Test Data

Tests create sample orders and trigger events to test analytics updates. The test database is refreshed before each test.

## Test Coverage

- ✅ Event-driven KPI updates
- ✅ Leaderboard updates
- ✅ HTTP API endpoints
- ✅ Use case logic
- ✅ Domain entities
- ✅ Value objects
- ✅ Error handling
- ✅ Edge cases

## Notes

- Tests use `RefreshDatabase` trait to ensure clean state
- Redis is flushed before each test (if available)
- Events are used to trigger analytics updates
- Tests verify both successful and failed order scenarios

