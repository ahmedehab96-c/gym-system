<?php

namespace Tests\Feature\Subscriptions;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformSubscriptionPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_platform_admin_can_list_plans(): void
    {
        SubscriptionPlan::factory()->count(3)->create();
        $admin = User::factory()->platformAdmin()->create();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/subscription-plans');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_a_platform_admin_can_create_a_plan(): void
    {
        $admin = User::factory()->platformAdmin()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/platform/subscription-plans', [
            'name' => 'Starter',
            'slug' => 'starter',
            'monthly_price' => 19,
            'yearly_price' => 190,
            'limits' => ['max_members' => 25, 'max_staff' => 2],
        ]);

        $response->assertCreated()->assertJsonPath('data.name', 'Starter');
        $this->assertDatabaseHas('subscription_plans', ['slug' => 'starter']);
    }

    public function test_a_platform_admin_can_update_a_plan(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $plan = SubscriptionPlan::factory()->create(['monthly_price' => 50]);

        $response = $this->actingAs($admin, 'sanctum')->putJson("/api/v1/platform/subscription-plans/{$plan->id}", [
            'monthly_price' => 75,
        ]);

        $response->assertOk()->assertJsonPath('data.monthlyPrice', 75);
    }

    public function test_a_platform_admin_cannot_delete_a_plan_with_active_subscriptions(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $plan = SubscriptionPlan::factory()->create();
        TenantSubscription::factory()->create(['tenant_id' => Tenant::factory()->create()->id, 'plan_id' => $plan->id]);

        $response = $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/platform/subscription-plans/{$plan->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('subscription_plans', ['id' => $plan->id]);
    }

    public function test_a_regular_tenant_admin_cannot_manage_platform_plans(): void
    {
        $tenant = Tenant::default();
        $admin = User::factory()->create(['role' => 'Super Admin', 'tenant_id' => $tenant->id]);

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/subscription-plans')->assertStatus(403);
        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/platform/subscription-plans', [])->assertStatus(403);
    }

    public function test_a_guest_cannot_access_platform_routes(): void
    {
        $this->getJson('/api/v1/platform/subscription-plans')->assertStatus(401);
    }
}
