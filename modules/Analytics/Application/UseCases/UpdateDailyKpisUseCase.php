<?php
namespace Modules\Analytics\Application\UseCases;

use Modules\Analytics\Domain\Entities\DailyKpi;
use Modules\Analytics\Domain\Repositories\AnalyticsRepositoryInterface;
use Modules\Analytics\Domain\ValueObjects\KpiDate;

class UpdateDailyKpisUseCase
{
    public function __construct(
        private AnalyticsRepositoryInterface $analyticsRepository
    ) {}

    public function execute(string $date, float $amount, bool $successful = true, ?string $customerId = null): void
    {
        $kpiDate = new KpiDate($date);

        if ($successful) {
            $this->analyticsRepository->incrementOrderMetrics($kpiDate, $amount, true, $customerId);
        } else {
            $this->analyticsRepository->incrementOrderMetrics($kpiDate, 0, false, $customerId);
        }
    }

    public function handleRefund(string $date, float $amount, bool $wasSuccessful = true): void
    {
        $kpiDate = new KpiDate($date);
        $this->analyticsRepository->decrementOrderMetrics($kpiDate, $amount, $wasSuccessful);
    }

    public function recalculateDailyKpi(string $date): DailyKpi
    {
        // This would recalculate from source data if needed
        $kpiDate = new KpiDate($date);
        return $this->analyticsRepository->getDailyKpi($kpiDate);
    }
}
