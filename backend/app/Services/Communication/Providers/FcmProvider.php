<?php

namespace App\Services\Communication\Providers;

use App\Services\Communication\Contracts\PushProviderContract;
use App\Services\Communication\DTO\PushSendResult;
use App\Services\Communication\Exceptions\CommunicationProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Firebase Cloud Messaging's HTTP v1 API — prepares push delivery for a
 * future Flutter/mobile client (Phase 24 §3); no app exists yet to
 * receive these. See config/communication.php for why FCM_ACCESS_TOKEN
 * is a pre-minted bearer token rather than something this class
 * refreshes itself.
 */
class FcmProvider implements PushProviderContract
{
    /**
     * @param  array{project_id: ?string, access_token: ?string, base_url: string, timeout: int}  $config
     */
    public function __construct(private readonly array $config) {}

    public function send(string $deviceToken, string $title, string $body, array $data = []): PushSendResult
    {
        $accessToken = $this->config['access_token'] ?? null;
        $projectId = $this->config['project_id'] ?? null;

        if (! $accessToken || ! $projectId) {
            throw new CommunicationProviderException('The push provider is not configured. Set FCM_ACCESS_TOKEN and FCM_PROJECT_ID.');
        }

        $baseUrl = rtrim($this->config['base_url'] ?? 'https://fcm.googleapis.com/v1', '/');

        try {
            $response = Http::withToken($accessToken)
                ->timeout($this->config['timeout'] ?? 15)
                ->post("{$baseUrl}/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $deviceToken,
                        'notification' => ['title' => $title, 'body' => $body],
                        'data' => (object) $data,
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new CommunicationProviderException('Could not reach the push provider.', previous: $e);
        }

        if ($response->failed()) {
            $message = $response->json('error.message') ?? "The push provider returned an error (HTTP {$response->status()}).";
            throw new CommunicationProviderException($message);
        }

        $messageId = $response->json('name');

        if (! is_string($messageId)) {
            throw new CommunicationProviderException('The push provider returned an unexpected response.');
        }

        return new PushSendResult(messageId: $messageId, status: 'sent');
    }
}
