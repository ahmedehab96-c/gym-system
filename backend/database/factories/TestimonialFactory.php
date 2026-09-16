<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'role' => $this->faker->randomElement(['Member since 2022', 'Powerlifter', 'Marathon Runner', 'Yoga Enthusiast']),
            'quote' => $this->faker->paragraph(2),
            'avatar' => 'https://i.pravatar.cc/150?img='.$this->faker->numberBetween(1, 70),
            'rating' => $this->faker->numberBetween(4, 5),
        ];
    }
}
