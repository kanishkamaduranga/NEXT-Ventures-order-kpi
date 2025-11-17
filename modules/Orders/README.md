# Orders Module

A comprehensive Laravel module for managing orders with CSV import functionality and an event-driven workflow system for processing orders through stock reservation, payment processing, and finalization.

## Overview

This module provides a complete order management system that handles:
- **CSV Import**: Bulk import of orders from CSV files using queued jobs
- **Order Workflow**: Automated processing pipeline (reserve stock → payment → finalize/rollback)
- **Event-Driven Architecture**: Decoupled workflow using Laravel events
- **Database Management**: Integer-based IDs with comprehensive order tracking

## Features

### 1. CSV Order Import
- Import large CSV files with chunked processing
- Queue-based background processing
- Duplicate detection (skips existing orders)
- Error handling and logging
- Support for multiple items per order

### 2. Order Processing Workflow
- **Step 1: Stock Reservation** - Reserves inventory for order items
- **Step 2: Payment Processing** - Simulates payment with callback
- **Step 3: Finalization/Rollback** - Completes order or rolls back on failure

### 3. Order Status Management
Comprehensive status tracking:
- `pending` - Initial state
- `reserving_stock` - Stock reservation in progress
- `stock_reserved` - Stock successfully reserved
- `stock_reservation_failed` - Stock reservation failed
- `processing_payment` - Payment processing in progress
- `payment_succeeded` - Payment successful
- `payment_failed` - Payment failed
- `completed` - Order successfully completed
- `cancelled` - Order cancelled (with stock released)

## Folder Structure

```
modules/Orders/
├── Application/                    # Application layer (use cases, commands, jobs)
│   ├── Commands/
│   │   ├── ImportOrdersCommand.php        # Artisan command: orders:import
│   │   └── ProcessOrderCommand.php        # Artisan command: orders:process
│   ├── Jobs/
│   │   └── ImportOrdersJob.php            # Queued job for CSV import processing
│   ├── Services/
│   │   ├── OrderWorkflowCoordinator.php   # Orchestrates order workflow
│   │   ├── SimulatedPaymentGateway.php    # Payment gateway simulation
│   │   └── StockReservationService.php    # Stock reservation service
│   └── UseCases/
│       ├── FinalizeOrderUseCase.php       # Finalizes successful orders
│       ├── ReserveStockUseCase.php        # Reserves stock for orders
│       ├── RollbackOrderUseCase.php       # Rolls back failed orders
│       └── SimulatePaymentUseCase.php     # Processes payments
├── Domain/                         # Domain layer (business logic)
│   ├── Events/                     # Domain events
│   │   ├── OrderCompleted.php
│   │   ├── OrderFailed.php
│   │   ├── OrderProcessStarted.php
│   │   ├── PaymentProcessed.php
│   │   ├── StockReserved.php
│   │   └── StockReservationFailed.php
│   ├── Models/                     # Eloquent models
│   │   ├── Order.php               # Order model
│   │   └── OrderItem.php           # Order item model
│   ├── Repositories/               # Repository interfaces
│   │   └── OrderRepositoryInterface.php
│   ├── Services/                   # Service interfaces
│   │   ├── PaymentGatewayInterface.php
│   │   └── StockServiceInterface.php
│   └── ValueObjects/
│       └── OrderStatus.php         # Order status enum with transitions
├── Infrastructure/                 # Infrastructure layer (implementations)
│   ├── Persistence/
│   │   ├── Migrations/
│   │   │   ├── 2025_11_16_185901_create_orders_table.php
│   │   │   └── 2025_11_16_185951_create_order_items_table.php
│   │   └── Repositories/
│   │       └── OrderRepository.php # Repository implementation
│   ├── Queue/
│   │   ├── Jobs/
│   │   │   └── ProcessOrderWorkflowJob.php # Workflow job
│   │   └── Listeners/
│   │       └── OrderEventSubscriber.php   # Event subscriber
│   └── Services/
│       ├── SimulatedPaymentGateway.php    # Payment gateway implementation
│       └── StockReservationService.php     # Stock service implementation
└── Providers/
    └── OrdersServiceProvider.php   # Service provider (DI, events, commands)
```

