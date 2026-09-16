<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipment>
 */
class EquipmentFactory extends Factory
{
    public function definition(): array
    {
        $lastMaintenance = $this->faker->dateTimeBetween('-6 months', '-1 week');

        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'name' => $this->faker->randomElement(['Treadmill', 'Rowing Machine', 'Squat Rack', 'Cable Machine', 'Leg Press', 'Bench Press', 'Dumbbell Set', 'Assault Bike']),
            'image' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=500&q=80',
            'category' => $this->faker->randomElement(['Cardio', 'Strength', 'Free Weights', 'Functional']),
            'brand' => $this->faker->randomElement(['Technogym', 'Life Fitness', 'Rogue', 'Hammer Strength', 'Cybex']),
            'model' => strtoupper($this->faker->bothify('??-###')),
            'purchase_date' => $this->faker->dateTimeBetween('-3 years', '-6 months')->format('Y-m-d'),
            'condition' => $this->faker->randomElement(['Excellent', 'Good', 'Good', 'Needs Maintenance', 'Out of Service']),
            'location' => $this->faker->randomElement(['Main Strength Floor', 'Cardio Zone', 'CrossFit Studio', 'Free Weights Area']),
            'last_maintenance' => $lastMaintenance->format('Y-m-d'),
            'next_maintenance' => (clone $lastMaintenance)->modify('+90 days')->format('Y-m-d'),
            'status' => $this->faker->randomElement(['In Use', 'In Use', 'Under Maintenance', 'Retired']),
        ];
    }
}
