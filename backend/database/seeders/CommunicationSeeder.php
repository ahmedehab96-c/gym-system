<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\AppNotification;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommunicationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        AppNotification::factory()
            ->count(18)
            ->create()
            ->each(function (AppNotification $notification) use ($users) {
                if (fake()->boolean(40)) {
                    $notification->update(['user_id' => $users->random()->id]);
                }
            });

        $plans = MembershipPlan::all();

        Announcement::factory()
            ->count(6)
            ->create()
            ->each(function (Announcement $announcement) use ($plans) {
                if ($announcement->audience === 'Specific Plan') {
                    $announcement->update(['plan_id' => $plans->random()->id]);
                }
            });
    }
}
