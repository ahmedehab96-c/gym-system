<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\DTO\AIResponse;
use App\Services\AI\Exceptions\AIProviderException;

/**
 * Every AI provider (OpenAI-compatible today, anything else later)
 * implements exactly this. App\Services\AI\AIManager — the only thing
 * business services depend on — is bound to whichever implementation
 * config('ai.default') selects, so a business service like
 * GymAssistantService never references a concrete provider.
 */
interface AIProviderContract
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options  Per-call overrides: model, max_tokens, temperature.
     *
     * @throws AIProviderException on any failure to reach or parse a response from the provider.
     */
    public function chat(array $messages, array $options = []): AIResponse;
}
