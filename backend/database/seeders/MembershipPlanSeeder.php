<?php

namespace Database\Seeders;

use App\Models\MembershipPlan;
use Illuminate\Database\Seeder;

class MembershipPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Basic', 'tagline' => 'Gym floor access for getting started', 'price' => 350, 'color' => '#8b8f9a', 'popular' => false, 'features' => ['Gym floor access', 'Locker room access', '1 fitness assessment', 'Standard hours']],
            ['name' => 'Standard', 'tagline' => 'Full access with group classes', 'price' => 600, 'color' => '#5b8def', 'popular' => false, 'features' => ['Everything in Basic', 'Unlimited group classes', 'Sauna access', 'Guest pass x1/mo']],
            ['name' => 'Premium', 'tagline' => 'Personal training included', 'price' => 950, 'color' => '#d4a72f', 'popular' => true, 'features' => ['Everything in Standard', '4 PT sessions/mo', 'Nutrition plan', 'Priority booking']],
            ['name' => 'VIP', 'tagline' => 'The complete premium experience', 'price' => 1500, 'color' => '#e0263c', 'popular' => false, 'features' => ['Everything in Premium', 'Unlimited PT sessions', 'Private locker', '24/7 access', 'Spa access']],
        ];

        foreach ($plans as $plan) {
            MembershipPlan::factory()->create(array_merge($plan, [
                'duration_label' => 'Monthly',
                'duration_days' => 30,
                'status' => 'Active',
            ]));
        }
    }
}
