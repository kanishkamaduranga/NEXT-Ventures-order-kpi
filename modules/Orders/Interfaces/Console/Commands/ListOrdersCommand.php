<?php

namespace Modules\Orders\Interfaces\Console\Commands;

use Illuminate\Console\Command;
use Modules\Orders\Domain\Models\Order;

class ListOrdersCommand extends Command
{
    protected $signature = 'orders:list 
                            {--order-id= : Filter by order ID}
                            {--customer-id= : Filter by customer ID}
                            {--status= : Filter by status}
                            {--limit=20 : Number of records to display}';

    protected $description = 'List orders with optional filters';

    public function handle(): int
    {
        $query = Order::query();

        if ($this->option('order-id')) {
            $query->where('id', $this->option('order-id'));
        }

        if ($this->option('customer-id')) {
            $query->where('customer_id', $this->option('customer-id'));
        }

        if ($this->option('status')) {
            $query->where('status', $this->option('status'));
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No orders found.');
            return self::SUCCESS;
        }

        $this->info("Found {$orders->count()} order(s):\n");

        $headers = ['ID', 'Order Number', 'Customer ID', 'Status', 'Total Amount', 'Currency', 'Created'];
        $rows = [];

        foreach ($orders as $order) {
            $rows[] = [
                $order->id,
                $order->order_number ?? '-',
                $order->customer_id,
                $order->status,
                '$' . number_format((float) $order->total_amount, 2),
                $order->currency ?? 'USD',
                $order->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $this->table($headers, $rows);

        return self::SUCCESS;
    }
}

