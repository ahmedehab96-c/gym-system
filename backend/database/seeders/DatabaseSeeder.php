<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with realistic development data
     * matching the existing frontend mock data (see Backend Blueprint).
     */
    public function run(): void
    {
        $this->call([
            StaffSeeder::class,
            SubscriptionPlanSeeder::class,
            MembershipPlanSeeder::class,
            TrainerSeeder::class,
            MemberSeeder::class,
            AttendanceSeeder::class,
            TrainingProgramSeeder::class,
            ClassSeeder::class,
            EquipmentSeeder::class,
            FacilitySeeder::class,
            FinanceSeeder::class,
            CommunicationSeeder::class,
            ContentSeeder::class,
        ]);
    }
}
