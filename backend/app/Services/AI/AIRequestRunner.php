<?php

namespace App\Services\AI;

use App\Models\Tenant;
use App\Models\User;
use App\Services\AI\DTO\AIResponse;
use App\Services\AI\Exceptions\AIProviderException;

/**
 * The one place "call the AI, then record what happened" is written —
 * every feature service (GymAssistantService, InsightService,
 * MemberInsightService, ReportAssistantService) calls run() instead of
 * each wrapping AIManager::chat() in its own try/catch + recordAIUsage()
 * pair. A failed call is still recorded (status 'error') before the
 * exception is rethrown, so failures show up in usage stats too.
 */
class AIRequestRunner
{
    public function __construct(
        private readonly AIManager $ai,
        private readonly AIUsageService $usage,
    ) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     *
     * @throws AIProviderException
     */
    public function run(Tenant $tenant, ?User $user, string $feature, array $messages): AIResponse
    {
        try {
            $response = $this->ai->chat($messages);
        } catch (AIProviderException $e) {
            $this->usage->recordAIUsage($tenant, $user, $feature, $this->providerName(), 'error');

            throw $e;
        }

        $this->usage->recordAIUsage($tenant, $user, $feature, $this->providerName(), 'success', $response);

        return $response;
    }

    private function providerName(): string
    {
        return (string) config('ai.default');
    }
}
