<?php

namespace Tests\Feature\Communication;

use App\Models\GymSetting;
use App\Models\NotificationTypePreference;
use App\Models\RolePermission;
use App\Models\Tenant;
use App\Models\User;
use App\Support\NotificationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_returns_the_tenants_channel_defaults_and_overrides(): void
    {
        $tenant = Tenant::default();
        GymSetting::factory()->create(['tenant_id' => $tenant->id, 'notify_email' => true, 'notify_push' => false, 'notify_whatsapp' => false]);
        $user = $this->userWithFullAccess(['Settings'], tenantId: $tenant->id);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/communication/preferences');

        $response->assertOk()
            ->assertJsonPath('data.channels.email', true)
            ->assertJsonPath('data.channels.push', false)
            ->assertJsonPath('data.overrides', []);
    }

    public function test_it_creates_a_type_level_override(): void
    {
        $tenant = Tenant::default();
        GymSetting::factory()->create(['tenant_id' => $tenant->id, 'notify_email' => true]);
        $user = $this->userWithFullAccess(['Settings'], tenantId: $tenant->id);

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/v1/communication/preferences', [
            'type' => NotificationType::MEMBERSHIP_EXPIRING,
            'channel' => 'email',
            'enabled' => false,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('notification_type_preferences', [
            'tenant_id' => $tenant->id,
            'type' => NotificationType::MEMBERSHIP_EXPIRING,
            'channel' => 'email',
            'enabled' => false,
        ]);
    }

    public function test_updating_the_same_type_and_channel_again_overwrites_rather_than_duplicates(): void
    {
        $tenant = Tenant::default();
        $user = $this->userWithFullAccess(['Settings'], tenantId: $tenant->id);

        $this->actingAs($user, 'sanctum')->putJson('/api/v1/communication/preferences', [
            'type' => NotificationType::PAYMENT_RECEIVED, 'channel' => 'push', 'enabled' => false,
        ])->assertOk();
        $this->actingAs($user, 'sanctum')->putJson('/api/v1/communication/preferences', [
            'type' => NotificationType::PAYMENT_RECEIVED, 'channel' => 'push', 'enabled' => true,
        ])->assertOk();

        $this->assertSame(1, NotificationTypePreference::where('tenant_id', $tenant->id)->count());
        $this->assertDatabaseHas('notification_type_preferences', ['tenant_id' => $tenant->id, 'enabled' => true]);
    }

    public function test_it_rejects_an_unknown_notification_type(): void
    {
        $user = $this->userWithFullAccess(['Settings']);

        $this->actingAs($user, 'sanctum')->putJson('/api/v1/communication/preferences', [
            'type' => 'Not A Real Type', 'channel' => 'email', 'enabled' => true,
        ])->assertStatus(422);
    }

    public function test_a_tenants_override_never_leaks_to_another_tenant(): void
    {
        $tenantA = Tenant::default();
        $tenantB = $this->otherTenant();
        $adminA = $this->userWithFullAccess(['Settings'], tenantId: $tenantA->id);
        $adminB = User::factory()->create(['tenant_id' => $tenantB->id, 'role' => $adminA->role]);

        $this->actingAs($adminA, 'sanctum')->putJson('/api/v1/communication/preferences', [
            'type' => NotificationType::NEW_MEMBER, 'channel' => 'email', 'enabled' => false,
        ])->assertOk();

        $response = $this->actingAs($adminB, 'sanctum')->getJson('/api/v1/communication/preferences');

        $response->assertOk()->assertJsonPath('data.overrides', []);
    }

    public function test_a_role_without_settings_permission_is_forbidden(): void
    {
        RolePermission::factory()->create([
            'role' => 'Trainer', 'module' => 'Settings',
            'can_view' => false, 'can_create' => false, 'can_edit' => false, 'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Trainer']);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/communication/preferences')->assertStatus(403);
    }

    public function test_guest_cannot_access_preferences(): void
    {
        $this->getJson('/api/v1/communication/preferences')->assertStatus(401);
    }
}
