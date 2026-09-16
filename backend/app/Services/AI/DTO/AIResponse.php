<?php

namespace App\Services\AI\DTO;

/**
 * A provider-agnostic result of one AI chat completion — every
 * App\Services\AI\Contracts\AIProviderContract implementation returns
 * this same shape regardless of which API it actually called, so
 * nothing above the provider layer needs to know which one ran.
 */
final class AIResponse
{
    public function __construct(
        public readonly string $content,
        public readonly ?string $model = null,
        public readonly ?int $promptTokens = null,
        public readonly ?int $completionTokens = null,
        public readonly ?int $totalTokens = null,
    ) {}
}
