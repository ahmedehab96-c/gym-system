<?php

namespace Tests\Feature\Subscriptions;

use App\Models\Member;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class SubscriptionLimitTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_usage_endpoint_reports_used_and_limit_per_resource(): void
    {
        $tenant = Tenant::default();
        $plan = SubscriptionPlan::factory()->create(['limits' => ['max_members' => 5, 'max_staff' => 2, 'max_trainers' => 1, 'max_classes' => 3]]);
        TenantSubscription::factory()->active()->create(['tenant_id' => $tenant->id, 'plan_id' => $plan->id]);
        Member::factory()->count(3)->create(['tenant_id' => $tenant->id]);
        $admin = $this->userWithFullAccess(['Members'], 'Super Admin', $tenant->id);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/subscription/usage');

        $response->assertOk()
            ->assertJsonPath('data.members.used', 3)
            ->assertJsonPath('data.members.limit', 5)
            ->assertJsonPath('data.staff.limit', 2)
            ->assertJsonPath('data.trainers.limit', 1)
            ->assertJsonPath('data.classes.limit', 3);
    }

    public function test_creating_a_member_is_blocked_once_the_plan_limit_is_reached(): void
    {
        $tenant = Tenant::default();
        $plan = SubscriptionPlan::factory()->create(['limits' => ['max_members' => 1]]);
        TenantSubscription::factory()->active()->create(['tenant_id' => $tenant->id, 'plan_id' => $plan->id]);
        Member::factory()->create(['tenant_id' => $tenant->id]);
        $admin = $this->userWithFullAccess(['Members'], 'Super Admin', $tenant->id);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/members', [
            'name' => 'Over Limit Member',
            'email' => 'overlimit@example.test',
            'phone' => '+20 100 000 0001',
            'gender' => 'Male',
        ]);

        $response->assertStatus(402);
        $this->assertDatabaseMissing('members', ['email' => 'overlimit@example.test']);
    }

    public function test_creating_a_member_is_allowed_when_under_the_plan_limit(): void
    {
        $tenant = Tenant::default();
        $plan = SubscriptionPlan::factory()->create(['limits' => ['max_members' => 10]]);
        TenantSubscription::factory()->active()->create(['tenant_id' => $tenant->id, 'plan_id' => $plan->id]);
        $admin = $this->userWithFullAccess(['Members'], 'Super Admin', $tenant->id);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/members', [
            'name' => 'Under Limit Member',
            'email' => 'underlimit@example.test',
            'phone' => '+20 100 000 0002',
            'gender' => 'Male',
        ]);

        $response->assertCreated();
    }

    public function test_an_unlimited_plan_never_blocks_creation(): void
    {
        $tenant = Tenant::default();
        $plan = SubscriptionPlan::factory()->unlimited()->create();
        TenantSubscription::factory()->active()->create(['tenant_id' => $tenant->id, 'plan_id' => $plan->id]);
        Member::factory()->count(50)->create(['tenant_id' => $tenant->id]);
        $admin = $this->userWithFullAccess(['Members'], 'Super Admin', $tenant->id);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/members', [
            'name' => 'Unlimited Plan Member',
            'email' => 'unlimited@example.test',
            'phone' => '+20 100 000 0003',
            'gender' => 'Male',
        ]);

        $response->assertCreated();
    }

    public function test_no_subscription_plan_configured_at_all_does_not_block_creation(): void
    {
        // No SubscriptionPlan exists anywhere — the billing subsystem
        // being unconfigured must never block core gym functionality.
        $tenant = Tenant::default();
        $admin = $this->userWithFullAccess(['Members'], 'Super Admin', $tenant->id);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/members', [
            'name' => 'No Plan Configured Member',
            'email' => 'noplan@example.test',
            'phone' => '+20 100 000 0004',
            'gender' => 'Male',
        ]);

        $response->assertCreated();
    }
}
