<?php

namespace Tests\Fakes;

use App\Services\Communication\Contracts\WhatsAppProviderContract;
use App\Services\Communication\DTO\WhatsAppSendResult;
use App\Services\Communication\Exceptions\CommunicationProviderException;

/** Bound over WhatsAppProviderContract in tests so no real network call is ever made (Phase 24 §11). */
class FakeWhatsAppProvider implements WhatsAppProviderContract
{
    /** @var array<int, array{to: string, template: string, params: array}> */
    public array $calls = [];

    public function __construct(private readonly ?CommunicationProviderException $throw = null) {}

    public function sendTemplate(string $to, string $templateName, array $params): WhatsAppSendResult
    {
        $this->calls[] = ['to' => $to, 'template' => $templateName, 'params' => $params];

        if ($this->throw) {
            throw $this->throw;
        }

        return new WhatsAppSendResult(messageId: 'wamid.fake123', status: 'sent');
    }
}
