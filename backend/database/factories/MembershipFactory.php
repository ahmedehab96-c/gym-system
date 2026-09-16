<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-6 months', 'now');
        $expiry = $this->faker->dateTimeBetween($start, '+2 months');

        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'member_id' => Member::factory(),
            'plan_id' => MembershipPlan::factory(),
            'start_date' => $start->format('Y-m-d'),
            'expiry_date' => $expiry->format('Y-m-d'),
            'price' => $this->faker->randomElement([350, 600, 950, 1500]),
            'status' => $this->faker->randomElement(['Active', 'Active', 'Expiring Soon', 'Expired', 'Suspended']),
        ];
    }
}
