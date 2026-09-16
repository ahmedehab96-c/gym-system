<?php

namespace Tests\Feature\Platform;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformCommunicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_platform_admin_can_view_provider_configuration_status(): void
    {
        config([
            'communication.whatsapp.whatsapp_cloud_api.access_token' => 'real-secret-token-value',
            'communication.whatsapp.whatsapp_cloud_api.phone_number_id' => '12345',
        ]);
        $admin = User::factory()->platformAdmin()->create();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/communication/status');

        $response->assertOk()
            ->assertJsonPath('data.whatsapp.configured', true)
            ->assertJsonPath('data.push.configured', false);
    }

    public function test_it_never_returns_the_raw_secret_value(): void
    {
        config(['communication.whatsapp.whatsapp_cloud_api.access_token' => 'super-secret-value-12345']);
        $admin = User::factory()->platformAdmin()->create();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/communication/status');

        $response->assertOk();
        $this->assertStringNotContainsString('super-secret-value-12345', $response->getContent());
    }

    public function test_a_regular_tenant_admin_cannot_view_platform_communication_status(): void
    {
        $tenant = Tenant::default();
        $admin = User::factory()->create(['role' => 'Super Admin', 'tenant_id' => $tenant->id]);

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/communication/status')->assertStatus(403);
    }

    public function test_guest_cannot_view_platform_communication_status(): void
    {
        $this->getJson('/api/v1/platform/communication/status')->assertStatus(401);
    }
}
