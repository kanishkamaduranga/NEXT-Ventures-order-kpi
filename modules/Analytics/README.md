# Analytics Module

A comprehensive analytics module that generates daily KPIs (Key Performance Indicators) and maintains a real-time leaderboard of top customers using Redis for high-performance data storage and retrieval.

## Overview

This module provides:
- **Daily KPIs**: Revenue, order count, average order value, conversion rate, and unique customers
- **Customer Leaderboard**: Real-time ranking of top customers by spending
- **Redis-Based Storage**: Fast, scalable analytics using Redis data structures
- **Event-Driven Updates**: Automatic KPI updates when orders are completed
- **Historical Data**: Track KPIs for date ranges with 30-day retention

## Features

### Daily KPIs
- **Total Revenue**: Sum of all successful order amounts
- **Order Count**: Total number of orders (successful + failed)
- **Successful Orders**: Count of completed orders
- **Failed Orders**: Count of failed/cancelled orders
- **Average Order Value**: Revenue divided by successful orders
- **Conversion Rate**: Percentage of successful orders
- **Unique Customers**: Number of distinct customers who placed orders
- **Refund Amount**: Total amount refunded

### Customer Leaderboard
- Real-time ranking by total spending per day
- Redis Sorted Sets for efficient ranking
- Get top N customers
- Get customer rank
- Handle refunds (decrement spending)

## Architecture

### Domain-Driven Design

The module follows DDD principles with clear separation:

```
Analytics/
├── Application/              # Application layer
│   ├── UseCases/
│   │   ├── GenerateDailyReportUseCase.php
│   │   ├── UpdateDailyKpisUseCase.php
│   │   └── UpdateLeaderboardUseCase.php
│   └── DTOs/
│       ├── DailyReportDto.php
│       └── KpiUpdateDto.php
├── Domain/                  # Domain layer
│   ├── Entities/
│   │   ├── DailyKpi.php
│   │   └── CustomerLeaderboard.php
│   ├── Repositories/
│   │   ├── AnalyticsRepositoryInterface.php
│   │   └── LeaderboardRepositoryInterface.php
│   └── ValueObjects/
│       ├── KpiDate.php
│       └── Revenue.php
├── Infrastructure/          # Infrastructure layer
│   ├── Repositories/
│   │   ├── RedisAnalyticsRepository.php
│   │   └── RedisLeaderboardRepository.php
│   └── Listeners/
│       └── AnalyticsEventSubscriber.php
└── Interfaces/
    └── Console/
        └── Commands/
            ├── GenerateDailyReportCommand.php
            └── BackfillKpisCommand.php
```

## Redis Data Structures

### Daily KPIs (Hash)
**Key Pattern**: `kpi:daily:YYYY-MM-DD`

```redis
HGETALL kpi:daily:2025-11-17
{
  "total_revenue": "15234.50",
  "order_count": "125",
  "successful_orders": "118",
  "failed_orders": "7",
  "average_order_value": "129.11",
  "unique_customers": "95",
  "refund_amount": "0.00",
  "updated_at": "2025-11-17T10:30:00Z"
}
```

**Expiration**: 30 days

### Customer Leaderboard (Sorted Set)
**Key Pattern**: `leaderboard:daily:YYYY-MM-DD`

```redis
ZREVRANGE leaderboard:daily:2025-11-17 0 9 WITHSCORES
1) "1005"    # Customer ID
2) "1523.45" # Total spent
3) "1002"
4) "1245.67"
...
```

**Expiration**: 30 days

### Unique Customers (Set)
**Key Pattern**: `kpi:customers:daily:YYYY-MM-DD`

Used to track unique customers per day efficiently.

## Usage

### 1. Generate Daily Report

```bash
# Generate report for yesterday (default)
php artisan analytics:daily-report

# Generate report for specific date
php artisan analytics:daily-report 2025-11-17

# Export to JSON
php artisan analytics:daily-report 2025-11-17 --export=json

# Export to CSV
php artisan analytics:daily-report 2025-11-17 --export=csv
```

