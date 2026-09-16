<?php

namespace Tests\Feature\Platform;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformGymTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_platform_admin_can_list_gyms(): void
    {
        Tenant::factory()->count(3)->create();
        $admin = User::factory()->platformAdmin()->create();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/gyms');

        // Tenant::count() (not a literal 3) because migrations already
        // create one default tenant when backfilling users.tenant_id —
        // see Tenant::default()'s docblock.
        $response->assertOk()->assertJsonCount(Tenant::count(), 'data');
    }

    public function test_a_platform_admin_can_search_gyms_by_name(): void
    {
        Tenant::factory()->create(['name' => 'Iron Paradise Gym']);
        Tenant::factory()->create(['name' => 'Zen Yoga Studio']);
        $admin = User::factory()->platformAdmin()->create();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/gyms?search=Iron');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Iron Paradise Gym');
    }

    public function test_a_platform_admin_can_create_a_gym_with_an_owner_account(): void
    {
        $admin = User::factory()->platformAdmin()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/platform/gyms', [
            'name' => 'New Gym',
            'slug' => 'new-gym',
            'owner_name' => 'Jane Owner',
            'owner_email' => 'jane@newgym.test',
            'owner_password' => 'password123',
        ]);

        $response->assertCreated()->assertJsonPath('data.name', 'New Gym');
        $this->assertDatabaseHas('tenants', ['slug' => 'new-gym']);
        $this->assertDatabaseHas('users', ['email' => 'jane@newgym.test', 'role' => 'Super Admin']);
    }

    public function test_a_platform_admin_can_suspend_and_reactivate_a_gym(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $tenant = Tenant::factory()->create(['status' => 'Active']);

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/platform/gyms/{$tenant->id}/suspend")
            ->assertOk()->assertJsonPath('data.status', 'Suspended');
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $tenant->id, 'action' => 'gym.suspended']);

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/platform/gyms/{$tenant->id}/reactivate")
            ->assertOk()->assertJsonPath('data.status', 'Active');
    }

    public function test_gym_details_include_owner_and_usage(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'Super Admin']);

        $response = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/platform/gyms/{$tenant->id}");

        $response->assertOk()
            ->assertJsonPath('data.gym.owner.email', $owner->email)
            ->assertJsonPath('data.gym.usage.staff', 1);
    }

    public function test_a_regular_tenant_admin_cannot_manage_gyms(): void
    {
        $tenant = Tenant::default();
        $admin = User::factory()->create(['role' => 'Super Admin', 'tenant_id' => $tenant->id]);

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/gyms')->assertStatus(403);
    }
}
