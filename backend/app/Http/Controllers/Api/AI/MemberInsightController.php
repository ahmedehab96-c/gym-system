<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Member;
use App\Services\AI\MemberInsightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberInsightController extends Controller
{
    public function __construct(private readonly MemberInsightService $memberInsights) {}

    public function show(Request $request, Member $member): JsonResponse
    {
        $result = $this->memberInsights->generate($request->user()->tenant, $request->user(), $member);

        return ApiResponse::item($result);
    }
}
