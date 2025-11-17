<?php

use Illuminate\Support\Facades\Route;
use Modules\Analytics\Interfaces\Http\Controllers\AnalyticsController;

// API Version 1
Route::prefix('v1')->group(function () {
    // Analytics API endpoints
    Route::prefix('analytics')->group(function () {
        Route::get('/daily/{date}', [AnalyticsController::class, 'getDailyReport']);
        Route::get('/leaderboard/{date}', [AnalyticsController::class, 'getLeaderboard']);
        Route::get('/kpis', [AnalyticsController::class, 'getKpisForDateRange']);
    });
});

