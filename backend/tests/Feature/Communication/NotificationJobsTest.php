<?php

namespace Tests\Feature\Communication;

use App\Jobs\SendEmailNotificationJob;
use App\Jobs\SendPushNotificationJob;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Mail\GymNotificationMail;
use App\Models\NotificationDelivery;
use App\Models\Tenant;
use App\Services\Communication\Exceptions\CommunicationProviderException;
use App\Services\NotificationDeliveryService;
use App\Support\NotificationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\InteractsWithCommunication;
use Tests\TestCase;

class NotificationJobsTest extends TestCase
{
    use InteractsWithCommunication, RefreshDatabase;

    public function test_the_email_job_sends_mail_and_records_a_sent_delivery(): void
    {
        Mail::fake();
        $tenant = Tenant::default();

        (new SendEmailNotificationJob($tenant->id, null, 'member@example.com', 'Welcome', 'Hello there', NotificationType::NEW_MEMBER))
            ->handle(app(NotificationDeliveryService::class));

        Mail::assertSent(GymNotificationMail::class, fn ($mail) => $mail->hasTo('member@example.com'));
        $this->assertDatabaseHas('notification_deliveries', [
            'tenant_id' => $tenant->id, 'channel' => 'email', 'recipient' => 'member@example.com', 'status' => 'Sent',
        ]);
    }

    public function test_the_whatsapp_job_calls_the_provider_and_records_a_sent_delivery(): void
    {
        $fake = $this->bindFakeWhatsAppProvider();
        $tenant = Tenant::default();

        (new SendWhatsAppNotificationJob($tenant->id, null, '+15551234567', 'Reminder', 'Your class starts soon', NotificationType::CLASS_REMINDER))
            ->handle($fake, app(NotificationDeliveryService::class));

        $this->assertCount(1, $fake->calls);
        $this->assertSame('+15551234567', $fake->calls[0]['to']);
        $this->assertDatabaseHas('notification_deliveries', [
            'tenant_id' => $tenant->id, 'channel' => 'whatsapp', 'recipient' => '+15551234567', 'status' => 'Sent',
        ]);
    }

    public function test_the_push_job_calls_the_provider_and_records_a_sent_delivery(): void
    {
        $fake = $this->bindFakePushProvider();
        $tenant = Tenant::default();

        (new SendPushNotificationJob($tenant->id, null, 'device-abc', 'Reminder', 'Your class starts soon', NotificationType::CLASS_REMINDER))
            ->handle($fake, app(NotificationDeliveryService::class));

        $this->assertCount(1, $fake->calls);
        $this->assertDatabaseHas('notification_deliveries', [
            'tenant_id' => $tenant->id, 'channel' => 'push', 'recipient' => 'device-abc', 'status' => 'Sent',
        ]);
    }

    public function test_a_provider_failure_is_recorded_via_the_jobs_failed_hook(): void
    {
        $tenant = Tenant::default();
        $job = new SendWhatsAppNotificationJob($tenant->id, null, '+15551234567', 'Reminder', 'Text', NotificationType::CLASS_REMINDER);

        // The job's own failed() looks up the most recent delivery row for
        // this recipient/type — simulate handle() having already logged one.
        NotificationDelivery::create([
            'tenant_id' => $tenant->id, 'type' => NotificationType::CLASS_REMINDER, 'channel' => 'whatsapp',
            'recipient' => '+15551234567', 'status' => 'Pending',
        ]);

        $job->failed(new CommunicationProviderException('The WhatsApp provider is not configured.'));

        $this->assertDatabaseHas('notification_deliveries', [
            'tenant_id' => $tenant->id, 'channel' => 'whatsapp', 'recipient' => '+15551234567', 'status' => 'Failed',
        ]);
        $delivery = NotificationDelivery::where('tenant_id', $tenant->id)->first();
        $this->assertStringContainsString('not configured', $delivery->error);
    }

    public function test_the_whatsapp_job_lets_a_provider_exception_propagate_so_the_queue_can_retry(): void
    {
        $fake = $this->bindFakeWhatsAppProvider(new CommunicationProviderException('Could not reach the WhatsApp provider.'));
        $tenant = Tenant::default();
        $job = new SendWhatsAppNotificationJob($tenant->id, null, '+15551234567', 'Reminder', 'Text', NotificationType::CLASS_REMINDER);

        $this->expectException(CommunicationProviderException::class);
        $job->handle($fake, app(NotificationDeliveryService::class));
    }
}
