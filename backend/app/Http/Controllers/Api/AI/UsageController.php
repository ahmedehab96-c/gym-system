<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\AI\AIUsageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsageController extends Controller
{
    public function __construct(private readonly AIUsageService $usage) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::item($this->usage->stats($request->user()->tenant));
    }
}
