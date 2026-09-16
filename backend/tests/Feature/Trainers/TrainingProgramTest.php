<?php

namespace Tests\Feature\Trainers;

use App\Models\Member;
use App\Models\RolePermission;
use App\Models\Trainer;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class TrainingProgramTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_lists_programs_with_pagination(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);
        TrainingProgram::factory()->count(15)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/training-programs?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 15)
            ->assertJsonPath('meta.perPage', 5);
    }

    public function test_it_searches_programs_by_name(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);
        TrainingProgram::factory()->create(['name' => 'Findable Program']);
        TrainingProgram::factory()->create(['name' => 'Other Program']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/training-programs?search=Findable');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_it_filters_programs_by_difficulty_status_and_trainer(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);
        $trainer = Trainer::factory()->create();
        $match = TrainingProgram::factory()->create([
            'difficulty' => 'Advanced',
            'status' => 'Active',
            'trainer_id' => $trainer->id,
        ]);
        TrainingProgram::factory()->create(['difficulty' => 'Beginner', 'status' => 'Active']);
        TrainingProgram::factory()->create(['difficulty' => 'Advanced', 'status' => 'Draft']);

        $response = $this->actingAs($user, 'sanctum')->getJson(
            "/api/v1/training-programs?difficulty=Advanced&status=Active&trainer_id={$trainer->id}"
        );

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $match->id);
    }

    public function test_it_creates_a_program(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);
        $trainer = Trainer::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/training-programs', [
            'name' => 'New Program',
            'description' => 'A great program',
            'duration' => '8 weeks',
            'difficulty' => 'Intermediate',
            'trainer_id' => $trainer->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Program')
            ->assertJsonPath('data.trainerId', $trainer->id)
            ->assertJsonPath('data.trainerName', $trainer->name)
            ->assertJsonPath('data.status', 'Draft')
            ->assertJsonPath('data.membersEnrolled', 0);
    }

    public function test_creating_a_program_requires_valid_data(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/training-programs', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_it_shows_a_program_with_enrolled_members(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);
        $program = TrainingProgram::factory()->create();
        $member = Member::factory()->create();
        $program->members()->attach($member->id, ['enrolled_at' => now()]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/training-programs/{$program->id}");

        $response->assertOk()
            ->assertJsonPath('data.membersEnrolled', 1)
            ->assertJsonCount(1, 'data.enrolledMembers');
    }

    public function test_it_updates_a_program(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);
        $program = TrainingProgram::factory()->create(['status' => 'Draft']);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/training-programs/{$program->id}", [
            'status' => 'Active',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'Active');
    }

    public function test_it_deletes_a_program(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);
        $program = TrainingProgram::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/training-programs/{$program->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('training_programs', ['id' => $program->id]);
    }

    public function test_it_uploads_a_program_image_to_storage(): void
    {
        Storage::fake('public');
        $user = $this->userWithFullAccess(['Trainers']);
        $program = TrainingProgram::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/training-programs/{$program->id}/image", [
            'image' => UploadedFile::fake()->image('program.jpg'),
        ]);

        $response->assertOk();
        $this->assertStringContainsString('/storage/programs/', $response->json('data.image'));
        $this->assertCount(1, Storage::disk('public')->files('programs'));
    }

    public function test_it_enrolls_a_member_in_a_program(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);
        $program = TrainingProgram::factory()->create();
        $member = Member::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/training-programs/{$program->id}/enroll", [
            'member_id' => $member->id,
        ]);

        $response->assertCreated()->assertJsonPath('data.membersEnrolled', 1);
        $this->assertDatabaseHas('program_enrollments', ['training_program_id' => $program->id, 'member_id' => $member->id]);
    }

    public function test_it_prevents_enrolling_the_same_member_twice(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);
        $program = TrainingProgram::factory()->create();
        $member = Member::factory()->create();
        $program->members()->attach($member->id, ['enrolled_at' => now()]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/training-programs/{$program->id}/enroll", [
            'member_id' => $member->id,
        ]);

        $response->assertStatus(422);
        $this->assertSame(1, $program->members()->count());
    }

    public function test_it_unenrolls_a_member_from_a_program(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);
        $program = TrainingProgram::factory()->create();
        $member = Member::factory()->create();
        $program->members()->attach($member->id, ['enrolled_at' => now()]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/training-programs/{$program->id}/enroll/{$member->id}");

        $response->assertOk()->assertJsonPath('data.membersEnrolled', 0);
        $this->assertDatabaseMissing('program_enrollments', ['training_program_id' => $program->id, 'member_id' => $member->id]);
    }

    public function test_a_role_without_permission_is_forbidden(): void
    {
        RolePermission::factory()->create([
            'role' => 'Accountant',
            'module' => 'Trainers',
            'can_view' => false,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Accountant']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/training-programs');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_training_programs(): void
    {
        $response = $this->getJson('/api/v1/training-programs');

        $response->assertStatus(401);
    }
}
