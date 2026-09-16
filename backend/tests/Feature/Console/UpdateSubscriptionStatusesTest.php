<?php

namespace Tests\Feature\Console;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UpdateSubscriptionStatusesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_trial_past_its_end_date_expires_and_notifies_staff(): void
    {
        $tenant = Tenant::default();
        $staff = User::factory()->create(['tenant_id' => $tenant->id, 'status' => 'Active']);
        $plan = SubscriptionPlan::factory()->create();
        $subscription = TenantSubscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'Trial',
            'trial_ends_at' => Carbon::today()->subDay()->toDateString(),
        ]);

        $this->artisan('subscriptions:update-statuses')->assertSuccessful();

        $this->assertDatabaseHas('tenant_subscriptions', ['id' => $subscription->id, 'status' => 'Expired']);
        $this->assertDatabaseHas('notifications', ['type' => 'Subscription Trial Expired', 'user_id' => $staff->id]);
    }

    public function test_a_trial_ending_in_three_days_sends_a_reminder_without_changing_status(): void
    {
        $tenant = Tenant::default();
        $staff = User::factory()->create(['tenant_id' => $tenant->id, 'status' => 'Active']);
        $subscription = TenantSubscription::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'Trial',
            'trial_ends_at' => Carbon::today()->addDays(3)->toDateString(),
        ]);

        $this->artisan('subscriptions:update-statuses')->assertSuccessful();

        $this->assertDatabaseHas('tenant_subscriptions', ['id' => $subscription->id, 'status' => 'Trial']);
        $this->assertDatabaseHas('notifications', ['type' => 'Subscription Trial Ending', 'user_id' => $staff->id]);
    }

    public function test_an_active_subscription_past_its_billing_date_becomes_past_due(): void
    {
        $tenant = Tenant::default();
        $staff = User::factory()->create(['tenant_id' => $tenant->id, 'status' => 'Active']);
        $subscription = TenantSubscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'next_billing_at' => Carbon::today()->subDay()->toDateString(),
        ]);

        $this->artisan('subscriptions:update-statuses')->assertSuccessful();

        $this->assertDatabaseHas('tenant_subscriptions', ['id' => $subscription->id, 'status' => 'Past Due']);
        $this->assertDatabaseHas('notifications', ['type' => 'Subscription Past Due', 'user_id' => $staff->id]);
    }

    public function test_a_past_due_subscription_beyond_the_grace_period_expires(): void
    {
        $tenant = Tenant::default();
        $staff = User::factory()->create(['tenant_id' => $tenant->id, 'status' => 'Active']);
        $subscription = TenantSubscription::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'Past Due',
            'next_billing_at' => Carbon::today()->subDays(10)->toDateString(),
        ]);

        $this->artisan('subscriptions:update-statuses')->assertSuccessful();

        $this->assertDatabaseHas('tenant_subscriptions', ['id' => $subscription->id, 'status' => 'Expired']);
        $this->assertDatabaseHas('notifications', ['type' => 'Subscription Expired', 'user_id' => $staff->id]);
    }

    public function test_a_past_due_subscription_still_within_the_grace_period_is_left_alone(): void
    {
        $tenant = Tenant::default();
        $subscription = TenantSubscription::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'Past Due',
            'next_billing_at' => Carbon::today()->subDays(2)->toDateString(),
        ]);

        $this->artisan('subscriptions:update-statuses')->assertSuccessful();

        $this->assertDatabaseHas('tenant_subscriptions', ['id' => $subscription->id, 'status' => 'Past Due']);
    }

    public function test_notifications_only_reach_the_owning_tenants_staff(): void
    {
        $tenantA = Tenant::default();
        $tenantB = Tenant::factory()->create();
        $staffA = User::factory()->create(['tenant_id' => $tenantA->id, 'status' => 'Active']);
        $staffB = User::factory()->create(['tenant_id' => $tenantB->id, 'status' => 'Active']);

        TenantSubscription::factory()->create([
            'tenant_id' => $tenantA->id,
            'status' => 'Trial',
            'trial_ends_at' => Carbon::today()->subDay()->toDateString(),
        ]);

        $this->artisan('subscriptions:update-statuses')->assertSuccessful();

        $this->assertDatabaseHas('notifications', ['type' => 'Subscription Trial Expired', 'user_id' => $staffA->id]);
        $this->assertDatabaseMissing('notifications', ['type' => 'Subscription Trial Expired', 'user_id' => $staffB->id]);
    }
}
