<?php

namespace Tests\Feature\Trainer;

use App\Models\GymClass;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\TrainingProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTrainerAuth;
use Tests\TestCase;

/**
 * Proves the "no new list endpoints needed" design (Phase 26): the
 * existing staff /classes, /schedule/*, /members, and /training-programs
 * endpoints already support ?trainer_id= and already grant the Trainer
 * role view access — this only needed the trainers.user_id link, not
 * new business logic.
 */
class TrainerScopedApiTest extends TestCase
{
    use InteractsWithTrainerAuth, RefreshDatabase;

    public function test_a_trainer_can_list_only_their_own_classes(): void
    {
        [$user, $trainer] = $this->trainerAccount();
        $tenant = Tenant::default();
        GymClass::factory()->create(['tenant_id' => $tenant->id, 'trainer_id' => $trainer->id]);
        GymClass::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/classes?trainer_id={$trainer->id}");

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_a_trainer_can_view_their_own_class_with_booked_members(): void
    {
        [$user, $trainer] = $this->trainerAccount();
        $class = GymClass::factory()->create(['tenant_id' => Tenant::default()->id, 'trainer_id' => $trainer->id]);
        $member = Member::factory()->create(['tenant_id' => Tenant::default()->id]);
        $class->bookings()->create(['member_id' => $member->id, 'booked_at' => now()]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/classes/{$class->id}");

        $response->assertOk()->assertJsonCount(1, 'data.bookedMembers');
    }

    public function test_a_trainer_can_list_only_their_assigned_members(): void
    {
        [$user, $trainer] = $this->trainerAccount();
        $tenant = Tenant::default();
        Member::factory()->create(['tenant_id' => $tenant->id, 'trainer_id' => $trainer->id]);
        Member::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/members?trainer_id={$trainer->id}");

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_a_trainer_can_list_only_their_assigned_programs(): void
    {
        [$user, $trainer] = $this->trainerAccount();
        $tenant = Tenant::default();
        TrainingProgram::factory()->create(['tenant_id' => $tenant->id, 'trainer_id' => $trainer->id]);
        TrainingProgram::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/training-programs?trainer_id={$trainer->id}");

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_a_trainer_can_view_the_daily_schedule_filtered_to_themself(): void
    {
        [$user, $trainer] = $this->trainerAccount();
        GymClass::factory()->create(['tenant_id' => Tenant::default()->id, 'trainer_id' => $trainer->id, 'date' => now()->toDateString()]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/schedule/daily?trainer_id={$trainer->id}");

        $response->assertOk()->assertJsonCount(1, 'data.classes');
    }

    public function test_a_trainer_can_view_attendance_but_cannot_check_a_member_in(): void
    {
        [$user] = $this->trainerAccount();
        $member = Member::factory()->create(['tenant_id' => Tenant::default()->id, 'status' => 'Active']);

        // Read access is granted by the existing Trainer role permission matrix.
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/attendance/today')->assertOk();

        // Marking attendance requires can_create, which Trainer does not have —
        // the backend must reject this regardless of what the app's UI shows.
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/attendance/check-in', [
            'member_id' => $member->id,
        ])->assertStatus(403);
    }

    public function test_a_trainer_cannot_edit_a_class_despite_having_view_access(): void
    {
        [$user, $trainer] = $this->trainerAccount();
        $class = GymClass::factory()->create(['tenant_id' => Tenant::default()->id, 'trainer_id' => $trainer->id]);

        $this->actingAs($user, 'sanctum')->putJson("/api/v1/classes/{$class->id}", ['name' => 'Renamed'])->assertStatus(403);
    }

    public function test_a_trainer_never_sees_another_tenants_members_even_with_a_matching_trainer_id_number(): void
    {
        [$user, $trainer] = $this->trainerAccount();
        $otherTenant = Tenant::factory()->create();
        Member::factory()->create(['tenant_id' => $otherTenant->id, 'trainer_id' => $trainer->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/members?trainer_id={$trainer->id}");

        $response->assertOk()->assertJsonCount(0, 'data');
    }
}
