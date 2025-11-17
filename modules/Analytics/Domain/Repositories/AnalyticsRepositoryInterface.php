<?php
namespace Modules\Analytics\Domain\Repositories;

use Modules\Analytics\Domain\Entities\DailyKpi;
use Modules\Analytics\Domain\ValueObjects\KpiDate;

interface AnalyticsRepositoryInterface
{
    public function getDailyKpi(KpiDate $date): ?DailyKpi;
    public function saveDailyKpi(DailyKpi $kpi): void;
    public function updateDailyKpi(KpiDate $date, array $updates): void;
    public function getKpiDateRange(KpiDate $startDate, KpiDate $endDate): array;
    public function incrementOrderMetrics(KpiDate $date, float $amount, bool $successful = true): void;
    public function decrementOrderMetrics(KpiDate $date, float $amount, bool $wasSuccessful = true): void;
}
