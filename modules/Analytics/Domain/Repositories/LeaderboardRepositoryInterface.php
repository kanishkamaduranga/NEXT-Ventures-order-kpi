<?php
namespace Modules\Analytics\Domain\Repositories;

use Modules\Analytics\Domain\Entities\CustomerLeaderboard;

interface LeaderboardRepositoryInterface
{
    public function getDailyLeaderboard(string $date): ?CustomerLeaderboard;
    public function updateCustomerSpending(string $customerId, string $date, float $amount): void;
    public function getTopCustomers(string $date, int $limit = 10): array;
    public function getCustomerRank(string $customerId, string $date): ?int;
    public function removeCustomerSpending(string $customerId, string $date, float $amount): void;
}
