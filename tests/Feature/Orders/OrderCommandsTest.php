<?php

namespace Tests\Feature\Orders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Orders\Application\Jobs\ImportOrdersJob;
use Modules\Orders\Domain\Models\Order;
use Modules\Orders\Infrastructure\Queue\Jobs\ProcessOrderWorkflowJob;
use Tests\TestCase;

class OrderCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_import_orders_command_validates_file_exists()
    {
        $this->artisan('orders:import', ['file' => 'nonexistent.csv'])
            ->expectsOutput('File not found: nonexistent.csv')
            ->assertExitCode(1);
    }

    public function test_import_orders_command_validates_csv_extension()
    {
        $filePath = storage_path('app/test.txt');
        file_put_contents($filePath, 'test');

        $this->artisan('orders:import', ['file' => $filePath])
            ->expectsOutput('File must be a CSV file. Got: txt')
            ->assertExitCode(1);

        unlink($filePath);
    }

    public function test_import_orders_command_dispatches_job()
    {
        $csvPath = storage_path('app/test_orders.csv');
        $this->createTestCsv($csvPath);

        $this->artisan('orders:import', [
            'file' => $csvPath,
            '--chunk' => 50,
        ])
            ->expectsOutputToContain('Starting import of orders from')
            ->assertExitCode(0);

        Queue::assertPushed(ImportOrdersJob::class);

        unlink($csvPath);
    }

    public function test_process_order_command_dispatches_workflow_job()
    {
        $order = Order::create([
            'customer_id' => 1001,
            'order_number' => 'ORD-TEST',
            'status' => 'pending',
            'total_amount' => 99.99,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => ['name' => 'Test', 'email' => 'test@example.com'],
        ]);

        $this->artisan('orders:process', ['order_id' => $order->id])
            ->expectsOutput("Dispatching order processing workflow for order: {$order->id}")
            ->assertExitCode(0);

        Queue::assertPushed(ProcessOrderWorkflowJob::class);
    }

    public function test_process_order_command_handles_invalid_order()
    {
        $this->artisan('orders:process', ['order_id' => 99999])
            ->assertExitCode(0); // Command succeeds, but job will fail
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
}

