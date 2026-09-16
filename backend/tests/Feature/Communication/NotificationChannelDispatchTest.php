<?php

namespace Tests\Feature\Communication;

use App\Jobs\SendEmailNotificationJob;
use App\Jobs\SendPushNotificationJob;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\DeviceToken;
use App\Models\GymSetting;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\User;
use App\Services\NotificationPreferenceService;
use App\Services\NotificationService;
use App\Support\NotificationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\InteractsWithCommunication;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class NotificationChannelDispatchTest extends TestCase
{
    use InteractsWithCommunication, InteractsWithPermissions, RefreshDatabase;

    public function test_email_is_queued_for_every_active_recipient_with_an_email_address(): void
    {
        Queue::fake();
        $tenant = Tenant::default();
        $staff = User::factory()->create(['tenant_id' => $tenant->id, 'status' => 'Active', 'email' => 'staff@example.com']);
        $member = Member::factory()->create(['tenant_id' => $tenant->id]);

        app(NotificationService::class)->newMember($member);

        Queue::assertPushed(SendEmailNotificationJob::class, fn ($job) => $this->jobProperty($job, 'email') === $staff->email);
    }

    public function test_whatsapp_is_not_queued_when_the_platform_provider_is_unconfigured(): void
    {
        Queue::fake();
        $tenant = Tenant::default();
        GymSetting::factory()->create(['tenant_id' => $tenant->id, 'notify_whatsapp' => true]);
        User::factory()->create(['tenant_id' => $tenant->id, 'status' => 'Active', 'phone' => '+15551234567']);
        $member = Member::factory()->create(['tenant_id' => $tenant->id]);

        app(NotificationService::class)->newMember($member);

        Queue::assertNotPushed(SendWhatsAppNotificationJob::class);
    }

    public function test_whatsapp_is_queued_when_configured_and_the_tenant_has_it_enabled(): void
    {
        Queue::fake();
        $this->enableWhatsAppPlatformConfig();
        $tenant = Tenant::default();
        GymSetting::factory()->create(['tenant_id' => $tenant->id, 'notify_whatsapp' => true]);
        $staff = User::factory()->create(['tenant_id' => $tenant->id, 'status' => 'Active', 'phone' => '+15551234567']);
        $member = Member::factory()->create(['tenant_id' => $tenant->id]);

        app(NotificationService::class)->newMember($member);

        Queue::assertPushed(SendWhatsAppNotificationJob::class, fn ($job) => $this->jobProperty($job, 'phone') === $staff->phone);
    }

    public function test_push_is_queued_once_per_registered_device_token(): void
    {
        Queue::fake();
        $this->enablePushPlatformConfig();
        $tenant = Tenant::default();
        GymSetting::factory()->create(['tenant_id' => $tenant->id, 'notify_push' => true]);
        $staff = User::factory()->create(['tenant_id' => $tenant->id, 'status' => 'Active']);
        DeviceToken::create(['tenant_id' => $tenant->id, 'user_id' => $staff->id, 'token' => 'device-token-1', 'platform' => 'android']);
        DeviceToken::create(['tenant_id' => $tenant->id, 'user_id' => $staff->id, 'token' => 'device-token-2', 'platform' => 'ios']);
        $member = Member::factory()->create(['tenant_id' => $tenant->id]);

        app(NotificationService::class)->newMember($member);

        Queue::assertPushed(SendPushNotificationJob::class, 2);
    }

    public function test_a_disabled_channel_preference_is_respected(): void
    {
        Queue::fake();
        $tenant = Tenant::default();
        GymSetting::factory()->create(['tenant_id' => $tenant->id, 'notify_email' => false]);
        User::factory()->create(['tenant_id' => $tenant->id, 'status' => 'Active', 'email' => 'staff@example.com']);
        $member = Member::factory()->create(['tenant_id' => $tenant->id]);

        app(NotificationService::class)->newMember($member);

        Queue::assertNotPushed(SendEmailNotificationJob::class);
    }

    public function test_a_type_level_override_wins_over_the_tenant_default(): void
    {
        Queue::fake();
        $tenant = Tenant::default();
        GymSetting::factory()->create(['tenant_id' => $tenant->id, 'notify_email' => true]);
        User::factory()->create(['tenant_id' => $tenant->id, 'status' => 'Active', 'email' => 'staff@example.com']);
        app(NotificationPreferenceService::class)->setTypePreference($tenant->id, NotificationType::NEW_MEMBER, 'email', false);
        $member = Member::factory()->create(['tenant_id' => $tenant->id]);

        app(NotificationService::class)->newMember($member);

        Queue::assertNotPushed(SendEmailNotificationJob::class);
    }

    public function test_a_tenants_recipients_never_receive_another_tenants_notification(): void
    {
        Queue::fake();
        $tenantA = Tenant::default();
        $tenantB = $this->otherTenant();
        User::factory()->create(['tenant_id' => $tenantA->id, 'status' => 'Active', 'email' => 'a@example.com']);
        User::factory()->create(['tenant_id' => $tenantB->id, 'status' => 'Active', 'email' => 'b@example.com']);
        $memberA = Member::factory()->create(['tenant_id' => $tenantA->id]);

        app(NotificationService::class)->newMember($memberA);

        Queue::assertPushed(SendEmailNotificationJob::class, fn ($job) => $this->jobProperty($job, 'email') === 'a@example.com');
        Queue::assertNotPushed(SendEmailNotificationJob::class, fn ($job) => $this->jobProperty($job, 'email') === 'b@example.com');
    }

    private function jobProperty(object $job, string $property): mixed
    {
        $reflection = new \ReflectionProperty($job, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($job);
    }
}
