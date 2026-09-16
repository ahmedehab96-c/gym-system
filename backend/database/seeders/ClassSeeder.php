<?php

namespace Database\Seeders;

use App\Models\ClassBooking;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Trainer;
use Illuminate\Database\Seeder;

class ClassSeeder extends Seeder
{
    public function run(): void
    {
        $trainers = Trainer::all();
        $members = Member::all();

        for ($i = 0; $i < 14; $i++) {
            $class = GymClass::factory()->create(['trainer_id' => $trainers->random()->id]);

            $bookingCount = min($class->capacity, $members->count(), fake()->numberBetween(3, 18));

            foreach ($members->random($bookingCount) as $member) {
                ClassBooking::factory()->create([
                    'class_id' => $class->id,
                    'member_id' => $member->id,
                ]);
            }
        }
    }
}
