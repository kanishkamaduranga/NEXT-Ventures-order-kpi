<?php

namespace Modules\Orders\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Orders\Domain\Models\Order;
use Modules\Orders\Domain\Models\OrderItem;

class ImportOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $filePath,
        public int $startRow = 1,
        public int $chunkSize = 100,
        public bool $skipExisting = true
    ) {
        $this->onQueue('orders-import');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!file_exists($this->filePath)) {
            Log::error("CSV file not found: {$this->filePath}");
            return;
        }

        $handle = fopen($this->filePath, 'r');
        if (!$handle) {
            Log::error("Could not open CSV file: {$this->filePath}");
            return;
        }

        // Skip header row and rows before startRow
        $currentRow = 0;
        $processed = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $currentRow++;

            // Skip header and rows before start
            if ($currentRow <= $this->startRow) {
                continue;
            }

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Stop if we've processed the chunk
            if ($processed >= $this->chunkSize) {
                // Dispatch next chunk
                self::dispatch($this->filePath, $currentRow, $this->chunkSize, $this->skipExisting);
                fclose($handle);
                return;
            }

            try {
                $this->processOrderRow($row);
                $processed++;
            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $currentRow,
                    'error' => $e->getMessage(),
                    'data' => $row,
                ];
                Log::error("Error processing row {$currentRow}: " . $e->getMessage(), [
                    'row' => $row,
                    'exception' => $e,
                ]);
            }
        }

        fclose($handle);

        if (!empty($errors)) {
            Log::warning("Import completed with errors", [
                'processed' => $processed,
                'errors' => $errors,
            ]);
        } else {
            Log::info("Successfully imported {$processed} orders from CSV");
        }
    }

    /**
     * Process a single order row from CSV
     */
    protected function processOrderRow(array $row): void
    {
        // Expected CSV format:
        // order_number, customer_id, customer_name, customer_email, status, total_amount, currency, 
        // item1_product_id, item1_product_name, item1_sku, item1_quantity, item1_unit_price,
        // item2_product_id, item2_product_name, item2_sku, item2_quantity, item2_unit_price, ...
        
        if (count($row) < 7) {
            throw new \InvalidArgumentException('Row does not have enough columns');
        }

        $orderNumber = trim($row[0]);
        $customerId = trim($row[1]);
        $customerName = trim($row[2]);
        $customerEmail = trim($row[3]);
        $status = trim($row[4]);
        $totalAmount = (float) trim($row[5]);
        $currency = trim($row[6]) ?: 'USD';

        // Validate required fields
        if (empty($orderNumber) || empty($customerId) || empty($totalAmount)) {
            throw new \InvalidArgumentException('Missing required fields: order_number, customer_id, or total_amount');
        }

        // Parse items (starting from column 7, items are in groups of 5)
        $items = [];
        $itemColumns = array_slice($row, 7);
        
        for ($i = 0; $i < count($itemColumns); $i += 5) {
            if (!isset($itemColumns[$i]) || empty(trim($itemColumns[$i]))) {
                continue; // Skip empty items
            }

            $productId = trim($itemColumns[$i]);
            $productName = trim($itemColumns[$i + 1] ?? '');
            $sku = trim($itemColumns[$i + 2] ?? '');
            $quantity = (int) trim($itemColumns[$i + 3] ?? 1);
            $unitPrice = (float) trim($itemColumns[$i + 4] ?? 0);

            if (empty($productId) || $quantity <= 0 || $unitPrice <= 0) {
                continue; // Skip invalid items
            }

            $items[] = [
                'product_id' => $productId,
                'product_name' => $productName,
                'sku' => $sku,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $quantity * $unitPrice,
            ];
        }

        if (empty($items)) {
            throw new \InvalidArgumentException('Order must have at least one item');
        }

        // Validate status
        $validStatuses = ['pending', 'reserved', 'paid', 'failed', 'refunded', 'partially_refunded'];
        if (!in_array(strtolower($status), $validStatuses)) {
            $status = 'pending';
        }

        DB::transaction(function () use ($orderNumber, $customerId, $customerName, $customerEmail, $status, $totalAmount, $currency, $items) {
            // Check if order already exists
            $existingOrder = Order::where('order_number', $orderNumber)->first();
            if ($existingOrder) {
                Log::info("Order {$orderNumber} already exists, skipping");
                return;
            }

            // Create order
            $order = Order::create([
                'customer_id' => (int) $customerId,
                'order_number' => $orderNumber,
                'status' => strtolower($status),
                'total_amount' => $totalAmount,
                'currency' => strtoupper($currency),
                'items' => $items,
                'customer_details' => [
                    'name' => $customerName,
                    'email' => $customerEmail,
                ],
                'paid_at' => strtolower($status) === 'paid' ? now() : null,
                'reserved_at' => strtolower($status) === 'reserved' ? now() : null,
                'failed_at' => strtolower($status) === 'failed' ? now() : null,
            ]);

            // Create order items
            foreach ($items as $itemData) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => (int) $itemData['product_id'],
                    'product_name' => $itemData['product_name'],
                    'sku' => $itemData['sku'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total_price' => $itemData['total_price'],
                ]);
            }
        });
    }
}

