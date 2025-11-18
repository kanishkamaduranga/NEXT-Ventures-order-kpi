<?php

namespace Modules\Refunds\Interfaces\Console\Commands;

use Illuminate\Console\Command;
use Modules\Refunds\Application\DTOs\RefundRequest;
use Modules\Refunds\Application\Jobs\ProcessRefundJob;

class ProcessRefundCommand extends Command
{
    protected $signature = 'refunds:process 
                            {order_id : The order ID to refund}
                            {--amount= : Refund amount (required for partial refunds)}
                            {--type=full : Refund type (full or partial)}
                            {--reason= : Reason for refund}
                            {--refund-id= : Unique refund ID for idempotency (optional)}
                            {--sync : Process synchronously instead of queuing}';

    protected $description = 'Process a refund for an order (full or partial)';

    public function handle(): int
    {
        $orderId = (int) $this->argument('order_id');
        $type = $this->option('type');
        $amount = $this->option('amount') ? (float) $this->option('amount') : null;
        $reason = $this->option('reason');
        $refundId = $this->option('refund-id');
        $sync = $this->option('sync');

        // Validate type
        if (!in_array($type, ['full', 'partial'])) {
            $this->error('Type must be either "full" or "partial"');
            return self::FAILURE;
        }

        // Validate amount for partial refunds
        if ($type === 'partial' && !$amount) {
            $this->error('Amount is required for partial refunds');
            return self::FAILURE;
        }

        // Create refund request
        $refundRequest = new RefundRequest(
            orderId: $orderId,
            amount: $amount ?? 0, // Will be set to order total for full refunds
            type: $type,
            reason: $reason,
            refundId: $refundId
        );

        $this->info("Processing {$type} refund for order #{$orderId}...");

        if ($sync) {
            // Process synchronously
            $job = new ProcessRefundJob($refundRequest);
            $job->handle(
                app(\Modules\Refunds\Domain\Repositories\RefundRepositoryInterface::class),
                app(\Modules\Orders\Domain\Repositories\OrderRepositoryInterface::class),
                app(\Modules\Refunds\Infrastructure\Services\PaymentGatewayRefundService::class)
            );
            $this->info('Refund processed synchronously.');
        } else {
            // Queue the job
            ProcessRefundJob::dispatch($refundRequest);
            $this->info('Refund job queued successfully.');
            $this->comment('Run queue worker to process: php artisan queue:work');
        }

        return self::SUCCESS;
    }
}

