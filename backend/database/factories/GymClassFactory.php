<?php

namespace Database\Factories;

use App\Models\GymClass;
use App\Models\Tenant;
use App\Models\Trainer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GymClass>
 */
class GymClassFactory extends Factory
{
    public function definition(): array
    {
        $capacity = $this->faker->numberBetween(10, 25);
        $startHour = $this->faker->numberBetween(6, 19);

        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'name' => $this->faker->randomElement(['HIIT Circuit', 'Yoga Flow', 'CrossFit WOD', 'Boxing Class', 'Pilates', 'Powerlifting Class', 'Spin']),
            'category' => $this->faker->randomElement(['Cardio', 'Strength', 'Mobility', 'Combat']),
            'trainer_id' => Trainer::factory(),
            'date' => $this->faker->dateTimeBetween('now', '+2 weeks')->format('Y-m-d'),
            'day' => $this->faker->randomElement(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']),
            'start_time' => sprintf('%02d:00', $startHour),
            'end_time' => sprintf('%02d:00', $startHour + 1),
            'capacity' => $capacity,
            'status' => $this->faker->randomElement(['Scheduled', 'Scheduled', 'Full', 'Cancelled', 'Completed']),
            'color' => $this->faker->randomElement(['#d4a72f', '#5b8def', '#22c55e', '#e0263c', '#8b5cf6']),
        ];
    }
}
