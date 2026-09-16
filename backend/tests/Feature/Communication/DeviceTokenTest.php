<?php

namespace Tests\Feature\Communication;

use App\Models\DeviceToken;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_register_a_device_token(): void
    {
        $tenant = Tenant::default();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/device-tokens', [
            'token' => 'expo-push-token-123',
            'platform' => 'android',
        ]);

        $response->assertCreated()->assertJsonPath('data.platform', 'android');
        $this->assertDatabaseHas('device_tokens', ['tenant_id' => $tenant->id, 'user_id' => $user->id, 'token' => 'expo-push-token-123']);
        // The raw token itself is never echoed back in the resource.
        $response->assertJsonMissingPath('data.token');
    }

    public function test_re_registering_the_same_token_reassigns_it_instead_of_erroring(): void
    {
        $tenant = Tenant::default();
        $firstUser = User::factory()->create(['tenant_id' => $tenant->id]);
        $secondUser = User::factory()->create(['tenant_id' => $tenant->id]);
        DeviceToken::create(['tenant_id' => $tenant->id, 'user_id' => $firstUser->id, 'token' => 'shared-device', 'platform' => 'ios']);

        $this->actingAs($secondUser, 'sanctum')->postJson('/api/v1/device-tokens', [
            'token' => 'shared-device', 'platform' => 'ios',
        ])->assertCreated();

        $this->assertSame(1, DeviceToken::where('token', 'shared-device')->count());
        $this->assertDatabaseHas('device_tokens', ['token' => 'shared-device', 'user_id' => $secondUser->id]);
    }

    public function test_a_user_can_delete_their_own_device_token(): void
    {
        $tenant = Tenant::default();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $token = DeviceToken::create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'token' => 'to-remove', 'platform' => 'web']);

        $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/device-tokens/{$token->id}")->assertOk();

        $this->assertDatabaseMissing('device_tokens', ['id' => $token->id]);
    }

    public function test_a_user_cannot_delete_someone_elses_device_token(): void
    {
        $tenant = Tenant::default();
        $owner = User::factory()->create(['tenant_id' => $tenant->id]);
        $other = User::factory()->create(['tenant_id' => $tenant->id]);
        $token = DeviceToken::create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'token' => 'owned-by-owner', 'platform' => 'web']);

        $this->actingAs($other, 'sanctum')->deleteJson("/api/v1/device-tokens/{$token->id}")->assertStatus(403);
        $this->assertDatabaseHas('device_tokens', ['id' => $token->id]);
    }

    public function test_a_user_cannot_delete_another_tenants_device_token(): void
    {
        $tenantA = Tenant::default();
        $tenantB = Tenant::factory()->create();
        $ownerB = User::factory()->create(['tenant_id' => $tenantB->id]);
        $tokenB = DeviceToken::create(['tenant_id' => $tenantB->id, 'user_id' => $ownerB->id, 'token' => 'tenant-b-device', 'platform' => 'web']);
        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);

        $this->actingAs($userA, 'sanctum')->deleteJson("/api/v1/device-tokens/{$tokenB->id}")->assertStatus(404);
    }

    public function test_guest_cannot_register_a_device_token(): void
    {
        $this->postJson('/api/v1/device-tokens', ['token' => 'x', 'platform' => 'ios'])->assertStatus(401);
    }
}
