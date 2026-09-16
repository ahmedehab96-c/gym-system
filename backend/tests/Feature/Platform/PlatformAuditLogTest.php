<?php

namespace Tests\Feature\Platform;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_gym_actions_are_recorded_and_listed_in_the_audit_log(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $tenant = Tenant::factory()->create(['status' => 'Active']);

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/platform/gyms/{$tenant->id}/suspend")->assertOk();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/audit-logs');

        $response->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'gym.suspended')
            ->assertJsonPath('data.0.actorName', $admin->name);
    }

    public function test_audit_logs_can_be_searched(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $tenant = Tenant::factory()->create(['name' => 'Findable Gym']);
        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/platform/gyms/{$tenant->id}/suspend")->assertOk();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/audit-logs?search=Findable');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_a_regular_tenant_admin_cannot_view_the_audit_log(): void
    {
        $tenant = Tenant::default();
        $admin = User::factory()->create(['role' => 'Super Admin', 'tenant_id' => $tenant->id]);

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/audit-logs')->assertStatus(403);
    }
}