## Architecture

This module follows **Domain-Driven Design (DDD)** principles with clear separation of concerns:

### Layers

1. **Domain Layer** (`Domain/`)
   - Contains business logic, entities, and domain events
   - No dependencies on framework or infrastructure
   - Defines interfaces for services and repositories

2. **Application Layer** (`Application/`)
   - Contains use cases, commands, and application services
   - Orchestrates domain objects
   - Handles workflow coordination

3. **Infrastructure Layer** (`Infrastructure/`)
   - Contains implementations of domain interfaces
   - Database migrations and repositories
   - External service integrations

### Event-Driven Workflow

The order processing workflow uses Laravel events for decoupled communication:

```
Order Created (pending)
    ↓
[ProcessOrderWorkflowJob] dispatches
    ↓
[ReserveStockUseCase] executes
    ↓
StockReserved event fired
    ↓
[OrderEventSubscriber] listens
    ↓
[SimulatePaymentUseCase] executes
    ↓
PaymentProcessed event fired
    ↓
[OrderEventSubscriber] listens
    ↓
    ├─ Success → [FinalizeOrderUseCase] → OrderCompleted event
    └─ Failure → [RollbackOrderUseCase] → OrderFailed event
```

## Database Schema

### Orders Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint unsigned | Primary key (auto-increment) |
| `customer_id` | bigint unsigned | Customer identifier |
| `order_number` | string | Unique order number |
| `status` | enum | Current order status |
| `total_amount` | decimal(10,2) | Total order amount |
| `currency` | varchar(3) | Currency code (e.g., USD) |
| `items` | json | Order items details |
| `customer_details` | json | Customer name and email |
| `reserved_at` | timestamp | When stock was reserved |
| `paid_at` | timestamp | When payment was completed |
| `failed_at` | timestamp | When order failed |
| `failure_reason` | text | Reason for failure |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |
| `deleted_at` | timestamp | Soft delete timestamp |

### Order Items Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint unsigned | Primary key (auto-increment) |
| `order_id` | bigint unsigned | Foreign key to orders |
| `product_id` | bigint unsigned | Product identifier |
| `product_name` | string | Product name |
| `sku` | string | Product SKU |
| `quantity` | integer | Item quantity |
| `unit_price` | decimal(10,2) | Price per unit |
| `total_price` | decimal(10,2) | Total price (quantity × unit_price) |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |

## Usage

### 1. Generate Sample CSV

```bash
# Generate 100 sample orders (default)
php artisan orders:generate-sample

# Generate custom number of orders
php artisan orders:generate-sample 1000

# Specify filename
php artisan orders:generate-sample 500 orders.csv
```

**CSV Format:**
```csv
order_number,customer_id,customer_name,customer_email,status,total_amount,currency,item1_product_id,item1_product_name,item1_sku,item1_quantity,item1_unit_price,...
ORD-000001,1001,John Doe,john@example.com,pending,299.99,USD,1,Laptop,PROD-001,2,150.50,...
```

### 2. Import Orders from CSV

```bash
# Import with default chunk size (100 rows per job)
php artisan orders:import storage/app/orders.csv

# Import with custom chunk size
php artisan orders:import storage/app/orders.csv --chunk=50

# Skip existing orders (default behavior)
php artisan orders:import storage/app/orders.csv --skip-existing

# Force import (attempts to import all, but existing orders still skipped)
php artisan orders:import storage/app/orders.csv --force
```

**Note:** Make sure the queue worker is running:
```bash
php artisan queue:work --queue=orders-import
```

### 3. Process Order Workflow

```bash
# Process a single order through the workflow
php artisan orders:process {order_id}
```

