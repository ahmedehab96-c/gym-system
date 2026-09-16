<?php

namespace Tests\Fakes;

use App\Services\Communication\Contracts\PushProviderContract;
use App\Services\Communication\DTO\PushSendResult;
use App\Services\Communication\Exceptions\CommunicationProviderException;

/** Bound over PushProviderContract in tests so no real network call is ever made (Phase 24 §11). */
class FakePushProvider implements PushProviderContract
{
    /** @var array<int, array{token: string, title: string, body: string, data: array}> */
    public array $calls = [];

    public function __construct(private readonly ?CommunicationProviderException $throw = null) {}

    public function send(string $deviceToken, string $title, string $body, array $data = []): PushSendResult
    {
        $this->calls[] = ['token' => $deviceToken, 'title' => $title, 'body' => $body, 'data' => $data];

        if ($this->throw) {
            throw $this->throw;
        }

        return new PushSendResult(messageId: 'projects/fake/messages/1', status: 'sent');
    }
}
