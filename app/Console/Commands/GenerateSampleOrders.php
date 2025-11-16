<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateSampleOrders extends Command
{
    protected $signature = 'orders:generate-sample {count=100} {filename=sample_orders.csv}';
    protected $description = 'Generate a sample CSV file for orders import';

    protected $products = [
        ['sku' => 'PROD-001', 'name' => 'Laptop', 'price' => 150.50],
        ['sku' => 'PROD-002', 'name' => 'Smartphone', 'price' => 299.99],
        ['sku' => 'PROD-003', 'name' => 'Headphones', 'price' => 37.63],
        ['sku' => 'PROD-004', 'name' => 'Tablet', 'price' => 450.00],
        ['sku' => 'PROD-005', 'name' => 'Keyboard', 'price' => 89.99],
        ['sku' => 'PROD-006', 'name' => 'Mouse', 'price' => 41.92],
        ['sku' => 'PROD-007', 'name' => 'Monitor', 'price' => 199.99],
        ['sku' => 'PROD-008', 'name' => 'Webcam', 'price' => 65.50],
        ['sku' => 'PROD-009', 'name' => 'Speaker', 'price' => 87.63],
        ['sku' => 'PROD-010', 'name' => 'Microphone', 'price' => 120.00],
    ];

    protected $customers = [
        ['id' => 1001, 'email' => 'john.doe@example.com'],
        ['id' => 1002, 'email' => 'jane.smith@example.com'],
        ['id' => 1003, 'email' => 'mike.wilson@example.com'],
        ['id' => 1004, 'email' => 'sarah.jones@example.com'],
        ['id' => 1005, 'email' => 'adam.brown@example.com'],
        ['id' => 1006, 'email' => 'lisa.taylor@example.com'],
        ['id' => 1007, 'email' => 'tom.harris@example.com'],
        ['id' => 1008, 'email' => 'emily.white@example.com'],
        ['id' => 1009, 'email' => 'david.miller@example.com'],
        ['id' => 1010, 'email' => 'susan.davis@example.com'],
    ];

    public function handle()
    {
        $count = $this->argument('count');
        $filename = $this->argument('filename');

        $this->info("Generating {$count} sample orders...");

        // Create CSV header
        $csvData = [
            ['order_id', 'customer_id', 'customer_email', 'total_amount', 'tax_amount', 'currency', 'status', 'items']
        ];

        // Generate sample orders
        for ($i = 1; $i <= $count; $i++) {
            $customer = $this->customers[array_rand($this->customers)];
            $productCount = rand(1, 3);
            $items = [];
            $totalAmount = 0;

            for ($j = 0; $j < $productCount; $j++) {
                $product = $this->products[array_rand($this->products)];
                $quantity = rand(1, 2);
                $itemTotal = $product['price'] * $quantity;
                $totalAmount += $itemTotal;

                $items[] = [
                    'sku' => $product['sku'],
                    'name' => $product['name'],
                    'quantity' => $quantity,
                    'unit_price' => $product['price']
                ];
            }

            $taxAmount = round($totalAmount * 0.1, 2); // 10% tax
            $totalAmountWithTax = $totalAmount + $taxAmount;

            $csvData[] = [
                'ORD-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                $customer['id'],
                $customer['email'],
                number_format($totalAmountWithTax, 2, '.', ''),
                number_format($taxAmount, 2, '.', ''),
                'USD',
                'pending',
                json_encode($items)
            ];
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

        return 0;
    }
}
