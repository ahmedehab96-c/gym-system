<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\AI\InsightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsightController extends Controller
{
    public function __construct(private readonly InsightService $insights) {}

    public function show(Request $request, string $domain): JsonResponse
    {
        if (! in_array($domain, InsightService::DOMAINS, true)) {
            return ApiResponse::error('Unknown insight domain.', ['domain' => ['Must be one of: '.implode(', ', InsightService::DOMAINS)]], 422);
        }

        $result = $this->insights->generate($request->user()->tenant, $request->user(), $domain);

        return ApiResponse::item($result);
    }
}
