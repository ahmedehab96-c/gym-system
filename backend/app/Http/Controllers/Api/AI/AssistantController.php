<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Http\Requests\AskAssistantRequest;
use App\Http\Responses\ApiResponse;
use App\Services\AI\GymAssistantService;
use Illuminate\Http\JsonResponse;

class AssistantController extends Controller
{
    public function __construct(private readonly GymAssistantService $assistant) {}

    public function ask(AskAssistantRequest $request): JsonResponse
    {
        $result = $this->assistant->ask(
            $request->user()->tenant,
            $request->user(),
            $request->validated('question'),
        );

        return ApiResponse::item($result);
    }
}
