<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AIProviderContract;
use App\Services\AI\DTO\AIResponse;
use App\Services\AI\Exceptions\AIProviderException;
use Illuminate\Support\Facades\Log;

/**
 * The single entry point every AI feature service calls through — never
 * a concrete provider directly. Bound in App\Providers\AppServiceProvider
 * to whichever App\Services\AI\Contracts\AIProviderContract
 * config('ai.default') selects, so this class (and everything above it)
 * stays provider-agnostic.
 */
class AIManager
{
    public function __construct(private readonly AIProviderContract $provider) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options
     *
     * @throws AIProviderException
     */
    public function chat(array $messages, array $options = []): AIResponse
    {
        try {
            return $this->provider->chat($messages, $options);
        } catch (AIProviderException $e) {
            Log::warning('AI provider request failed', ['message' => $e->getMessage()]);

            throw $e;
        }
    }
}
