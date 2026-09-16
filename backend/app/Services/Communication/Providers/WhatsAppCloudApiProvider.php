<?php

namespace App\Services\Communication\Providers;

use App\Services\Communication\Contracts\WhatsAppProviderContract;
use App\Services\Communication\DTO\WhatsAppSendResult;
use App\Services\Communication\Exceptions\CommunicationProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Talks to Meta's official WhatsApp Business Cloud API directly via
 * Laravel's HTTP client — no vendor SDK, mirroring
 * App\Services\Payment\Providers\StripeProvider. This is the ONLY
 * WhatsApp integration in the app; never an unofficial/third-party
 * gateway (Phase 24 §4).
 */
class WhatsAppCloudApiProvider implements WhatsAppProviderContract
{
    /**
     * @param  array{phone_number_id: ?string, access_token: ?string, api_version: string, base_url: string, timeout: int}  $config
     */
    public function __construct(private readonly array $config) {}

    public function sendTemplate(string $to, string $templateName, array $params): WhatsAppSendResult
    {
        $accessToken = $this->config['access_token'] ?? null;
        $phoneNumberId = $this->config['phone_number_id'] ?? null;

        if (! $accessToken || ! $phoneNumberId) {
            throw new CommunicationProviderException('The WhatsApp provider is not configured. Set WHATSAPP_ACCESS_TOKEN and WHATSAPP_PHONE_NUMBER_ID.');
        }

        $version = $this->config['api_version'] ?? 'v20.0';
        $baseUrl = rtrim($this->config['base_url'] ?? 'https://graph.facebook.com', '/');

        try {
            $response = Http::withToken($accessToken)
                ->timeout($this->config['timeout'] ?? 15)
                ->post("{$baseUrl}/{$version}/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'template',
                    'template' => [
                        'name' => $templateName,
                        'language' => ['code' => 'en_US'],
                        'components' => empty($params) ? [] : [[
                            'type' => 'body',
                            'parameters' => array_map(fn ($value) => ['type' => 'text', 'text' => $value], array_values($params)),
                        ]],
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new CommunicationProviderException('Could not reach the WhatsApp provider.', previous: $e);
        }

        if ($response->failed()) {
            $message = $response->json('error.message') ?? "The WhatsApp provider returned an error (HTTP {$response->status()}).";
            throw new CommunicationProviderException($message);
        }

        $messageId = $response->json('messages.0.id');

        if (! is_string($messageId)) {
            throw new CommunicationProviderException('The WhatsApp provider returned an unexpected response.');
        }

        return new WhatsAppSendResult(messageId: $messageId, status: 'sent');
    }
}
