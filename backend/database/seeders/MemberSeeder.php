<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MemberNote;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\PersonalTrainingSession;
use App\Models\Trainer;
use App\Support\MembershipStatus;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        $plans = MembershipPlan::all();
        $trainers = Trainer::all();

        for ($i = 0; $i < 42; $i++) {
            $plan = $plans->random();
            $hasTrainer = fake()->boolean(55);
            $trainerId = $hasTrainer ? $trainers->random()->id : null;

            $member = Member::factory()->create([
                'plan_id' => $plan->id,
                'trainer_id' => $trainerId,
            ]);

            Membership::factory()->create([
                'member_id' => $member->id,
                'plan_id' => $plan->id,
                'start_date' => $member->start_date,
                'expiry_date' => $member->expiry_date,
                'price' => $plan->price,
                'status' => MembershipStatus::resolveMembershipStatus($member->expiry_date),
            ]);

            if (fake()->boolean(30)) {
                MemberNote::factory()->create(['member_id' => $member->id]);
            }

            if ($hasTrainer) {
                PersonalTrainingSession::factory()->create([
                    'member_id' => $member->id,
                    'trainer_id' => $trainerId,
                ]);
            }
        }
    }
}
