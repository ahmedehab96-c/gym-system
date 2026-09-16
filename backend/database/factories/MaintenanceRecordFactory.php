<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\MaintenanceRecord;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceRecord>
 */
class MaintenanceRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'equipment_id' => Equipment::factory(),
            'type' => $this->faker->randomElement(['Routine Service', 'Belt Replacement', 'Deep Clean', 'Calibration', 'Repair']),
            'technician' => $this->faker->name(),
            'date' => $this->faker->dateTimeBetween('-3 months', '+1 month')->format('Y-m-d'),
            'cost' => $this->faker->numberBetween(200, 3000),
            'status' => $this->faker->randomElement(['Upcoming', 'Overdue', 'Completed', 'In Progress']),
            'notes' => $this->faker->sentence(10),
        ];
    }
}
