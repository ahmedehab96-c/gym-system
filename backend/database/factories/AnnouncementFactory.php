<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(2),
            'image' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=600&q=80',
            'audience' => $this->faker->randomElement(['All Members', 'Trainers', 'Staff', 'Specific Plan']),
            'plan_id' => null,
            'status' => $this->faker->randomElement(['Published', 'Scheduled', 'Draft']),
            'publish_date' => $this->faker->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
        ];
    }
}
