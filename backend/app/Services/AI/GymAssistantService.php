<?php

namespace App\Services\AI;

use App\Models\Tenant;
use App\Models\User;

/**
 * Answers a free-text staff question using only a controlled snapshot of
 * the tenant's own data (App\Services\AI\GymDataToolService) — the AI
 * never sees a database connection or runs a query itself (Phase 22 §3).
 */
class GymAssistantService
{
    public function __construct(
        private readonly AIRequestRunner $runner,
        private readonly GymDataToolService $tools,
    ) {}

    public function ask(Tenant $tenant, ?User $user, string $question): array
    {
        $snapshot = $this->tools->snapshot();
        $messages = PromptLibrary::assistantMessagesV1($question, $snapshot);

        $response = $this->runner->run($tenant, $user, 'assistant', $messages);

        return [
            'question' => $question,
            'answer' => $response->content,
            'data' => $snapshot,
        ];
    }
}
