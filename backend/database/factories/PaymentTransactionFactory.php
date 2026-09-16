<?php

namespace Database\Factories;

use App\Models\PaymentTransaction;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'gateway' => 'stripe',
            'type' => 'Charge',
            'reference' => 'cs_test_'.Str::random(24),
            'amount' => $this->faker->numberBetween(0, 200),
            'currency' => 'USD',
            'status' => 'Pending',
            'metadata' => [],
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => 'Paid', 'paid_at' => now()]);
    }

    public function refund(): static
    {
        return $this->state(fn () => [
            'type' => 'Refund',
            'reference' => 're_test_'.Str::random(24),
            'status' => 'Paid',
            'paid_at' => now(),
        ]);
    }
}
