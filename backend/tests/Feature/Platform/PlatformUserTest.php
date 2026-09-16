<?php

namespace Tests\Feature\Platform;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_platform_admin_can_list_only_platform_admins(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        User::factory()->platformAdmin()->create();
        User::factory()->create(['tenant_id' => Tenant::default()->id]); // regular tenant staff, must not appear

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/users');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_a_platform_admin_can_create_another_platform_admin_without_leaking_the_password(): void
    {
        $admin = User::factory()->platformAdmin()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/platform/users', [
            'name' => 'New Platform Admin',
            'email' => 'newadmin@platform.test',
            'password' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'newadmin@platform.test')
            ->assertJsonMissingPath('data.password');
        $this->assertDatabaseHas('users', ['email' => 'newadmin@platform.test', 'is_platform_admin' => true, 'tenant_id' => null]);
    }

    public function test_a_platform_admin_can_deactivate_another_platform_admin(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $other = User::factory()->platformAdmin()->create(['status' => 'Active']);

        $response = $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/platform/users/{$other->id}/status", ['status' => 'Inactive']);

        $response->assertOk()->assertJsonPath('data.status', 'Inactive');
    }

    public function test_a_platform_admin_cannot_deactivate_their_own_account(): void
    {
        $admin = User::factory()->platformAdmin()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/platform/users/{$admin->id}/status", ['status' => 'Inactive'])
            ->assertStatus(422);
    }

    public function test_a_regular_tenant_admin_cannot_manage_platform_users(): void
    {
        $tenant = Tenant::default();
        $admin = User::factory()->create(['role' => 'Super Admin', 'tenant_id' => $tenant->id]);

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/users')->assertStatus(403);
    }
}
