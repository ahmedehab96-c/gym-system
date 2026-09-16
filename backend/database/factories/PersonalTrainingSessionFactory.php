<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\PersonalTrainingSession;
use App\Models\Tenant;
use App\Models\Trainer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonalTrainingSession>
 */
class PersonalTrainingSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'member_id' => Member::factory(),
            'trainer_id' => Trainer::factory(),
            'goal' => $this->faker->randomElement(['Fat Loss', 'Muscle Gain', 'Strength', 'Rehab', 'General Fitness']),
            'sessions_per_week' => $this->faker->numberBetween(1, 3),
        ];
    }
}
