<?php
namespace Modules\Analytics\Application\UseCases;

use Modules\Analytics\Domain\Repositories\LeaderboardRepositoryInterface;

class UpdateLeaderboardUseCase
{
    public function __construct(
        private LeaderboardRepositoryInterface $leaderboardRepository
    ) {}

    public function execute(string $customerId, string $date, float $amount): void
    {
        $this->leaderboardRepository->updateCustomerSpending($customerId, $date, $amount);
    }

    public function handleRefund(string $customerId, string $date, float $amount): void
    {
        $this->leaderboardRepository->removeCustomerSpending($customerId, $date, $amount);
    }

    public function getTopCustomers(string $date, int $limit = 10): array
    {
        return $this->leaderboardRepository->getTopCustomers($date, $limit);
    }

    public function getCustomerRank(string $customerId, string $date): ?int
    {
        return $this->leaderboardRepository->getCustomerRank($customerId, $date);
    }
}
