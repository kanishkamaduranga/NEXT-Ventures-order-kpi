<?php
namespace Modules\Orders\Infrastructure\Queue\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Orders\Application\Services\OrderWorkflowCoordinator;

class ProcessOrderWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $orderId
    ) {
        $this->onQueue('order-processing');
    }

    public function handle(OrderWorkflowCoordinator $workflowCoordinator): void
    {
        $workflowCoordinator->startOrderProcessing($this->orderId);
    }

    public function retryUntil()
    {
        return now()->addMinutes(30);
    }
}
