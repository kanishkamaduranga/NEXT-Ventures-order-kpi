<?php
namespace Modules\Analytics\Infrastructure\Repositories;

use Illuminate\Support\Facades\Redis;
use Modules\Analytics\Domain\Entities\CustomerLeaderboard;
use Modules\Analytics\Domain\Repositories\LeaderboardRepositoryInterface;

class RedisLeaderboardRepository implements LeaderboardRepositoryInterface
{
    private const LEADERBOARD_KEY = 'leaderboard:daily:%s'; // leaderboard:daily:2024-01-15

    public function getDailyLeaderboard(string $date): ?CustomerLeaderboard
    {
        $key = sprintf(self::LEADERBOARD_KEY, $date);
        $topCustomers = Redis::zrevrange($key, 0, -1, 'WITHSCORES');

        if (empty($topCustomers)) {
            return null;
        }

        return new CustomerLeaderboard('daily', $date, $topCustomers);
    }

    public function updateCustomerSpending(string $customerId, string $date, float $amount): void
    {
        $key = sprintf(self::LEADERBOARD_KEY, $date);

        Redis::pipeline(function ($pipe) use ($key, $customerId, $amount) {
            $pipe->zincrby($key, $amount, $customerId);
            $pipe->expire($key, 60 * 60 * 24 * 30); // 30 days expiration
        });
    }

    public function getTopCustomers(string $date, int $limit = 10): array
    {
        $key = sprintf(self::LEADERBOARD_KEY, $date);
        return Redis::zrevrange($key, 0, $limit - 1, 'WITHSCORES');
    }

    public function getCustomerRank(string $customerId, string $date): ?int
    {
        $key = sprintf(self::LEADERBOARD_KEY, $date);

        // ZREVRANK returns 0-based rank (0 = highest)
        $rank = Redis::zrevrank($key, $customerId);

        return $rank !== null ? $rank + 1 : null;
    }

    public function removeCustomerSpending(string $customerId, string $date, float $amount): void
    {
        $key = sprintf(self::LEADERBOARD_KEY, $date);

        Redis::pipeline(function ($pipe) use ($key, $customerId, $amount) {
            $pipe->zincrby($key, -$amount, $customerId);
            $pipe->expire($key, 60 * 60 * 24 * 30);
        });
    }

    public function getCustomerSpending(string $customerId, string $date): float
    {
        $key = sprintf(self::LEADERBOARD_KEY, $date);
        return (float) Redis::zscore($key, $customerId);
    }
}
