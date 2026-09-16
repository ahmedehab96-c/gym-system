<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Trainer;
use App\Models\TrainingProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingProgram>
 */
class TrainingProgramFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'name' => $this->faker->randomElement(['Strength Foundations', 'Fat Loss Bootcamp', 'Powerlifting Prep', 'Mobility & Recovery', 'Athletic Conditioning']).' Program',
            'description' => $this->faker->paragraph(3),
            'image' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=600&q=80',
            'duration' => $this->faker->numberBetween(4, 12).' weeks',
            'difficulty' => $this->faker->randomElement(['Beginner', 'Intermediate', 'Advanced', 'All Levels']),
            'trainer_id' => Trainer::factory(),
            'status' => $this->faker->randomElement(['Active', 'Active', 'Draft', 'Archived']),
        ];
    }
}
