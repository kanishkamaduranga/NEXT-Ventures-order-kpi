<?php

namespace Modules\Analytics\Interfaces\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Analytics\Application\UseCases\GenerateDailyReportUseCase;
use Modules\Analytics\Application\UseCases\UpdateLeaderboardUseCase;

class AnalyticsController extends Controller
{
    public function __construct(
        private GenerateDailyReportUseCase $generateReportUseCase,
        private UpdateLeaderboardUseCase $leaderboardUseCase
    ) {}

    /**
     * Get daily analytics report (KPIs and leaderboard)
     */
    public function getDailyReport(string $date): JsonResponse
    {
        try {
            // Validate date format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return response()->json([
                    'error' => 'Invalid date format. Expected YYYY-MM-DD'
                ], 400);
            }

            $report = $this->generateReportUseCase->execute($date);

            return response()->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer leaderboard for a specific date
     */
    public function getLeaderboard(string $date, Request $request): JsonResponse
    {
        try {
            // Validate date format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return response()->json([
                    'error' => 'Invalid date format. Expected YYYY-MM-DD'
                ], 400);
            }

            $limit = (int) $request->get('limit', 10);
            $limit = max(1, min(100, $limit)); // Limit between 1 and 100

            $topCustomers = $this->leaderboardUseCase->getTopCustomers($date, $limit);

            // Format leaderboard data
            // Redis WITHSCORES returns associative array: [customer_id => score, ...]
            $leaderboard = [];
            $rank = 1;
            
            foreach ($topCustomers as $customerId => $amount) {
                $leaderboard[] = [
                    'rank' => $rank++,
                    'customer_id' => $customerId,
                    'total_spent' => (float) $amount,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $date,
                    'limit' => $limit,
                    'leaderboard' => $leaderboard,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get KPIs for a date range
     */
    public function getKpisForDateRange(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date', now()->format('Y-m-d'));

            if (!$startDate) {
                return response()->json([
                    'error' => 'start_date parameter is required'
                ], 400);
            }

            // Validate date formats
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || 
                !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                return response()->json([
                    'error' => 'Invalid date format. Expected YYYY-MM-DD'
                ], 400);
            }

            $report = $this->generateReportUseCase->executeForDateRange($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

