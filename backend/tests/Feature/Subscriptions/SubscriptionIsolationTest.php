<?php

namespace Tests\Feature\Subscriptions;

use App\Models\Member;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class SubscriptionIsolationTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_a_tenant_only_ever_sees_its_own_subscription(): void
    {
        $tenantA = Tenant::default();
        $tenantB = $this->otherTenant();
        TenantSubscription::factory()->active()->create(['tenant_id' => $tenantA->id]);
        TenantSubscription::factory()->active()->create(['tenant_id' => $tenantB->id]);

        $adminA = User::factory()->create(['role' => 'Super Admin', 'tenant_id' => $tenantA->id]);

        $response = $this->actingAs($adminA, 'sanctum')->getJson('/api/v1/subscription');

        $response->assertOk();
        $this->assertNotEquals(
            TenantSubscription::withoutGlobalScopes()->where('tenant_id', $tenantB->id)->first()->id,
            $response->json('data.id'),
        );
    }

    public function test_a_tenant_cannot_see_another_tenants_billing_invoices(): void
    {
        $tenantA = Tenant::default();
        $tenantB = $this->otherTenant();
        $subA = TenantSubscription::factory()->active()->create(['tenant_id' => $tenantA->id]);
        $subB = TenantSubscription::factory()->active()->create(['tenant_id' => $tenantB->id]);

        SubscriptionInvoice::factory()->create(['tenant_id' => $tenantA->id, 'tenant_subscription_id' => $subA->id, 'plan_id' => $subA->plan_id]);
        SubscriptionInvoice::factory()->count(3)->create(['tenant_id' => $tenantB->id, 'tenant_subscription_id' => $subB->id, 'plan_id' => $subB->plan_id]);

        $adminA = User::factory()->create(['role' => 'Super Admin', 'tenant_id' => $tenantA->id]);

        $response = $this->actingAs($adminA, 'sanctum')->getJson('/api/v1/subscription/invoices');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_usage_counts_are_isolated_per_tenant(): void
    {
        $tenantA = Tenant::default();
        $tenantB = $this->otherTenant();
        TenantSubscription::factory()->active()->create(['tenant_id' => $tenantA->id]);
        Member::factory()->count(2)->create(['tenant_id' => $tenantA->id]);
        Member::factory()->count(7)->create(['tenant_id' => $tenantB->id]);

        $adminA = $this->userWithFullAccess(['Members'], 'Super Admin', $tenantA->id);

        $response = $this->actingAs($adminA, 'sanctum')->getJson('/api/v1/subscription/usage');

        $response->assertOk()->assertJsonPath('data.members.used', 2);
    }

    public function test_changing_another_tenants_plan_is_impossible_since_actions_only_target_the_callers_own_tenant(): void
    {
        $tenantA = Tenant::default();
        $tenantB = $this->otherTenant();
        $planA = SubscriptionPlan::factory()->create(['monthly_price' => 10]);
        $planB = SubscriptionPlan::factory()->create(['monthly_price' => 999]);
        TenantSubscription::factory()->active()->create(['tenant_id' => $tenantA->id, 'plan_id' => $planA->id]);
        $subB = TenantSubscription::factory()->active()->create(['tenant_id' => $tenantB->id, 'plan_id' => $planB->id]);

        $adminA = User::factory()->create(['role' => 'Super Admin', 'tenant_id' => $tenantA->id]);

        // changePlan has no {id} route parameter at all — it can only ever
        // touch the acting admin's own tenant's subscription.
        $this->actingAs($adminA, 'sanctum')->postJson('/api/v1/subscription/change-plan', ['plan_id' => $planA->id]);

        $this->assertDatabaseHas('tenant_subscriptions', ['id' => $subB->id, 'plan_id' => $planB->id]);
    }
}
