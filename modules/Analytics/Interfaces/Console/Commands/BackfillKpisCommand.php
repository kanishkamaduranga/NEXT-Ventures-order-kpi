<?php

namespace Modules\Analytics\Interfaces\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Analytics\Application\UseCases\UpdateDailyKpisUseCase;
use Modules\Analytics\Application\UseCases\UpdateLeaderboardUseCase;

class BackfillKpisCommand extends Command
{
    protected $signature = 'analytics:backfill-kpis 
                            {--from= : Start date (YYYY-MM-DD)}
                            {--to= : End date (YYYY-MM-DD, default: today)}
                            {--force : Force recalculation even if KPIs exist}';

    protected $description = 'Backfill KPIs and leaderboard from existing completed orders';

    public function handle(
        UpdateDailyKpisUseCase $updateKpisUseCase,
        UpdateLeaderboardUseCase $updateLeaderboardUseCase
    ): int {
        $from = $this->option('from') ? Carbon::parse($this->option('from')) : Carbon::today()->subDays(30);
        $to = $this->option('to') ? Carbon::parse($this->option('to')) : Carbon::today();
        $force = $this->option('force');

        $this->info("Backfilling KPIs from {$from->format('Y-m-d')} to {$to->format('Y-m-d')}");

        // Get all completed orders in the date range
        $orders = DB::table('orders')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->whereNull('deleted_at')
            ->orderBy('created_at')
            ->get();

        $this->info("Found {$orders->count()} completed orders");

        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        $processed = 0;
        foreach ($orders as $order) {
            $date = Carbon::parse($order->created_at)->format('Y-m-d');

            // Update KPIs
            $updateKpisUseCase->execute($date, (float) $order->total_amount, true);

            // Update leaderboard
            $updateLeaderboardUseCase->execute(
                (string) $order->customer_id,
                $date,
                (float) $order->total_amount
            );

            $processed++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Processed {$processed} orders");

        return Command::SUCCESS;
    }
}

