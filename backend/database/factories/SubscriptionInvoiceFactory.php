<?php

namespace Database\Factories;

use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<SubscriptionInvoice>
 */
class SubscriptionInvoiceFactory extends Factory
{
    public function definition(): array
    {
        $issueDate = Carbon::today();

        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'tenant_subscription_id' => TenantSubscription::factory(),
            'plan_id' => SubscriptionPlan::factory(),
            'invoice_number' => 'SUB-'.strtoupper(Str::random(8)),
            'amount' => $this->faker->numberBetween(0, 200),
            'currency' => 'USD',
            'billing_period_start' => $issueDate->toDateString(),
            'billing_period_end' => $issueDate->copy()->addMonth()->toDateString(),
            'status' => 'Pending',
            'issue_date' => $issueDate->toDateString(),
            'due_date' => $issueDate->copy()->addDays(7)->toDateString(),
        ];
    }
}
