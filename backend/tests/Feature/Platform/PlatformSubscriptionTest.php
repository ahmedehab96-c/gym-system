<?php

namespace Tests\Feature\Platform;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_platform_admin_can_list_every_tenants_subscription(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        TenantSubscription::factory()->count(2)->create(['tenant_id' => Tenant::factory()]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/subscriptions');

        $response->assertOk()->assertJsonCount(2, 'data');
        $this->assertNotNull($response->json('data.0.tenant.name'));
    }

    public function test_subscriptions_can_be_filtered_by_status(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        TenantSubscription::factory()->create(['tenant_id' => Tenant::factory(), 'status' => 'Trial']);
        TenantSubscription::factory()->active()->create(['tenant_id' => Tenant::factory()]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/subscriptions?status=Active');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.status', 'Active');
    }

    public function test_subscriptions_can_be_filtered_by_plan(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $plan = SubscriptionPlan::factory()->create();
        TenantSubscription::factory()->create(['tenant_id' => Tenant::factory(), 'plan_id' => $plan->id]);
        TenantSubscription::factory()->create(['tenant_id' => Tenant::factory()]);

        $response = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/platform/subscriptions?plan_id={$plan->id}");

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_a_regular_tenant_admin_cannot_view_platform_subscriptions(): void
    {
        $tenant = Tenant::default();
        $admin = User::factory()->create(['role' => 'Super Admin', 'tenant_id' => $tenant->id]);

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/subscriptions')->assertStatus(403);
    }
}
