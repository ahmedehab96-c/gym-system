<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;

class FacilitySeeder extends Seeder
{
    public function run(): void
    {
        $facilities = [
            ['name' => 'Main Strength Floor', 'capacity' => 80, 'area' => '420 m²', 'status' => 'Open'],
            ['name' => 'Cardio Zone', 'capacity' => 60, 'area' => '260 m²', 'status' => 'Open'],
            ['name' => 'CrossFit Studio', 'capacity' => 24, 'area' => '180 m²', 'status' => 'Open'],
            ['name' => 'Yoga & Pilates Room', 'capacity' => 20, 'area' => '120 m²', 'status' => 'Open'],
            ['name' => 'Sauna & Recovery', 'capacity' => 10, 'area' => '60 m²', 'status' => 'Maintenance'],
            ['name' => 'Locker Rooms', 'capacity' => 120, 'area' => '150 m²', 'status' => 'Open'],
        ];

        foreach ($facilities as $facility) {
            Facility::factory()->create($facility);
        }
    }
}
