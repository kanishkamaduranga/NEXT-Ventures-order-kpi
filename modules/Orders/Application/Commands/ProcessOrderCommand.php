<?php

namespace Modules\Orders\Application\Commands;

use Illuminate\Console\Command;
use Modules\Orders\Infrastructure\Queue\Jobs\ProcessOrderWorkflowJob;

class ProcessOrderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:process 
                            {order_id : The ID of the order to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process an order through the workflow: reserve stock → payment → finalize/rollback';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $orderId = $this->argument('order_id');

        $this->info("Dispatching order processing workflow for order: {$orderId}");

        // Dispatch the workflow job
        ProcessOrderWorkflowJob::dispatch($orderId);

        $this->info("Order processing job dispatched to queue.");
        $this->info("Monitor progress with: php artisan queue:work --queue=order-processing");

        return Command::SUCCESS;
    }
}

