<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function summary(): JsonResponse
    {
        return ApiResponse::item($this->dashboard->summary());
    }

    public function activity(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->integer('limit', 10), 1), 50);

        return ApiResponse::item($this->dashboard->activity($limit));
    }
}
