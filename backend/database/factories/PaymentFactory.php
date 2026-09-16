<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'member_id' => Member::factory(),
            'invoice_id' => null,
            'amount' => $this->faker->randomElement([350, 600, 950, 1500]),
            'method' => $this->faker->randomElement(['Cash', 'Card', 'Bank Transfer', 'Online']),
            'date' => $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'status' => $this->faker->randomElement(['Paid', 'Paid', 'Paid', 'Pending', 'Failed', 'Refunded']),
        ];
    }
}
