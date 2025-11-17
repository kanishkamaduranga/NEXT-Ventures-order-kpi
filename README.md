# NEXT Ventures Order KPI System

A Laravel-based order management and analytics system with real-time KPI tracking and customer leaderboards.

## Overview

This application provides a comprehensive order processing system with:
- **Order Management**: Import, process, and track orders through a complete workflow
- **Real-time Analytics**: Daily KPIs (revenue, order count, AOV) and customer leaderboards
- **Event-Driven Architecture**: Decoupled modules using Laravel events
- **Domain-Driven Design**: Clean architecture with clear separation of concerns
- **Redis-Powered Analytics**: High-performance analytics using Redis data structures

## Features

### Orders Module
- CSV import of large order files using queued jobs
- Order processing workflow: stock reservation → payment → finalization/rollback
- Event-driven state management
- Simulated payment gateway and stock reservation services
- Support for multiple order statuses and failure handling

### Analytics Module
- Daily KPIs: revenue, order count, average order value, conversion rate, unique customers
- Real-time customer leaderboard
- Redis-based storage for high performance
- Historical data tracking (30-day retention)
- HTTP API endpoints for data retrieval

## Tech Stack

- **Framework**: Laravel 12.x
- **PHP**: 8.2+
- **Database**: MySQL 8.0
- **Cache/Queue**: Redis
- **Containerization**: Docker & Laravel Sail

## Getting Started

### Prerequisites

- Docker & Docker Compose
- PHP 8.2+ (if running locally)
- Composer

### Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd NEXT-Ventures-order-kpi
```

2. Install dependencies:
```bash
composer install
```

3. Copy environment file:
```bash
cp .env.example .env
```

4. Start Docker containers:
```bash
./vendor/bin/sail up -d
```

5. Generate application key:
```bash
./vendor/bin/sail artisan key:generate
```

6. Run migrations:
```bash
./vendor/bin/sail artisan migrate
```

### Services

- **Laravel Application**: http://localhost:8088
- **MySQL**: localhost:3308
- **Redis**: localhost:6379
- **Adminer** (Database UI): http://localhost:8089

## Usage

### Import Orders

```bash
# Generate sample orders CSV
php artisan orders:generate-sample

# Import orders from CSV (queued)
php artisan orders:import storage/app/orders.csv
```

### Process Orders

```bash
# Process a specific order through the workflow
php artisan orders:process {order_id}
```

### Analytics

```bash
# Generate daily analytics report
php artisan analytics:daily-report [date]

# Backfill KPIs from existing orders
php artisan analytics:backfill-kpis --days=30
```

## API Endpoints

### Analytics API

All endpoints return JSON responses and are prefixed with `/api/v1`.

#### Get Daily Report
```
GET /api/v1/analytics/daily/{date}
```

Returns daily KPIs and leaderboard for a specific date.

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
      }
    ],
    "date": "2025-11-16",
    "generated_at": "2025-11-17T09:40:27.932567Z"
  }
}
```

#### Get Leaderboard
```
GET /api/v1/analytics/leaderboard/{date}?limit=10
```

Returns top customers leaderboard for a specific date.

**Parameters:**
- `limit` (optional): Number of top customers to return (default: 10, max: 100)

**Example:**
```bash
curl http://localhost:8088/api/v1/analytics/leaderboard/2025-11-16?limit=20
```

#### Get KPIs for Date Range
```
GET /api/v1/analytics/kpis?start_date=2025-11-01&end_date=2025-11-17
```

Returns KPIs aggregated for a date range.

**Parameters:**
- `start_date` (required): Start date (YYYY-MM-DD)
- `end_date` (optional): End date (YYYY-MM-DD, default: today)

**Example:**
```bash
curl "http://localhost:8088/api/v1/analytics/kpis?start_date=2025-11-01&end_date=2025-11-17"
```

## Project Structure

```
├── app/                    # Laravel application core
├── modules/                # Domain modules
│   ├── Orders/            # Orders module
│   │   ├── Application/   # Use cases, jobs, commands
│   │   ├── Domain/        # Models, events, interfaces
│   │   ├── Infrastructure/# Repositories, services, migrations
│   │   └── Interfaces/    # HTTP controllers, console commands
│   └── Analytics/         # Analytics module
│       ├── Application/   # Use cases, DTOs
│       ├── Domain/        # Entities, repositories
│       ├── Infrastructure/# Redis repositories, listeners
│       └── Interfaces/    # HTTP controllers, console commands
├── routes/                # Route definitions
├── database/              # Migrations, seeders
└── compose.yaml           # Docker Compose configuration
```

## Module Documentation

- [Orders Module Documentation](modules/Orders/README.md)
- [Analytics Module Documentation](modules/Analytics/README.md)

## Development

### Running Tests

```bash
./vendor/bin/sail artisan test
```

### Code Style

```bash
./vendor/bin/sail pint
```

### Queue Workers

```bash
./vendor/bin/sail artisan queue:work
```

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
