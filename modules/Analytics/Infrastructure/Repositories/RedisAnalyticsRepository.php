<?php
namespace Modules\Analytics\Infrastructure\Repositories;

use Illuminate\Support\Facades\Redis;
use Modules\Analytics\Domain\Entities\DailyKpi;
use Modules\Analytics\Domain\Repositories\AnalyticsRepositoryInterface;
use Modules\Analytics\Domain\ValueObjects\KpiDate;

class RedisAnalyticsRepository implements AnalyticsRepositoryInterface
{
    private const DAILY_KPI_KEY = 'kpi:daily:%s'; // kpi:daily:2024-01-15
    private const DAILY_ORDERS_SET = 'kpi:orders:daily:%s'; // kpi:orders:daily:2024-01-15
    private const DAILY_CUSTOMERS_SET = 'kpi:customers:daily:%s'; // kpi:customers:daily:2024-01-15

    public function getDailyKpi(KpiDate $date): ?DailyKpi
    {
        $key = sprintf(self::DAILY_KPI_KEY, $date->toString());
        $data = Redis::hgetall($key);

        if (empty($data)) {
            return null;
        }

        return new DailyKpi(
            date: $date->toString(),
            totalRevenue: (float) ($data['total_revenue'] ?? 0),
            orderCount: (int) ($data['order_count'] ?? 0),
            averageOrderValue: (float) ($data['average_order_value'] ?? 0),
            successfulOrders: (int) ($data['successful_orders'] ?? 0),
            failedOrders: (int) ($data['failed_orders'] ?? 0),
            refundAmount: (float) ($data['refund_amount'] ?? 0),
            uniqueCustomers: (int) ($data['unique_customers'] ?? 0)
        );
    }

    public function saveDailyKpi(DailyKpi $kpi): void
    {
        $key = sprintf(self::DAILY_KPI_KEY, $kpi->date);

        Redis::pipeline(function ($pipe) use ($key, $kpi) {
            $pipe->hset($key, [
                'total_revenue' => $kpi->totalRevenue,
                'order_count' => $kpi->orderCount,
                'average_order_value' => $kpi->averageOrderValue,
                'successful_orders' => $kpi->successfulOrders,
                'failed_orders' => $kpi->failedOrders,
                'refund_amount' => $kpi->refundAmount,
                'unique_customers' => $kpi->uniqueCustomers,
                'updated_at' => now()->toISOString()
            ]);

            // Set expiration to 30 days for daily KPIs
            $pipe->expire($key, 60 * 60 * 24 * 30);
        });
    }

    public function updateDailyKpi(KpiDate $date, array $updates): void
    {
        $key = sprintf(self::DAILY_KPI_KEY, $date->toString());

        if (!empty($updates)) {
            Redis::hmset($key, $updates);
            Redis::expire($key, 60 * 60 * 24 * 30);
        }
    }

    public function getKpiDateRange(KpiDate $startDate, KpiDate $endDate): array
    {
        $kpis = [];
        $currentDate = $startDate;

        while (strtotime($currentDate->toString()) <= strtotime($endDate->toString())) {
            $kpi = $this->getDailyKpi($currentDate);
            if ($kpi) {
                $kpis[] = $kpi;
            }
            // Move to next day
            $currentDate = new KpiDate(date('Y-m-d', strtotime($currentDate->toString() . ' +1 day')));
        }

        return $kpis;
    }

    public function incrementOrderMetrics(KpiDate $date, float $amount, bool $successful = true, ?string $customerId = null): void
    {
        $key = sprintf(self::DAILY_KPI_KEY, $date->toString());
        $customerSetKey = sprintf(self::DAILY_CUSTOMERS_SET, $date->toString());

        Redis::pipeline(function ($pipe) use ($key, $customerSetKey, $amount, $successful, $customerId) {
            // Increment total revenue for successful orders
            if ($successful && $amount > 0) {
                $pipe->hincrbyfloat($key, 'total_revenue', $amount);
            }

            // Increment order count
            $pipe->hincrby($key, 'order_count', 1);

            // Increment successful/failed orders
            if ($successful) {
                $pipe->hincrby($key, 'successful_orders', 1);
            } else {
                $pipe->hincrby($key, 'failed_orders', 1);
            }

            // Track unique customer if provided
            if ($customerId !== null) {
                // Use Lua script to atomically add customer and increment counter if new
                $pipe->eval(
                    "local added = redis.call('sadd', KEYS[2], ARGV[1])
                     if added == 1 then
                         redis.call('hincrby', KEYS[1], 'unique_customers', 1)
                     end
                     redis.call('expire', KEYS[2], 2592000)",
                    2, $key, $customerSetKey, $customerId
                );
            }

            // Recalculate average order value
            $this->recalculateAverageOrderValue($pipe, $key);

            // Update timestamp
            $pipe->hset($key, 'updated_at', now()->toISOString());
            $pipe->expire($key, 60 * 60 * 24 * 30);
        });
    }

    public function decrementOrderMetrics(KpiDate $date, float $amount, bool $wasSuccessful = true): void
    {
        $key = sprintf(self::DAILY_KPI_KEY, $date->toString());

        Redis::pipeline(function ($pipe) use ($key, $amount, $wasSuccessful) {
            // Decrement total revenue for refunds
            if ($wasSuccessful && $amount > 0) {
                $pipe->hincrbyfloat($key, 'total_revenue', -$amount);
                $pipe->hincrbyfloat($key, 'refund_amount', $amount);
            }

            // Note: We don't decrement order count for refunds, just adjust revenue

            // Recalculate average order value
            $this->recalculateAverageOrderValue($pipe, $key);

            $pipe->hset($key, 'updated_at', now()->toISOString());
            $pipe->expire($key, 60 * 60 * 24 * 30);
        });
    }

    private function recalculateAverageOrderValue($pipe, string $key): void
    {
        // This would be called after revenue/order count changes
        // In a real implementation, you might want to calculate this on read
        // or use a Lua script for atomic operations
        $pipe->eval(
            "local revenue = redis.call('hget', KEYS[1], 'total_revenue') or 0
             local orders = redis.call('hget', KEYS[1], 'successful_orders') or 0
             if tonumber(orders) > 0 then
                 local aov = tonumber(revenue) / tonumber(orders)
                 redis.call('hset', KEYS[1], 'average_order_value', aov)
             end",
            1, $key
        );
    }

    public function trackUniqueCustomer(KpiDate $date, string $customerId): void
    {
        $key = sprintf(self::DAILY_CUSTOMERS_SET, $date->toString());
        $kpiKey = sprintf(self::DAILY_KPI_KEY, $date->toString());

        $added = Redis::sadd($key, $customerId);
        if ($added) {
            Redis::hincrby($kpiKey, 'unique_customers', 1);
            Redis::expire($key, 60 * 60 * 24 * 30);
        }
    }
}
