<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function memberGrowth(Request $request): JsonResponse
    {
        $months = min(max((int) $request->integer('months', 12), 1), 24);

        return ApiResponse::item($this->analytics->memberGrowth($months));
    }

    public function attendanceTrends(Request $request): JsonResponse
    {
        $days = min(max((int) $request->integer('days', 30), 1), 90);

        return ApiResponse::item($this->analytics->attendanceTrends($days));
    }

    public function revenueTrends(Request $request): JsonResponse
    {
        $months = min(max((int) $request->integer('months', 12), 1), 24);

        return ApiResponse::item($this->analytics->revenueTrends($months));
    }

    public function expenseTrends(Request $request): JsonResponse
    {
        $months = min(max((int) $request->integer('months', 12), 1), 24);

        return ApiResponse::item($this->analytics->expenseTrends($months));
    }

    public function netRevenue(Request $request): JsonResponse
    {
        $months = min(max((int) $request->integer('months', 12), 1), 24);

        return ApiResponse::item($this->analytics->netRevenue($months));
    }

    public function membershipDistribution(): JsonResponse
    {
        return ApiResponse::item($this->analytics->membershipDistribution());
    }

    public function revenueByMethod(): JsonResponse
    {
        return ApiResponse::item($this->analytics->revenueByMethod());
    }

    public function classPerformance(): JsonResponse
    {
        return ApiResponse::item($this->analytics->classPerformance());
    }

    public function trainerPerformance(): JsonResponse
    {
        return ApiResponse::item($this->analytics->trainerPerformance());
    }

    public function equipmentStats(): JsonResponse
    {
        return ApiResponse::item($this->analytics->equipmentStats());
    }
}
