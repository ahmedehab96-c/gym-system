<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\MaintenanceRecord;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        Equipment::factory()
            ->count(16)
            ->create()
            ->each(function (Equipment $equipment) {
                MaintenanceRecord::factory()->count(fake()->numberBetween(1, 3))->create([
                    'equipment_id' => $equipment->id,
                ]);
            });
    }
}
