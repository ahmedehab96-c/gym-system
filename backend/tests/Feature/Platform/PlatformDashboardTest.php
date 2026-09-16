<?php

namespace Tests\Feature\Platform;

use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PlatformDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_platform_admin_sees_real_aggregate_counts(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $plan = SubscriptionPlan::factory()->create();

        // Tenant::default() (rather than another Tenant::factory()->create())
        // deliberately reuses the single tenant migrations already create
        // when backfilling users.tenant_id, so totalGyms stays a
        // predictable, exact number — see Tenant::default()'s docblock.
        $active = Tenant::default();
        Tenant::factory()->create(['status' => 'Trial']);
        $suspended = Tenant::factory()->create(['status' => 'Suspended']);

        TenantSubscription::factory()->active()->create(['tenant_id' => $suspended->id, 'plan_id' => $plan->id, 'billing_cycle' => 'Monthly', 'price' => 100]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/dashboard/summary');

        $response->assertOk()
            ->assertJsonPath('data.totalGyms', 3)
            ->assertJsonPath('data.activeGyms', 1)
            ->assertJsonPath('data.trialGyms', 1)
            ->assertJsonPath('data.suspendedGyms', 1)
            ->assertJsonPath('data.activeSubscriptions', 1)
            ->assertJsonPath('data.mrr', 100);
        $this->assertSame('Active', $active->status);
    }

    public function test_revenue_trend_reflects_paid_invoices_only(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $tenant = Tenant::factory()->create();
        $subscription = TenantSubscription::factory()->active()->create(['tenant_id' => $tenant->id]);

        SubscriptionInvoice::factory()->create([
            'tenant_id' => $tenant->id,
            'tenant_subscription_id' => $subscription->id,
            'plan_id' => $subscription->plan_id,
            'status' => 'Paid',
            'amount' => 50,
            'paid_date' => Carbon::today(),
        ]);
        SubscriptionInvoice::factory()->create([
            'tenant_id' => $tenant->id,
            'tenant_subscription_id' => $subscription->id,
            'plan_id' => $subscription->plan_id,
            'status' => 'Pending',
            'amount' => 999,
            'paid_date' => null,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/dashboard/charts');

        $response->assertOk();
        $thisMonth = collect($response->json('data.revenueTrend'))->last();
        $this->assertSame(50, $thisMonth['revenue']);
    }

    public function test_a_regular_tenant_admin_cannot_view_the_platform_dashboard(): void
    {
        $tenant = Tenant::default();
        $admin = User::factory()->create(['role' => 'Super Admin', 'tenant_id' => $tenant->id]);

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/dashboard/summary')->assertStatus(403);
    }
}
