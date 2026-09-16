<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderContract;
use App\Services\AI\DTO\AIResponse;
use App\Services\AI\Exceptions\AIProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Talks to any OpenAI-compatible /chat/completions endpoint. "OpenAI-
 * compatible" is deliberate: the same client works against OpenAI
 * itself, Azure OpenAI, or a self-hosted gateway just by changing
 * config('ai.providers.openai.base_url') — no code change.
 */
class OpenAIProvider implements AIProviderContract
{
    /**
     * @param  array{api_key: ?string, base_url: string, model: string, timeout: int}  $config
     */
    public function __construct(private readonly array $config) {}

    public function chat(array $messages, array $options = []): AIResponse
    {
        $apiKey = $this->config['api_key'] ?? null;

        if (! $apiKey) {
            throw new AIProviderException('The AI provider is not configured. Set OPENAI_API_KEY in the environment.');
        }

        $model = $options['model'] ?? $this->config['model'] ?? 'gpt-4o-mini';

        try {
            $response = Http::withToken($apiKey)
                ->timeout($this->config['timeout'] ?? 20)
                ->baseUrl(rtrim($this->config['base_url'] ?? 'https://api.openai.com/v1', '/'))
                ->post('/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'max_tokens' => $options['max_tokens'] ?? config('ai.max_tokens', 500),
                    'temperature' => $options['temperature'] ?? config('ai.temperature', 0.3),
                ]);
        } catch (ConnectionException $e) {
            throw new AIProviderException('Could not reach the AI provider.', previous: $e);
        }

        if ($response->failed()) {
            throw new AIProviderException("The AI provider returned an error (HTTP {$response->status()}).");
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new AIProviderException('The AI provider returned an empty response.');
        }

        return new AIResponse(
            content: trim($content),
            model: $response->json('model') ?? $model,
            promptTokens: $response->json('usage.prompt_tokens'),
            completionTokens: $response->json('usage.completion_tokens'),
            totalTokens: $response->json('usage.total_tokens'),
        );
    }
}
