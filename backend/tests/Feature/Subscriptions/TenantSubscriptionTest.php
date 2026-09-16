<?php

namespace Tests\Feature\Subscriptions;

use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TenantSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(?Tenant $tenant = null): User
    {
        $tenant ??= Tenant::default();

        return User::factory()->create(['role' => 'Super Admin', 'tenant_id' => $tenant->id]);
    }

    public function test_a_tenant_with_no_subscription_yet_is_started_on_a_trial_automatically(): void
    {
        SubscriptionPlan::factory()->create(['status' => 'Active', 'sort_order' => 1, 'trial_days' => 14]);
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/subscription');

        $response->assertOk()->assertJsonPath('data.status', 'Trial');
        $this->assertDatabaseHas('tenant_subscriptions', ['tenant_id' => $admin->tenant_id, 'status' => 'Trial']);
    }

    public function test_a_tenant_can_view_available_plans(): void
    {
        SubscriptionPlan::factory()->create(['status' => 'Active']);
        SubscriptionPlan::factory()->create(['status' => 'Inactive']);
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/subscription/plans');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_an_admin_can_start_a_paid_subscription(): void
    {
        $plan = SubscriptionPlan::factory()->create(['monthly_price' => 79]);
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/subscription/start', [
            'plan_id' => $plan->id,
            'billing_cycle' => 'Monthly',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'Active')
            ->assertJsonPath('data.price', 79);
        $this->assertDatabaseHas('subscription_invoices', ['tenant_id' => $admin->tenant_id, 'amount' => 79]);
    }

    public function test_an_admin_can_change_plan(): void
    {
        $tenant = Tenant::default();
        $oldPlan = SubscriptionPlan::factory()->create(['monthly_price' => 29]);
        $newPlan = SubscriptionPlan::factory()->create(['monthly_price' => 79]);
        TenantSubscription::factory()->active()->create(['tenant_id' => $tenant->id, 'plan_id' => $oldPlan->id, 'billing_cycle' => 'Monthly']);
        $admin = $this->admin($tenant);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/subscription/change-plan', [
            'plan_id' => $newPlan->id,
        ]);

        $response->assertOk()->assertJsonPath('data.price', 79);
    }

    public function test_an_admin_can_cancel_and_reactivate_a_subscription(): void
    {
        $tenant = Tenant::default();
        TenantSubscription::factory()->active()->create(['tenant_id' => $tenant->id]);
        $admin = $this->admin($tenant);

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/subscription/cancel')
            ->assertOk()->assertJsonPath('data.status', 'Cancelled');
        $this->assertDatabaseHas('tenant_subscriptions', ['tenant_id' => $tenant->id, 'status' => 'Cancelled']);

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/subscription/reactivate')
            ->assertOk()->assertJsonPath('data.status', 'Active');
    }

    public function test_cancelling_an_already_cancelled_subscription_is_rejected(): void
    {
        $tenant = Tenant::default();
        TenantSubscription::factory()->create(['tenant_id' => $tenant->id, 'status' => 'Cancelled']);
        $admin = $this->admin($tenant);

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/subscription/cancel')->assertStatus(422);
    }

    public function test_an_admin_can_renew_a_subscription(): void
    {
        $tenant = Tenant::default();
        $subscription = TenantSubscription::factory()->active()->create(['tenant_id' => $tenant->id]);
        $admin = $this->admin($tenant);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/subscription/renew');

        $response->assertOk()->assertJsonPath('data.status', 'Active');
        $this->assertTrue(
            Carbon::parse($response->json('data.nextBillingAt'))
                ->gt(Carbon::parse($subscription->next_billing_at))
        );
    }

    public function test_a_non_admin_staff_member_cannot_manage_the_subscription(): void
    {
        $tenant = Tenant::default();
        TenantSubscription::factory()->active()->create(['tenant_id' => $tenant->id]);
        $receptionist = User::factory()->create(['role' => 'Receptionist', 'tenant_id' => $tenant->id]);

        $this->actingAs($receptionist, 'sanctum')->postJson('/api/v1/subscription/cancel')->assertStatus(403);
        $this->actingAs($receptionist, 'sanctum')->getJson('/api/v1/subscription')->assertOk();
    }

    public function test_billing_history_lists_invoices_for_the_tenant(): void
    {
        $tenant = Tenant::default();
        $subscription = TenantSubscription::factory()->active()->create(['tenant_id' => $tenant->id]);
        SubscriptionInvoice::factory()->count(2)->create([
            'tenant_id' => $tenant->id,
            'tenant_subscription_id' => $subscription->id,
            'plan_id' => $subscription->plan_id,
        ]);
        $admin = $this->admin($tenant);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/subscription/invoices');

        $response->assertOk()->assertJsonCount(2, 'data');
    }
}
