<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Http\Requests\SummarizeReportRequest;
use App\Http\Responses\ApiResponse;
use App\Services\AI\ReportAssistantService;
use Illuminate\Http\JsonResponse;

class ReportAssistantController extends Controller
{
    public function __construct(private readonly ReportAssistantService $reports) {}

    public function summarize(SummarizeReportRequest $request): JsonResponse
    {
        $result = $this->reports->summarize(
            $request->user()->tenant,
            $request->user(),
            $request->validated('report_type'),
            (int) ($request->validated('months') ?? 6),
        );

        return ApiResponse::item($result);
    }
}
