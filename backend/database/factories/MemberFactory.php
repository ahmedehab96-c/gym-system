<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    public function definition(): array
    {
        $joinDate = $this->faker->dateTimeBetween('-2 years', '-1 month');
        $startDate = $this->faker->dateTimeBetween($joinDate, 'now');
        $expiryDate = $this->faker->dateTimeBetween($startDate, '+2 months');

        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'member_id' => 'GYM-'.$this->faker->unique()->numberBetween(1000, 9999),
            'name' => $this->faker->name(),
            'avatar' => 'https://i.pravatar.cc/150?img='.$this->faker->numberBetween(1, 70),
            'gender' => $this->faker->randomElement(['Male', 'Female']),
            'phone' => $this->faker->e164PhoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'address' => $this->faker->streetAddress(),
            'dob' => $this->faker->dateTimeBetween('-55 years', '-18 years')->format('Y-m-d'),
            'join_date' => $joinDate->format('Y-m-d'),
            'plan_id' => MembershipPlan::factory(),
            'trainer_id' => null,
            'start_date' => $startDate->format('Y-m-d'),
            'expiry_date' => $expiryDate->format('Y-m-d'),
            'status' => $this->faker->randomElement(['Active', 'Active', 'Active', 'Inactive', 'Suspended', 'Expired']),
            'attendance_rate' => $this->faker->numberBetween(20, 98),
            'balance_due' => $this->faker->randomElement([0, 0, 0, 350, 600, 950]),
            'emergency_contact' => $this->faker->e164PhoneNumber(),
        ];
    }
}
