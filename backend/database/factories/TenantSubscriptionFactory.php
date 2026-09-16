<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<TenantSubscription>
 */
class TenantSubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'plan_id' => SubscriptionPlan::factory(),
            'status' => 'Trial',
            'billing_cycle' => 'Monthly',
            'price' => 0,
            'trial_starts_at' => Carbon::today()->toDateString(),
            'trial_ends_at' => Carbon::today()->addDays(14)->toDateString(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => 'Active',
            'started_at' => Carbon::today()->toDateString(),
            'next_billing_at' => Carbon::today()->addMonth()->toDateString(),
            'trial_starts_at' => null,
            'trial_ends_at' => null,
        ]);
    }
}
