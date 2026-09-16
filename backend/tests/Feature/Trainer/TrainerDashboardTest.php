<?php

namespace Tests\Feature\Trainer;

use App\Models\AttendanceRecord;
use App\Models\ClassBooking;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTrainerAuth;
use Tests\TestCase;

class TrainerDashboardTest extends TestCase
{
    use InteractsWithTrainerAuth, RefreshDatabase;

    public function test_it_aggregates_the_trainers_own_classes_members_and_attendance(): void
    {
        [$user, $trainer] = $this->trainerAccount();
        $tenant = Tenant::default();

        $todayClass = GymClass::factory()->create(['tenant_id' => $tenant->id, 'trainer_id' => $trainer->id, 'date' => now()->toDateString()]);
        GymClass::factory()->create(['tenant_id' => $tenant->id, 'trainer_id' => $trainer->id, 'date' => now()->addDays(2)->toDateString()]);
        // Someone else's class must never appear in this trainer's dashboard.
        GymClass::factory()->create(['tenant_id' => $tenant->id, 'date' => now()->toDateString()]);

        $assignedMember = Member::factory()->create(['tenant_id' => $tenant->id, 'trainer_id' => $trainer->id]);
        Member::factory()->create(['tenant_id' => $tenant->id]);
        AttendanceRecord::factory()->create(['tenant_id' => $tenant->id, 'member_id' => $assignedMember->id, 'date' => now()->toDateString()]);

        ClassBooking::create(['class_id' => $todayClass->id, 'member_id' => $assignedMember->id, 'booked_at' => now()]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trainer/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.trainer.id', $trainer->id)
            ->assertJsonCount(1, 'data.todaysClasses')
            ->assertJsonCount(1, 'data.upcomingClasses')
            ->assertJsonPath('data.assignedMembersCount', 1)
            ->assertJsonPath('data.todaysAttendance', 1)
            ->assertJsonCount(1, 'data.recentActivity');
    }

    public function test_a_trainer_with_no_classes_or_members_sees_a_clean_empty_dashboard(): void
    {
        [$user] = $this->trainerAccount();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trainer/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.assignedMembersCount', 0)
            ->assertJsonPath('data.todaysAttendance', 0)
            ->assertJsonCount(0, 'data.todaysClasses');
    }

    public function test_a_trainer_never_sees_another_tenants_data(): void
    {
        [$user, $trainer] = $this->trainerAccount();
        $otherTenant = Tenant::factory()->create();
        GymClass::factory()->create(['tenant_id' => $otherTenant->id, 'date' => now()->toDateString()]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trainer/dashboard');

        $response->assertOk()->assertJsonCount(0, 'data.todaysClasses');
    }
}
