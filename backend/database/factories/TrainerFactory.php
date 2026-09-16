<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Trainer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trainer>
 */
class TrainerFactory extends Factory
{
    public function definition(): array
    {
        $specialty = $this->faker->randomElement([
            'Strength & Conditioning', 'Yoga & Mobility', 'HIIT & Fat Loss', 'CrossFit', 'Boxing & Conditioning', 'Pilates & Core',
        ]);

        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'name' => $this->faker->name(),
            'photo' => 'https://i.pravatar.cc/200?img='.$this->faker->numberBetween(1, 70),
            'specialty' => $specialty,
            'specialties' => $this->faker->randomElements(['Strength', 'Yoga', 'HIIT', 'CrossFit', 'Boxing', 'Pilates', 'Mobility', 'Fat Loss'], 2),
            'experience' => $this->faker->numberBetween(2, 12).' years',
            'phone' => $this->faker->e164PhoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'bio' => $this->faker->paragraph(2),
            'status' => $this->faker->randomElement(['Active', 'Active', 'Active', 'On Leave', 'Inactive']),
            'rating' => $this->faker->randomFloat(2, 4, 5),
            'sessions_completed' => $this->faker->numberBetween(100, 2000),
            'schedule' => [
                ['day' => 'Mon', 'time' => '07:00 - 09:00', 'activity' => $specialty],
                ['day' => 'Wed', 'time' => '17:00 - 19:00', 'activity' => $specialty],
            ],
        ];
    }
}
