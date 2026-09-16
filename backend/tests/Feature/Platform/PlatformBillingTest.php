<?php

namespace Tests\Feature\Platform;

use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_platform_admin_can_list_billing_history_across_tenants(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $subscription = TenantSubscription::factory()->create(['tenant_id' => Tenant::factory()]);
        SubscriptionInvoice::factory()->count(2)->create([
            'tenant_id' => $subscription->tenant_id,
            'tenant_subscription_id' => $subscription->id,
            'plan_id' => $subscription->plan_id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/billing');

        $response->assertOk()->assertJsonCount(2, 'data');
        $this->assertNotNull($response->json('data.0.tenant.name'));
    }

    public function test_billing_can_be_filtered_by_status(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $subscription = TenantSubscription::factory()->create(['tenant_id' => Tenant::factory()]);
        SubscriptionInvoice::factory()->create([
            'tenant_id' => $subscription->tenant_id, 'tenant_subscription_id' => $subscription->id,
            'plan_id' => $subscription->plan_id, 'status' => 'Paid',
        ]);
        SubscriptionInvoice::factory()->create([
            'tenant_id' => $subscription->tenant_id, 'tenant_subscription_id' => $subscription->id,
            'plan_id' => $subscription->plan_id, 'status' => 'Failed',
        ]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/billing?status=Failed');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.status', 'Failed');
    }

    public function test_a_regular_tenant_admin_cannot_view_platform_billing(): void
    {
        $tenant = Tenant::default();
        $admin = User::factory()->create(['role' => 'Super Admin', 'tenant_id' => $tenant->id]);

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/billing')->assertStatus(403);
    }
}
