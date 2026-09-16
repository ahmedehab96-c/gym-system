<?php

namespace Database\Factories;

use App\Models\Facility;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Facility>
 */
class FacilityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'name' => $this->faker->randomElement(['Main Strength Floor', 'Cardio Zone', 'CrossFit Studio', 'Yoga & Pilates Room', 'Sauna & Recovery', 'Locker Rooms']),
            'description' => $this->faker->sentence(10),
            'image' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=500&q=80',
            'capacity' => $this->faker->numberBetween(10, 120),
            'area' => $this->faker->numberBetween(60, 420).' m²',
            'status' => $this->faker->randomElement(['Open', 'Open', 'Open', 'Maintenance']),
        ];
    }
}
