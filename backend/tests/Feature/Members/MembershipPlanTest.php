<?php

namespace Tests\Feature\Members;

use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class MembershipPlanTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_lists_plans_with_member_counts(): void
    {
        $user = $this->userWithFullAccess(['Memberships']);
        $plan = MembershipPlan::factory()->create(['price' => 350]);
        Member::factory()->count(2)->create(['plan_id' => $plan->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/membership-plans');

        $response->assertOk();
        $found = collect($response->json('data'))->firstWhere('id', $plan->id);
        $this->assertSame(2, $found['memberCount']);
    }

    public function test_it_creates_a_plan(): void
    {
        $user = $this->userWithFullAccess(['Memberships']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/membership-plans', [
            'name' => 'Gold',
            'tagline' => 'Great value',
            'price' => 800,
            'duration_label' => 'Monthly',
            'duration_days' => 30,
            'features' => ['Gym access'],
            'color' => '#000000',
        ]);

        $response->assertCreated()->assertJsonPath('data.name', 'Gold');
        $this->assertDatabaseHas('membership_plans', ['name' => 'Gold', 'price' => 800]);
    }

    public function test_creating_a_plan_requires_valid_data(): void
    {
        $user = $this->userWithFullAccess(['Memberships']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/membership-plans', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['name', 'price', 'duration_label', 'duration_days']);
    }

    public function test_it_updates_a_plan(): void
    {
        $user = $this->userWithFullAccess(['Memberships']);
        $plan = MembershipPlan::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/membership-plans/{$plan->id}", [
            'price' => 999,
        ]);

        $response->assertOk()->assertJsonPath('data.price', 999);
    }

    public function test_it_deletes_a_plan_without_memberships(): void
    {
        $user = $this->userWithFullAccess(['Memberships']);
        $plan = MembershipPlan::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/membership-plans/{$plan->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('membership_plans', ['id' => $plan->id]);
    }

    public function test_it_refuses_to_delete_a_plan_with_memberships(): void
    {
        $user = $this->userWithFullAccess(['Memberships']);
        $plan = MembershipPlan::factory()->create();
        $member = Member::factory()->create(['plan_id' => $plan->id]);
        Membership::factory()->create(['member_id' => $member->id, 'plan_id' => $plan->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/membership-plans/{$plan->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('membership_plans', ['id' => $plan->id]);
    }

    public function test_a_role_without_permission_is_forbidden(): void
    {
        RolePermission::factory()->create([
            'role' => 'Trainer',
            'module' => 'Memberships',
            'can_view' => false,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Trainer']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/membership-plans');

        $response->assertStatus(403);
    }
}
