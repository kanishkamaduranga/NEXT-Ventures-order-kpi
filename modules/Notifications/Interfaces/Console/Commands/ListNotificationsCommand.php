<?php

namespace Modules\Notifications\Interfaces\Console\Commands;

use Illuminate\Console\Command;
use Modules\Notifications\Domain\Repositories\NotificationRepositoryInterface;

class ListNotificationsCommand extends Command
{
    protected $signature = 'notifications:list 
                            {--order-id= : Filter by order ID}
                            {--customer-id= : Filter by customer ID}
                            {--type= : Filter by type (order_completed, order_failed)}
                            {--status= : Filter by status (pending, sent, failed)}
                            {--limit=50 : Number of records to show}';

    protected $description = 'List order notifications';

    public function handle(NotificationRepositoryInterface $repository): int
    {
        $query = \Modules\Notifications\Domain\Models\Notification::query();

        if ($orderId = $this->option('order-id')) {
            $query->where('order_id', $orderId);
        }

        if ($customerId = $this->option('customer-id')) {
            $query->where('customer_id', $customerId);
        }

        if ($type = $this->option('type')) {
            $query->where('type', $type);
        }

        if ($status = $this->option('status')) {
            $query->where('status_sent', $status);
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($notifications->isEmpty()) {
            $this->info('No notifications found.');
            return self::SUCCESS;
        }

        $this->info("Found {$notifications->count()} notification(s):\n");

        $tableData = $notifications->map(function ($notification) {
            return [
                'ID' => $notification->id,
                'Order ID' => $notification->order_id,
                'Customer ID' => $notification->customer_id,
                'Type' => $notification->type,
                'Channel' => $notification->channel,
                'Status' => $notification->status_sent,
                'Amount' => '$' . number_format($notification->total_amount, 2),
                'Sent At' => $notification->sent_at ? $notification->sent_at->format('Y-m-d H:i:s') : '-',
                'Created' => $notification->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        $this->table([
            'ID', 'Order ID', 'Customer ID', 'Type', 'Channel', 'Status', 'Amount', 'Sent At', 'Created'
        ], $tableData);

        return self::SUCCESS;
    }
}

