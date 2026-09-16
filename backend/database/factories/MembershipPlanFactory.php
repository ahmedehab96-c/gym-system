<?php

namespace Database\Factories;

use App\Models\MembershipPlan;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipPlan>
 */
class MembershipPlanFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->randomElement(['Basic', 'Standard', 'Premium', 'VIP']);

        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'name' => $name,
            'tagline' => $this->faker->sentence(6),
            'price' => $this->faker->numberBetween(300, 1600),
            'duration_label' => 'Monthly',
            'duration_days' => 30,
            'features' => $this->faker->sentences(4),
            'status' => 'Active',
            'color' => $this->faker->randomElement(['#8b8f9a', '#5b8def', '#d4a72f', '#e0263c']),
            'popular' => false,
        ];
    }
}
