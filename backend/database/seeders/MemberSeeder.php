<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MemberNote;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\PersonalTrainingSession;
use App\Models\Trainer;
use App\Models\Tenant;
use App\Support\MembershipStatus;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        $plans = MembershipPlan::all();
        $trainers = Trainer::all();

        // Fixed demo account for the Flutter Member App live demo/APK — unlike
        // the 42 members below (fresh random fake() data every reseed), this one
        // keeps a stable email/password across redeploys, mirroring how
        // StaffSeeder gives the staff dashboard demo a fixed login.
        $demoPlan = $plans->first();
        $demoMember = Member::updateOrCreate(
            ['email' => 'member.demo@premiumgym.com'],
            [
                'tenant_id' => Tenant::default()->id,
                'member_id' => 'GYM-DEMO',
                'name' => 'Demo Member',
                'avatar' => 'https://i.pravatar.cc/150?img=33',
                'gender' => 'Male',
                'phone' => '+20 100 000 0099',
                'address' => 'Demo City',
                'dob' => now()->subYears(30)->format('Y-m-d'),
                'join_date' => now()->subMonths(6)->format('Y-m-d'),
                'plan_id' => $demoPlan->id,
                'trainer_id' => null,
                'start_date' => now()->subMonths(6)->format('Y-m-d'),
                'expiry_date' => now()->addYear()->format('Y-m-d'),
                'status' => 'Active',
                'attendance_rate' => 80,
                'balance_due' => 0,
                'emergency_contact' => '+20 100 000 0098',
            ]
        );
        $demoMember->forceFill(['password' => 'MemberDemo123!'])->save();

        if (! Membership::where('member_id', $demoMember->id)->exists()) {
            Membership::factory()->create([
                'member_id' => $demoMember->id,
                'plan_id' => $demoPlan->id,
                'start_date' => $demoMember->start_date,
                'expiry_date' => $demoMember->expiry_date,
                'price' => $demoPlan->price,
                'status' => MembershipStatus::resolveMembershipStatus($demoMember->expiry_date),
            ]);
        }

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
