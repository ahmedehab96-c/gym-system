<?php

namespace Tests\Feature\Members;

use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class MembershipTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_lists_membership_history_for_a_member(): void
    {
        $user = $this->userWithFullAccess(['Members', 'Memberships']);
        $member = Member::factory()->create();
        Membership::factory()->count(2)->create(['member_id' => $member->id]);
        Membership::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/memberships?member_id={$member->id}");

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_it_creates_a_membership_and_syncs_the_member(): void
    {
        $user = $this->userWithFullAccess(['Members', 'Memberships']);
        $member = Member::factory()->create();
        $plan = MembershipPlan::factory()->create(['duration_days' => 30, 'price' => 950]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/memberships', [
            'member_id' => $member->id,
            'plan_id' => $plan->id,
        ]);

        $response->assertCreated()->assertJsonPath('data.price', 950);
        $this->assertDatabaseHas('members', ['id' => $member->id, 'plan_id' => $plan->id]);
    }

    public function test_it_rejects_creating_a_membership_for_a_member_with_an_active_one(): void
    {
        $user = $this->userWithFullAccess(['Members', 'Memberships']);
        $member = Member::factory()->create();
        Membership::factory()->create(['member_id' => $member->id, 'status' => 'Active']);
        $plan = MembershipPlan::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/memberships', [
            'member_id' => $member->id,
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(422);
        $this->assertSame(1, Membership::where('member_id', $member->id)->count());
    }

    public function test_it_renews_a_membership(): void
    {
        $user = $this->userWithFullAccess(['Members', 'Memberships']);
        $plan = MembershipPlan::factory()->create(['duration_days' => 30]);
        $member = Member::factory()->create(['plan_id' => $plan->id]);
        $membership = Membership::factory()->create([
            'member_id' => $member->id,
            'plan_id' => $plan->id,
            'expiry_date' => Carbon::today()->subDays(3)->toDateString(),
            'status' => 'Expired',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/memberships/{$membership->id}/renew");

        $response->assertOk()->assertJsonPath('data.status', 'Active');
        $this->assertTrue(Carbon::parse($response->json('data.expiryDate'))->isFuture());
    }

    public function test_it_changes_a_membership_plan(): void
    {
        $user = $this->userWithFullAccess(['Members', 'Memberships']);
        $oldPlan = MembershipPlan::factory()->create(['price' => 350]);
        $newPlan = MembershipPlan::factory()->create(['price' => 1500, 'duration_days' => 30]);
        $member = Member::factory()->create(['plan_id' => $oldPlan->id]);
        $membership = Membership::factory()->create(['member_id' => $member->id, 'plan_id' => $oldPlan->id]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/memberships/{$membership->id}/change-plan", [
            'plan_id' => $newPlan->id,
        ]);

        $response->assertOk()->assertJsonPath('data.planId', $newPlan->id)->assertJsonPath('data.price', 1500);
        $this->assertDatabaseHas('members', ['id' => $member->id, 'plan_id' => $newPlan->id]);
    }

    public function test_it_suspends_a_membership_and_the_member(): void
    {
        $user = $this->userWithFullAccess(['Members', 'Memberships']);
        $member = Member::factory()->create(['status' => 'Active']);
        $membership = Membership::factory()->create(['member_id' => $member->id, 'status' => 'Active']);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/memberships/{$membership->id}/suspend");

        $response->assertOk()->assertJsonPath('data.status', 'Suspended');
        $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Suspended']);
    }

    public function test_it_cancels_a_membership_and_deactivates_the_member(): void
    {
        $user = $this->userWithFullAccess(['Members', 'Memberships']);
        $member = Member::factory()->create(['status' => 'Active']);
        $membership = Membership::factory()->create(['member_id' => $member->id, 'status' => 'Active']);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/memberships/{$membership->id}/cancel");

        $response->assertOk()->assertJsonPath('data.status', 'Expired');
        $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Inactive']);
    }

    public function test_a_role_with_only_view_permission_cannot_renew(): void
    {
        RolePermission::factory()->create([
            'role' => 'Receptionist',
            'module' => 'Memberships',
            'can_view' => true,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Receptionist']);
        $membership = Membership::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/memberships/{$membership->id}/renew");

        $response->assertStatus(403);
    }
}
