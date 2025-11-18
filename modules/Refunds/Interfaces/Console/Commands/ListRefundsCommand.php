<?php

namespace Modules\Refunds\Interfaces\Console\Commands;

use Illuminate\Console\Command;
use Modules\Refunds\Domain\Models\Refund;

class ListRefundsCommand extends Command
{
    protected $signature = 'refunds:list 
                            {--order-id= : Filter by order ID}
                            {--customer-id= : Filter by customer ID}
                            {--status= : Filter by status (pending, processing, completed, failed)}
                            {--type= : Filter by type (full, partial)}
                            {--limit=20 : Number of records to display}';

    protected $description = 'List refunds with optional filters';

    public function handle(): int
    {
        $query = Refund::query();

        if ($this->option('order-id')) {
            $query->where('order_id', $this->option('order-id'));
        }

        if ($this->option('customer-id')) {
            $query->where('customer_id', $this->option('customer-id'));
        }

        if ($this->option('status')) {
            $query->where('status', $this->option('status'));
        }

        if ($this->option('type')) {
            $query->where('type', $this->option('type'));
        }

        $refunds = $query->orderBy('created_at', 'desc')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($refunds->isEmpty()) {
            $this->info('No refunds found.');
            return self::SUCCESS;
        }

        $this->info("Found {$refunds->count()} refund(s):\n");

        $headers = ['ID', 'Refund ID', 'Order ID', 'Customer ID', 'Amount', 'Type', 'Status', 'Created'];
        $rows = [];

        foreach ($refunds as $refund) {
            $rows[] = [
                $refund->id,
                $refund->refund_id,
                $refund->order_id,
                $refund->customer_id,
                '$' . number_format($refund->amount, 2),
                $refund->type,
                $refund->status,
                $refund->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $this->table($headers, $rows);

        return self::SUCCESS;
    }
}

