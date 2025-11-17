<?php
namespace Modules\Analytics\Application\UseCases;

use Modules\Analytics\Domain\Entities\DailyKpi;
use Modules\Analytics\Domain\Repositories\AnalyticsRepositoryInterface;
use Modules\Analytics\Domain\Repositories\LeaderboardRepositoryInterface;
use Modules\Analytics\Domain\ValueObjects\KpiDate;

class GenerateDailyReportUseCase
{
    public function __construct(
        private AnalyticsRepositoryInterface $analyticsRepository,
        private LeaderboardRepositoryInterface $leaderboardRepository
    ) {}

    public function execute(string $date): array
    {
        $kpiDate = new KpiDate($date);

        $kpi = $this->analyticsRepository->getDailyKpi($kpiDate);
        $topCustomers = $this->leaderboardRepository->getTopCustomers($date, 10);

        return [
            'kpis' => $kpi ? $kpi->toArray() : null,
            'leaderboard' => $topCustomers,
            'date' => $date,
            'generated_at' => now()->toISOString()
        ];
    }

    public function executeForDateRange(string $startDate, string $endDate): array
    {
        $startKpiDate = new KpiDate($startDate);
        $endKpiDate = new KpiDate($endDate);

        $kpis = $this->analyticsRepository->getKpiDateRange($startKpiDate, $endKpiDate);

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'kpis' => array_map(fn($kpi) => $kpi->toArray(), $kpis),
            'generated_at' => now()->toISOString()
        ];
    }
}
