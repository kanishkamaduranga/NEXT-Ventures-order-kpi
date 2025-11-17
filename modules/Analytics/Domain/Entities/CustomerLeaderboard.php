<?php
namespace Modules\Analytics\Domain\Entities;

class CustomerLeaderboard
{
    public function __construct(
        public string $period, // daily, weekly, monthly
        public string $date,
        public array $topCustomers // [customer_id => total_spent]
    ) {}

    public function getTopCustomers(int $limit = 10): array
    {
        return array_slice($this->topCustomers, 0, $limit, true);
    }

    public function getCustomerRank(string $customerId): ?int
    {
        $rank = array_search($customerId, array_keys($this->topCustomers));
        return $rank !== false ? $rank + 1 : null;
    }
}
