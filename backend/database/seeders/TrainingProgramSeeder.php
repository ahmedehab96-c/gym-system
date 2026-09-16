<?php

namespace Database\Seeders;

use App\Models\Trainer;
use App\Models\TrainingProgram;
use Illuminate\Database\Seeder;

class TrainingProgramSeeder extends Seeder
{
    public function run(): void
    {
        $trainers = Trainer::all();

        for ($i = 0; $i < 6; $i++) {
            TrainingProgram::factory()->create(['trainer_id' => $trainers->random()->id]);
        }
    }
}
