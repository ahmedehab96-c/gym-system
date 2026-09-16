<?php

namespace Tests\Feature\Communication;

use App\Services\Communication\Exceptions\CommunicationProviderException;
use App\Services\Communication\Providers\FcmProvider;
use App\Services\Communication\Providers\WhatsAppCloudApiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_provider_sends_a_template_message(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.HBg']]], 200)]);

        $provider = new WhatsAppCloudApiProvider([
            'phone_number_id' => '123', 'access_token' => 'test-token', 'api_version' => 'v20.0',
            'base_url' => 'https://graph.facebook.com', 'timeout' => 15,
        ]);

        $result = $provider->sendTemplate('+15551234567', 'membership_expiry_reminder', ['name' => 'Alex']);

        $this->assertSame('wamid.HBg', $result->messageId);
        Http::assertSent(fn ($request) => $request->url() === 'https://graph.facebook.com/v20.0/123/messages'
            && $request['messaging_product'] === 'whatsapp'
            && $request['template']['name'] === 'membership_expiry_reminder');
    }

    public function test_whatsapp_provider_throws_when_unconfigured(): void
    {
        $provider = new WhatsAppCloudApiProvider(['phone_number_id' => null, 'access_token' => null, 'api_version' => 'v20.0', 'base_url' => 'https://graph.facebook.com', 'timeout' => 15]);

        $this->expectException(CommunicationProviderException::class);
        $provider->sendTemplate('+15551234567', 'x', []);
    }

    public function test_whatsapp_provider_throws_on_a_failed_response(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid template name']], 400)]);

        $provider = new WhatsAppCloudApiProvider([
            'phone_number_id' => '123', 'access_token' => 'test-token', 'api_version' => 'v20.0',
            'base_url' => 'https://graph.facebook.com', 'timeout' => 15,
        ]);

        $this->expectException(CommunicationProviderException::class);
        $this->expectExceptionMessage('Invalid template name');
        $provider->sendTemplate('+15551234567', 'bad_template', []);
    }

    public function test_fcm_provider_sends_a_push_message(): void
    {
        Http::fake(['fcm.googleapis.com/*' => Http::response(['name' => 'projects/x/messages/1'], 200)]);

        $provider = new FcmProvider(['project_id' => 'test-project', 'access_token' => 'test-token', 'base_url' => 'https://fcm.googleapis.com/v1', 'timeout' => 15]);

        $result = $provider->send('device-token', 'Title', 'Body', ['type' => 'Class Reminder']);

        $this->assertSame('projects/x/messages/1', $result->messageId);
        Http::assertSent(fn ($request) => $request->url() === 'https://fcm.googleapis.com/v1/projects/test-project/messages:send'
            && $request['message']['token'] === 'device-token');
    }

    public function test_fcm_provider_throws_when_unconfigured(): void
    {
        $provider = new FcmProvider(['project_id' => null, 'access_token' => null, 'base_url' => 'https://fcm.googleapis.com/v1', 'timeout' => 15]);

        $this->expectException(CommunicationProviderException::class);
        $provider->send('device-token', 'Title', 'Body');
    }
}
