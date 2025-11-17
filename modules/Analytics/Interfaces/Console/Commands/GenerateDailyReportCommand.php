<?php
namespace Modules\Analytics\Interfaces\Console\Commands;

use Illuminate\Console\Command;
use Modules\Analytics\Application\UseCases\GenerateDailyReportUseCase;

class GenerateDailyReportCommand extends Command
{
    protected $signature = 'analytics:daily-report
                            {date? : The date in YYYY-MM-DD format (default: yesterday)}
                            {--export= : Export to file (json|csv)}';

    protected $description = 'Generate daily KPIs and leaderboard report';

    public function handle(GenerateDailyReportUseCase $useCase): int
    {
        $date = $this->argument('date') ?: now()->subDay()->format('Y-m-d');

        $this->info("Generating daily report for: {$date}");

        try {
            $report = $useCase->execute($date);

            $this->displayReport($report);

            if ($export = $this->option('export')) {
                $this->exportReport($report, $export);
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to generate report: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    private function displayReport(array $report): void
    {
        $this->info("\n=== DAILY ANALYTICS REPORT ===");
        $this->info("Date: {$report['date']}");
        $this->info("Generated: {$report['generated_at']}");

        if ($report['kpis']) {
            $this->info("\n--- KEY PERFORMANCE INDICATORS ---");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Revenue', '$' . number_format($report['kpis']['total_revenue'], 2)],
                    ['Order Count', $report['kpis']['order_count']],
                    ['Successful Orders', $report['kpis']['successful_orders']],
                    ['Failed Orders', $report['kpis']['failed_orders']],
                    ['Conversion Rate', number_format($report['kpis']['conversion_rate'], 2) . '%'],
                    ['Average Order Value', '$' . number_format($report['kpis']['average_order_value'], 2)],
                    ['Unique Customers', $report['kpis']['unique_customers']],
                    ['Refund Amount', '$' . number_format($report['kpis']['refund_amount'], 2)],
                ]
            );
        }

        if ($report['leaderboard']) {
            $this->info("\n--- TOP CUSTOMERS LEADERBOARD ---");
            $leaderboardData = [];
            $rank = 1;

            foreach ($report['leaderboard'] as $customerId => $amount) {
                $leaderboardData[] = [
                    'Rank' => $rank++,
                    'Customer ID' => $customerId,
                    'Total Spent' => '$' . number_format($amount, 2)
                ];
            }

            $this->table(['Rank', 'Customer ID', 'Total Spent'], $leaderboardData);
        }
    }

    private function exportReport(array $report, string $format): void
    {
        $filename = "daily_report_{$report['date']}.{$format}";

        if ($format === 'json') {
            file_put_contents($filename, json_encode($report, JSON_PRETTY_PRINT));
        } elseif ($format === 'csv') {
            // Implement CSV export logic
        }

        $this->info("Report exported to: {$filename}");
    }
}