**Output Example:**
```
=== DAILY ANALYTICS REPORT ===
Date: 2025-11-17
Generated: 2025-11-17T10:30:00Z

--- KEY PERFORMANCE INDICATORS ---
+------------------+------------+
| Metric            | Value      |
+------------------+------------+
| Total Revenue     | $15,234.50 |
| Order Count       | 125        |
| Successful Orders | 118        |
| Failed Orders     | 7          |
| Conversion Rate   | 94.40%     |
| Average Order Value| $129.11    |
| Unique Customers  | 95         |
| Refund Amount     | $0.00      |
+------------------+------------+

--- TOP CUSTOMERS LEADERBOARD ---
+------+-------------+-------------+
| Rank | Customer ID | Total Spent |
+------+-------------+-------------+
| 1    | 1005        | $1,523.45   |
| 2    | 1002        | $1,245.67   |
| 3    | 1009        | $987.23      |
...
+------+-------------+-------------+
```

### 2. Backfill KPIs from Existing Orders

```bash
# Backfill last 30 days (default)
php artisan analytics:backfill-kpis

# Backfill specific date range
php artisan analytics:backfill-kpis --from=2025-11-01 --to=2025-11-17

# Force recalculation (overwrite existing)
php artisan analytics:backfill-kpis --force
```

## Event-Driven Updates

The module automatically updates KPIs and leaderboard when orders are completed:

### OrderCompleted Event
- Increments revenue
- Increments order count
- Updates successful orders
- Tracks unique customer
- Updates customer leaderboard
- Recalculates average order value

### OrderFailed Event
- Increments order count
- Updates failed orders
- Does not affect revenue or leaderboard

## HTTP API Endpoints

The Analytics module exposes HTTP endpoints for retrieving analytics data.

### Base URL
All endpoints are prefixed with `/api/v1/analytics`

### Get Daily Report
```
GET /api/v1/analytics/daily/{date}
```

Returns daily KPIs and leaderboard for a specific date.

**Parameters:**
- `date` (path): Date in YYYY-MM-DD format

**Example:**
```bash
curl http://localhost:8088/api/v1/analytics/daily/2025-11-16
```

**Response:**
```json
{
  "success": true,
  "data": {
    "kpis": {
      "date": "2025-11-16",
      "total_revenue": 15234.50,
      "order_count": 125,
      "successful_orders": 118,
      "failed_orders": 7,
      "average_order_value": 129.11,
      "conversion_rate": 94.40,
      "unique_customers": 95,
      "refund_amount": 0.00
    },
    "leaderboard": [
      {
        "rank": 1,
        "customer_id": "1005",
        "total_spent": 1523.45
      },
      {
        "rank": 2,
        "customer_id": "1002",
        "total_spent": 1245.67
      }
    ],
    "date": "2025-11-16",
    "generated_at": "2025-11-17T09:40:27.932567Z"
  }
}
```

**Error Response:**
```json
{
  "error": "Invalid date format. Expected YYYY-MM-DD"
}
```
Status: 400 Bad Request

### Get Leaderboard
```
GET /api/v1/analytics/leaderboard/{date}?limit=10
```

Returns top customers leaderboard for a specific date.

**Parameters:**
- `date` (path): Date in YYYY-MM-DD format
- `limit` (query, optional): Number of top customers to return (default: 10, max: 100)

**Example:**
```bash
curl http://localhost:8088/api/v1/analytics/leaderboard/2025-11-16?limit=20
```

**Response:**
```json
{
  "success": true,
  "data": {
    "date": "2025-11-16",
    "limit": 20,
    "leaderboard": [
      {
        "rank": 1,
        "customer_id": "1005",
        "total_spent": 1523.45
      },
      {
        "rank": 2,
        "customer_id": "1002",
        "total_spent": 1245.67
      }
    ]
  }
}
```

### Get KPIs for Date Range
```
GET /api/v1/analytics/kpis?start_date=2025-11-01&end_date=2025-11-17
```

Returns aggregated KPIs for a date range.

**Parameters:**
- `start_date` (query, required): Start date in YYYY-MM-DD format
- `end_date` (query, optional): End date in YYYY-MM-DD format (default: today)

**Example:**
```bash
curl "http://localhost:8088/api/v1/analytics/kpis?start_date=2025-11-01&end_date=2025-11-17"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "start_date": "2025-11-01",
    "end_date": "2025-11-17",
    "total_revenue": 125430.50,
    "total_order_count": 1250,
    "total_successful_orders": 1180,
    "total_failed_orders": 70,
    "average_order_value": 106.30,
    "conversion_rate": 94.40,
    "total_unique_customers": 450,
    "daily_breakdown": [
      {
        "date": "2025-11-01",
        "total_revenue": 15234.50,
        "order_count": 125
      }
    ]
  }
}
```