**Note:** Make sure the queue worker is running:
```bash
php artisan queue:work --queue=order-processing
```

## Workflow Details

### Stock Reservation

- Simulates inventory reservation for each product in the order
- 10% failure rate for testing purposes
- Updates order status to `stock_reserved` on success
- Triggers `StockReserved` event on success
- Triggers `StockReservationFailed` event on failure

### Payment Processing

- Simulates payment gateway processing
- 15% failure rate for testing purposes
- Updates order status to `payment_succeeded` or `payment_failed`
- Triggers `PaymentProcessed` event with success/failure status

### Finalization

- Marks order as `completed` on successful payment
- Triggers `OrderCompleted` event
- Order is ready for fulfillment

### Rollback

- Releases reserved stock if order fails
- Updates order status to `cancelled`
- Records failure reason
- Triggers `OrderFailed` event

## Configuration

### Service Provider

The `OrdersServiceProvider` registers:
- **Repositories**: `OrderRepositoryInterface` → `OrderRepository`
- **Services**: 
  - `StockServiceInterface` → `StockReservationService`
  - `PaymentGatewayInterface` → `SimulatedPaymentGateway`
- **Commands**: `orders:import`, `orders:process`
- **Event Subscribers**: `OrderEventSubscriber`

### Queue Configuration

The module uses two queues:
- `orders-import` - For CSV import jobs
- `order-processing` - For order workflow processing

Make sure your `.env` has:
```env
QUEUE_CONNECTION=database
```

## Testing

### Simulated Services

Both services include failure simulation for testing:

1. **StockReservationService**
   - 10% random failure rate
   - Throws exception: "Insufficient stock for product: {product_id}"

2. **SimulatedPaymentGateway**
   - 15% random failure rate
   - Returns failure reason: "Payment declined by bank"

### Example Test Scenarios

1. **Successful Order Flow:**
   ```
   pending → reserving_stock → stock_reserved → processing_payment → payment_succeeded → completed
   ```

2. **Stock Reservation Failure:**
   ```
   pending → reserving_stock → stock_reservation_failed → cancelled
   ```

3. **Payment Failure:**
   ```
   pending → reserving_stock → stock_reserved → processing_payment → payment_failed → cancelled (stock released)
   ```

## Extending the Module

### Adding a Real Payment Gateway

1. Implement `PaymentGatewayInterface`:
```php
class RealPaymentGateway implements PaymentGatewayInterface
{
    public function processPayment(array $paymentData): object
    {
        // Your payment gateway integration
    }
}
```

2. Update service provider:
```php
$this->app->bind(
    PaymentGatewayInterface::class, 
    RealPaymentGateway::class
);
```

### Adding Real Stock Management

1. Implement `StockServiceInterface`:
```php
class InventoryService implements StockServiceInterface
{
    public function reserveStock(string $productId, int $quantity, string $orderId): void
    {
        // Your inventory system integration
    }
}
```

2. Update service provider:
```php
$this->app->bind(
    StockServiceInterface::class, 
    InventoryService::class
);
```

## Commands Reference

| Command | Description |
|---------|-------------|
| `orders:generate-sample [count] [filename]` | Generate sample CSV file |
| `orders:import {file} [--chunk=100] [--skip-existing] [--force]` | Import orders from CSV |
| `orders:process {order_id}` | Process order through workflow |

## Events Reference

| Event | Description | Triggered By |
|-------|-------------|--------------|
| `OrderProcessStarted` | Order processing initiated | Manual trigger |
| `StockReserved` | Stock successfully reserved | `ReserveStockUseCase` |
| `StockReservationFailed` | Stock reservation failed | `ReserveStockUseCase` |
| `PaymentProcessed` | Payment processing completed | `SimulatePaymentUseCase` |
| `OrderCompleted` | Order successfully completed | `FinalizeOrderUseCase` |
| `OrderFailed` | Order failed and rolled back | `RollbackOrderUseCase` |



