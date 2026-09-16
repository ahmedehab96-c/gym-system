<?php

namespace Tests\Feature\Member;

use App\Models\Member;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMemberAuth;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class MemberAuthTest extends TestCase
{
    use InteractsWithMemberAuth, InteractsWithPermissions, RefreshDatabase;

    public function test_a_member_can_log_in_with_correct_credentials(): void
    {
        $member = $this->memberWithPassword('correct-password', attributes: ['email' => 'member@example.com']);

        $response = $this->postJson('/api/v1/member/auth/login', [
            'email' => 'member@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['token', 'member' => ['id', 'name', 'email']]])
            ->assertJsonPath('data.member.email', 'member@example.com');
        $this->assertNotNull($member->fresh()->last_login_at);
    }

    public function test_login_fails_with_the_wrong_password(): void
    {
        $this->memberWithPassword('correct-password', attributes: ['email' => 'member@example.com']);

        $this->postJson('/api/v1/member/auth/login', [
            'email' => 'member@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }

    public function test_login_fails_for_a_member_with_no_password_set_yet(): void
    {
        Member::factory()->create(['email' => 'nopass@example.com', 'password' => null, 'status' => 'Active']);

        $this->postJson('/api/v1/member/auth/login', [
            'email' => 'nopass@example.com',
            'password' => 'anything',
        ])->assertStatus(422);
    }

    public function test_login_fails_for_an_inactive_member(): void
    {
        $this->memberWithPassword('correct-password', attributes: ['email' => 'inactive@example.com', 'status' => 'Suspended']);

        $this->postJson('/api/v1/member/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'correct-password',
        ])->assertStatus(422);
    }

    public function test_an_authenticated_member_can_fetch_their_own_profile_via_me(): void
    {
        $member = $this->memberWithPassword();

        $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $member->id);
    }

    public function test_a_member_can_log_out(): void
    {
        $member = $this->memberWithPassword();
        $token = $member->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/v1/member/auth/logout');

        $response->assertOk();
        $this->assertSame(0, $member->tokens()->count());
    }

    public function test_an_unauthenticated_request_to_a_protected_member_route_is_rejected(): void
    {
        $this->getJson('/api/v1/member/auth/me')->assertStatus(401);
    }

    public function test_staff_can_set_a_members_initial_password(): void
    {
        $tenant = Tenant::default();
        $admin = $this->userWithFullAccess(['Members'], tenantId: $tenant->id);
        $member = Member::factory()->create(['tenant_id' => $tenant->id, 'password' => null, 'status' => 'Active']);

        $response = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/members/{$member->id}/set-password", [
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ]);

        $response->assertOk();
        $this->assertNotNull($member->fresh()->password);

        $this->postJson('/api/v1/member/auth/login', [
            'email' => $member->email,
            'password' => 'new-secure-password',
        ])->assertOk();
    }
}