## Programmatic API Usage

### Get Daily KPIs

```php
use Modules\Analytics\Application\UseCases\GenerateDailyReportUseCase;

$useCase = app(GenerateDailyReportUseCase::class);
$report = $useCase->execute('2025-11-17');

// Returns:
[
    'kpis' => [
        'date' => '2025-11-17',
        'total_revenue' => 15234.50,
        'order_count' => 125,
        'average_order_value' => 129.11,
        'successful_orders' => 118,
        'failed_orders' => 7,
        'conversion_rate' => 94.40,
        'unique_customers' => 95,
        'refund_amount' => 0.00,
    ],
    'leaderboard' => [
        '1005' => 1523.45,
        '1002' => 1245.67,
        // ...
    ],
    'date' => '2025-11-17',
    'generated_at' => '2025-11-17T10:30:00Z'
]
```

### Get Top Customers

```php
use Modules\Analytics\Application\UseCases\UpdateLeaderboardUseCase;

$useCase = app(UpdateLeaderboardUseCase::class);
$topCustomers = $useCase->getTopCustomers('2025-11-17', 10);

// Returns: ['customer_id' => amount, ...]
```

### Get Customer Rank

```php
$rank = $useCase->getCustomerRank('1005', '2025-11-17');
// Returns: 1 (if customer is #1)
```

## Redis Configuration

Make sure Redis is configured in your `.env`:

```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
```

For Docker/Sail, Redis should be available at `redis:6379` from within containers.

## Performance Considerations

### Redis Operations
- **KPIs**: Stored as Redis Hashes for efficient field updates
- **Leaderboard**: Uses Sorted Sets (ZSET) for O(log N) ranking operations
- **Unique Customers**: Uses Sets for O(1) membership checks
- **Pipelining**: Multiple operations batched for performance
- **Lua Scripts**: Atomic operations for complex updates

### Data Retention
- KPIs expire after 30 days automatically
- Leaderboard data expires after 30 days
- Historical data can be backfilled from database

## Integration with Orders Module

The Analytics module listens to events from the Orders module:

1. **OrderCompleted** → Updates KPIs and leaderboard
2. **OrderFailed** → Updates failed order count

No direct coupling - uses Laravel's event system.

## Commands Reference

| Command | Description |
|---------|-------------|
| `analytics:daily-report [date] [--export=json\|csv]` | Generate daily KPIs and leaderboard |
| `analytics:backfill-kpis [--from=DATE] [--to=DATE] [--force]` | Backfill KPIs from existing orders |

## Redis Key Patterns

| Pattern | Type | Description |
|---------|------|-------------|
| `kpi:daily:YYYY-MM-DD` | Hash | Daily KPI metrics |
| `leaderboard:daily:YYYY-MM-DD` | Sorted Set | Customer leaderboard |
| `kpi:customers:daily:YYYY-MM-DD` | Set | Unique customers per day |

## Extending the Module

### Adding New KPIs

1. Update `DailyKpi` entity to include new field
2. Update `RedisAnalyticsRepository` to store/retrieve new field
3. Update `GenerateDailyReportCommand` to display new metric

### Adding Weekly/Monthly KPIs

1. Create new repository methods for weekly/monthly keys
2. Add aggregation logic
3. Create new commands for weekly/monthly reports

### Custom Leaderboard Periods

Extend `LeaderboardRepositoryInterface` to support:
- Weekly leaderboards
- Monthly leaderboards
- All-time leaderboards

## Troubleshooting

### Redis Connection Issues

```bash
# Test Redis connection
php artisan tinker
>>> Redis::ping()
# Should return: "PONG"
```

### Missing KPIs

If KPIs are missing for a date:
```bash
# Backfill from database
php artisan analytics:backfill-kpis --from=YYYY-MM-DD --to=YYYY-MM-DD
```

### Clear Redis Data

```bash
# Clear all analytics data (use with caution)
redis-cli FLUSHDB
```

## Dependencies

- Redis 6.0+ (or compatible)
- Orders Module (for events)

## Notes

- KPIs are calculated in real-time as orders are completed
- Leaderboard uses Redis Sorted Sets for efficient ranking
- All data expires after 30 days (configurable)
- Backfill command can regenerate KPIs from database
- Module is event-driven and decoupled from Orders module

