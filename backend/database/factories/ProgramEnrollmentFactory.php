<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\ProgramEnrollment;
use App\Models\TrainingProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramEnrollment>
 */
class ProgramEnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'training_program_id' => TrainingProgram::factory(),
            'member_id' => Member::factory(),
            'enrolled_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
