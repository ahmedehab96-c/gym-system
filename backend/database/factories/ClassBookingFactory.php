<?php

namespace Database\Factories;

use App\Models\ClassBooking;
use App\Models\GymClass;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassBooking>
 */
class ClassBookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'class_id' => GymClass::factory(),
            'member_id' => Member::factory(),
            'booked_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
