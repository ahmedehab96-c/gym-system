<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\FinanceReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function __construct(private readonly FinanceReportService $reports) {}

    public function overview(): JsonResponse
    {
        return ApiResponse::item($this->reports->overview());
    }

    public function revenueOverTime(Request $request): JsonResponse
    {
        $months = min(max((int) $request->integer('months', 12), 1), 24);

        return ApiResponse::item($this->reports->revenueOverTime($months));
    }
}
