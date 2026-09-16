<?php

namespace Tests\Concerns;

use App\Services\Communication\Contracts\PushProviderContract;
use App\Services\Communication\Contracts\WhatsAppProviderContract;
use App\Services\Communication\Exceptions\CommunicationProviderException;
use Tests\Fakes\FakePushProvider;
use Tests\Fakes\FakeWhatsAppProvider;

trait InteractsWithCommunication
{
    protected function bindFakeWhatsAppProvider(?CommunicationProviderException $throw = null): FakeWhatsAppProvider
    {
        $fake = new FakeWhatsAppProvider($throw);
        $this->app->instance(WhatsAppProviderContract::class, $fake);

        return $fake;
    }

    protected function bindFakePushProvider(?CommunicationProviderException $throw = null): FakePushProvider
    {
        $fake = new FakePushProvider($throw);
        $this->app->instance(PushProviderContract::class, $fake);

        return $fake;
    }

    /** NotificationChannelDispatcher only queues WhatsApp/push jobs when the platform has credentials configured — see its docblock. */
    protected function enableWhatsAppPlatformConfig(): void
    {
        config([
            'communication.whatsapp.whatsapp_cloud_api.access_token' => 'test-whatsapp-token',
            'communication.whatsapp.whatsapp_cloud_api.phone_number_id' => 'test-phone-id',
        ]);
    }

    protected function enablePushPlatformConfig(): void
    {
        config([
            'communication.push.fcm.access_token' => 'test-fcm-token',
            'communication.push.fcm.project_id' => 'test-project',
        ]);
    }
}
