<?php

namespace Tests\Feature\Member;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMemberAuth;
use Tests\TestCase;

/**
 * Proves EnsureMemberToken actually separates the two actor types —
 * without it, any authenticated Sanctum token (staff or member) could
 * reach either route surface, since both User and Member satisfy the
 * same Authenticatable contract auth:sanctum checks.
 */
class CrossActorGuardTest extends TestCase
{
    use InteractsWithMemberAuth, RefreshDatabase;

    public function test_a_staff_token_cannot_access_member_only_routes(): void
    {
        $tenant = Tenant::default();
        $staff = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'Super Admin']);

        $this->actingAs($staff, 'sanctum')->getJson('/api/v1/member/dashboard')->assertStatus(403);
    }

    /** Denied cleanly (403 from CheckPermission's defensive check), not a 500 crash — see Member::hasPermission(). */
    public function test_a_member_token_cannot_access_staff_only_routes(): void
    {
        $member = $this->memberWithPassword();

        $this->actingAs($member, 'sanctum')->getJson('/api/v1/members')->assertStatus(403);
    }

    /** Denied cleanly (403 from EnsurePlatformAdmin's defensive check), not a 500 crash — see Member::isPlatformAdmin(). */
    public function test_a_member_token_cannot_access_platform_routes(): void
    {
        $member = $this->memberWithPassword();

        $this->actingAs($member, 'sanctum')->getJson('/api/v1/platform/dashboard/summary')->assertStatus(403);
    }
}
