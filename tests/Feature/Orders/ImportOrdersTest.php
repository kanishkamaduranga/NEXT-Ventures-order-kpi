<?php

namespace Tests\Feature\Orders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Orders\Application\Jobs\ImportOrdersJob;
use Modules\Orders\Domain\Models\Order;
use Modules\Orders\Domain\Models\OrderItem;
use Tests\TestCase;

class ImportOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_it_can_dispatch_import_job()
    {
        $csvPath = storage_path('app/test_orders.csv');
        $this->createTestCsv($csvPath);

        ImportOrdersJob::dispatch($csvPath, 1, 100, true);

        Queue::assertPushed(ImportOrdersJob::class);
    }

    public function test_it_can_import_orders_from_csv()
    {
        Queue::fake();
        $csvPath = storage_path('app/test_orders.csv');
        $this->createTestCsv($csvPath);

        $job = new ImportOrdersJob($csvPath, 1, 100, true);
        $job->handle();

        $this->assertDatabaseHas('orders', [
            'order_number' => 'ORD-001',
            'customer_id' => 1001,
            'total_amount' => 99.99,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => 1,
            'quantity' => 2,
        ]);
    }

    public function test_it_skips_existing_orders_when_skip_existing_is_true()
    {
        Queue::fake();
        
        // Create existing order
        Order::create([
            'order_number' => 'ORD-001',
            'customer_id' => 1001,
            'status' => 'pending',
            'total_amount' => 99.99,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => ['name' => 'Test', 'email' => 'test@example.com'],
        ]);

        $csvPath = storage_path('app/test_orders.csv');
        $this->createTestCsv($csvPath);

        $job = new ImportOrdersJob($csvPath, 1, 100, true);
        $job->handle();

        // Should still have only one order
        $this->assertEquals(1, Order::where('order_number', 'ORD-001')->count());
    }

    public function test_it_processes_chunks_correctly()
    {
        Queue::fake();
        $csvPath = storage_path('app/test_orders_large.csv');
        $this->createLargeTestCsv($csvPath, 150); // Create 150 orders

        $job = new ImportOrdersJob($csvPath, 1, 50, true);
        $job->handle();

        // Should process first 50 and dispatch next chunk
        $this->assertEquals(50, Order::count());
        Queue::assertPushed(ImportOrdersJob::class);
    }

    public function test_it_handles_invalid_csv_rows_gracefully()
    {
        Queue::fake();
        $csvPath = storage_path('app/test_orders_invalid.csv');
        $this->createInvalidCsv($csvPath);

        $job = new ImportOrdersJob($csvPath, 1, 100, true);
        $job->handle();

        // Should not crash, but may have errors logged
        $this->assertTrue(true); // Test passes if no exception thrown
    }

    public function test_it_creates_order_items_correctly()
    {
        Queue::fake();
        $csvPath = storage_path('app/test_orders.csv');
        $this->createTestCsv($csvPath);

        $job = new ImportOrdersJob($csvPath, 1, 100, true);
        $job->handle();

        $order = Order::where('order_number', 'ORD-001')->first();
        $this->assertNotNull($order);

        $items = OrderItem::where('order_id', $order->id)->get();
        $this->assertGreaterThan(0, $items->count());
    }

    protected function createTestCsv(string $path): void
    {
        $csv = [
            ['order_number', 'customer_id', 'customer_name', 'customer_email', 'status', 'total_amount', 'currency', 'item1_product_id', 'item1_product_name', 'item1_sku', 'item1_quantity', 'item1_unit_price'],
            ['ORD-001', '1001', 'John Doe', 'john@example.com', 'pending', '99.99', 'USD', '1', 'Product 1', 'SKU-001', '2', '49.99'],
        ];

        $handle = fopen($path, 'w');
        foreach ($csv as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
    }

    protected function createLargeTestCsv(string $path, int $count): void
    {
        $handle = fopen($path, 'w');
        
        // Header
        fputcsv($handle, ['order_number', 'customer_id', 'customer_name', 'customer_email', 'status', 'total_amount', 'currency', 'item1_product_id', 'item1_product_name', 'item1_sku', 'item1_quantity', 'item1_unit_price']);
        
        // Data rows
        for ($i = 1; $i <= $count; $i++) {
            fputcsv($handle, [
                "ORD-{$i}",
                (1000 + $i),
                "Customer {$i}",
                "customer{$i}@example.com",
                'pending',
                (10.00 * $i),
                'USD',
                $i,
                "Product {$i}",
                "SKU-{$i}",
                1,
                (10.00 * $i),
            ]);
        }
        
        fclose($handle);
    }

    protected function createInvalidCsv(string $path): void
    {
        $csv = [
            ['order_number', 'customer_id', 'customer_name', 'customer_email', 'status', 'total_amount', 'currency'],
            ['ORD-001', '1001', 'John Doe', 'john@example.com', 'pending', '99.99', 'USD'], // Valid
            ['', '', '', '', '', '', ''], // Empty row
            ['ORD-002'], // Incomplete row
        ];

        $handle = fopen($path, 'w');
        foreach ($csv as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
    }
}

