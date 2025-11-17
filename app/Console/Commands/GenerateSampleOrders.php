<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateSampleOrders extends Command
{
    protected $signature = 'orders:generate-sample {count=100} {filename=sample_orders.csv}';
    protected $description = 'Generate a sample CSV file for orders import';

    protected $products = [
        ['id' => 1, 'sku' => 'PROD-001', 'name' => 'Laptop', 'price' => 150.50],
        ['id' => 2, 'sku' => 'PROD-002', 'name' => 'Smartphone', 'price' => 299.99],
        ['id' => 3, 'sku' => 'PROD-003', 'name' => 'Headphones', 'price' => 37.63],
        ['id' => 4, 'sku' => 'PROD-004', 'name' => 'Tablet', 'price' => 450.00],
        ['id' => 5, 'sku' => 'PROD-005', 'name' => 'Keyboard', 'price' => 89.99],
        ['id' => 6, 'sku' => 'PROD-006', 'name' => 'Mouse', 'price' => 41.92],
        ['id' => 7, 'sku' => 'PROD-007', 'name' => 'Monitor', 'price' => 199.99],
        ['id' => 8, 'sku' => 'PROD-008', 'name' => 'Webcam', 'price' => 65.50],
        ['id' => 9, 'sku' => 'PROD-009', 'name' => 'Speaker', 'price' => 87.63],
        ['id' => 10, 'sku' => 'PROD-010', 'name' => 'Microphone', 'price' => 120.00],
    ];

    protected $customers = [
        ['id' => 1001, 'name' => 'John Doe', 'email' => 'john.doe@example.com'],
        ['id' => 1002, 'name' => 'Jane Smith', 'email' => 'jane.smith@example.com'],
        ['id' => 1003, 'name' => 'Mike Wilson', 'email' => 'mike.wilson@example.com'],
        ['id' => 1004, 'name' => 'Sarah Jones', 'email' => 'sarah.jones@example.com'],
        ['id' => 1005, 'name' => 'Adam Brown', 'email' => 'adam.brown@example.com'],
        ['id' => 1006, 'name' => 'Lisa Taylor', 'email' => 'lisa.taylor@example.com'],
        ['id' => 1007, 'name' => 'Tom Harris', 'email' => 'tom.harris@example.com'],
        ['id' => 1008, 'name' => 'Emily White', 'email' => 'emily.white@example.com'],
        ['id' => 1009, 'name' => 'David Miller', 'email' => 'david.miller@example.com'],
        ['id' => 1010, 'name' => 'Susan Davis', 'email' => 'susan.davis@example.com'],
    ];

    protected $statuses = ['pending', 'reserved', 'paid', 'failed', 'refunded', 'partially_refunded'];

    public function handle()
    {
        $count = (int) $this->argument('count');
        $filename = $this->argument('filename');

        $this->info("Generating {$count} sample orders...");

        // Build CSV header
        // Format: order_number, customer_id, customer_name, customer_email, status, total_amount, currency,
        //         item1_product_id, item1_product_name, item1_sku, item1_quantity, item1_unit_price, ...
        $header = [
            'order_number',
            'customer_id',
            'customer_name',
            'customer_email',
            'status',
            'total_amount',
            'currency',
        ];

        // Add item columns (support up to 5 items per order)
        for ($i = 1; $i <= 5; $i++) {
            $header[] = "item{$i}_product_id";
            $header[] = "item{$i}_product_name";
            $header[] = "item{$i}_sku";
            $header[] = "item{$i}_quantity";
            $header[] = "item{$i}_unit_price";
        }

        $csvData = [$header];

        // Generate sample orders
        for ($i = 1; $i <= $count; $i++) {
            $customer = $this->customers[array_rand($this->customers)];
            $productCount = rand(1, 3);
            $items = [];
            $totalAmount = 0;

            // Generate items
            for ($j = 0; $j < $productCount; $j++) {
                $product = $this->products[array_rand($this->products)];
                $quantity = rand(1, 3);
                $itemTotal = $product['price'] * $quantity;
                $totalAmount += $itemTotal;

                $items[] = [
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'sku' => $product['sku'],
                    'quantity' => $quantity,
                    'unit_price' => $product['price'],
                ];
            }

            // Build CSV row
            $row = [
                'ORD-' . str_pad($i, 6, '0', STR_PAD_LEFT), // order_number
                $customer['id'], // customer_id (UUID)
                $customer['name'], // customer_name
                $customer['email'], // customer_email
                $this->statuses[array_rand($this->statuses)], // status
                number_format($totalAmount, 2, '.', ''), // total_amount
                'USD', // currency
            ];

            // Add items as columns
            for ($j = 0; $j < 5; $j++) {
                if (isset($items[$j])) {
                    $item = $items[$j];
                    $row[] = $item['product_id'];
                    $row[] = $item['product_name'];
                    $row[] = $item['sku'];
                    $row[] = $item['quantity'];
                    $row[] = number_format($item['unit_price'], 2, '.', '');
                } else {
                    // Empty columns for unused item slots
                    $row[] = '';
                    $row[] = '';
                    $row[] = '';
                    $row[] = '';
                    $row[] = '';
                }
            }

            $csvData[] = $row;
        }

        // Write to CSV file
        $filePath = storage_path('app/' . $filename);
        $handle = fopen($filePath, 'w');

        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        $this->info("Sample CSV generated: {$filePath}");
        $this->info("You can now run: php artisan orders:import {$filePath}");

        return Command::SUCCESS;
    }
}
