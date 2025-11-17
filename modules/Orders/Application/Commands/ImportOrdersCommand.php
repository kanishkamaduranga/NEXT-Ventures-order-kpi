<?php

namespace Modules\Orders\Application\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Orders\Application\Jobs\ImportOrdersJob;

class ImportOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:import 
                            {file : The path to the CSV file to import}
                            {--chunk=100 : Number of rows to process per job}
                            {--skip-existing : Skip orders that already exist (default behavior)}
                            {--force : Re-import existing orders (updates will be skipped, only new orders imported)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import orders from a CSV file using queued jobs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');
        $chunkSize = (int) $this->option('chunk');

        // Validate file exists
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        // Validate it's a CSV file
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            $this->error("File must be a CSV file. Got: {$extension}");
            return Command::FAILURE;
        }

        // Get absolute path
        $absolutePath = realpath($filePath);
        if (!$absolutePath) {
            $this->error("Could not resolve file path: {$filePath}");
            return Command::FAILURE;
        }

        $skipExisting = $this->option('skip-existing') || !$this->option('force');
        
        $this->info("Starting import of orders from: {$absolutePath}");
        $this->info("Chunk size: {$chunkSize} rows per job");
        $this->info("Mode: " . ($skipExisting ? "Skip existing orders" : "Import all orders"));

        // Count total rows (excluding header)
        $totalRows = $this->countRows($absolutePath);
        $this->info("Total rows to process: {$totalRows}");

        // Dispatch the first job
        ImportOrdersJob::dispatch($absolutePath, 1, $chunkSize, $skipExisting);

        $this->info("Import job dispatched to queue. Processing will happen in the background.");
        $this->info("Monitor progress with: php artisan queue:work");
        $this->info("Or use Laravel Horizon if configured.");

        Log::info("Orders import initiated", [
            'file' => $absolutePath,
            'total_rows' => $totalRows,
            'chunk_size' => $chunkSize,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Count the number of rows in the CSV file (excluding header)
     */
    protected function countRows(string $filePath): int
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return 0;
        }

        $count = 0;
        while (fgetcsv($handle) !== false) {
            $count++;
        }

        fclose($handle);

        // Subtract header row
        return max(0, $count - 1);
    }
}

