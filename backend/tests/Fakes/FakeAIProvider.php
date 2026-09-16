<?php

namespace Tests\Fakes;

use App\Services\AI\Contracts\AIProviderContract;
use App\Services\AI\DTO\AIResponse;
use App\Services\AI\Exceptions\AIProviderException;

/**
 * Bound over App\Services\AI\Contracts\AIProviderContract in AI feature
 * tests so the suite never makes a real network call (Phase 22 §11).
 * Also records every call it received, so a test can assert what a
 * feature service actually sent the "provider" (e.g. that a prompt only
 * contains the current tenant's data).
 */
class FakeAIProvider implements AIProviderContract
{
    /** @var array<int, array{messages: array, options: array}> */
    public array $calls = [];

    public function __construct(
        private readonly string $content = 'This is a fake AI response.',
        private readonly ?AIProviderException $throw = null,
    ) {}

    public function chat(array $messages, array $options = []): AIResponse
    {
        $this->calls[] = ['messages' => $messages, 'options' => $options];

        if ($this->throw) {
            throw $this->throw;
        }

        return new AIResponse(
            content: $this->content,
            model: 'fake-model',
            promptTokens: 42,
            completionTokens: 8,
            totalTokens: 50,
        );
    }
}
