<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement(['Free', 'Basic', 'Pro', 'Enterprise']);

        return [
            'name' => $name,
            'slug' => Str::slug($name.'-'.$this->faker->unique()->numberBetween(1, 100000)),
            'description' => $this->faker->sentence(10),
            'monthly_price' => $this->faker->numberBetween(0, 200),
            'yearly_price' => $this->faker->numberBetween(0, 2000),
            'trial_days' => 14,
            'features' => $this->faker->sentences(3),
            'limits' => [
                'max_members' => 100,
                'max_staff' => 5,
                'max_trainers' => 3,
                'max_classes' => 20,
                'storage_mb' => 500,
                'ai_requests' => 0,
            ],
            'status' => 'Active',
            'sort_order' => 0,
        ];
    }

    /** No limit blocks anything — used by tests that shouldn't hit a cap. */
    public function unlimited(): static
    {
        return $this->state(fn () => [
            'limits' => [
                'max_members' => null,
                'max_staff' => null,
                'max_trainers' => null,
                'max_classes' => null,
                'storage_mb' => null,
                'ai_requests' => null,
            ],
        ]);
    }
}
