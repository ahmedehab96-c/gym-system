<?php

namespace Tests\Feature\Trainers;

use App\Models\GymClass;
use App\Models\Member;
use App\Models\PersonalTrainingSession;
use App\Models\RolePermission;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class TrainerTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_lists_trainers_with_pagination(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);
        Trainer::factory()->count(20)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trainers?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.perPage', 5);
    }

    public function test_it_searches_trainers_by_name_email_or_specialty(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);
        Trainer::factory()->create(['name' => 'Findable Trainer', 'email' => 'findable@example.com']);
        Trainer::factory()->create(['name' => 'Someone Else']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trainers?search=Findable');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Findable Trainer');
    }

    public function test_it_filters_trainers_by_status(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);
        Trainer::factory()->create(['status' => 'Active']);
        Trainer::factory()->create(['status' => 'On Leave']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trainers?status=On Leave');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.status', 'On Leave');
    }

    public function test_it_sorts_trainers_by_rating(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);
        Trainer::factory()->create(['name' => 'Low', 'rating' => 3.5]);
        Trainer::factory()->create(['name' => 'High', 'rating' => 4.9]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trainers?sort_by=rating&sort_dir=desc');

        $response->assertOk()->assertJsonPath('data.0.name', 'High');
    }

    public function test_it_creates_a_trainer(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/trainers', [
            'name' => 'New Trainer',
            'specialty' => 'Yoga & Mobility',
            'specialties' => ['Yoga', 'Mobility'],
            'email' => 'new.trainer@example.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Trainer')
            ->assertJsonPath('data.status', 'Active')
            ->assertJsonPath('data.assignedMembers', 0)
            ->assertJsonPath('data.classesCount', 0);
    }

    public function test_creating_a_trainer_requires_valid_data(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/trainers', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['name', 'specialty', 'email']);
    }

    public function test_it_shows_a_trainer_with_assigned_members(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);
        $trainer = Trainer::factory()->create();
        Member::factory()->count(2)->create(['trainer_id' => $trainer->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/trainers/{$trainer->id}");

        $response->assertOk()
            ->assertJsonPath('data.assignedMembers', 2)
            ->assertJsonCount(2, 'data.assignedMembersList');
    }

    public function test_it_updates_a_trainer(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);
        $trainer = Trainer::factory()->create(['status' => 'Active']);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/trainers/{$trainer->id}", [
            'status' => 'On Leave',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'On Leave');
    }

    public function test_it_deletes_a_trainer(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);
        $trainer = Trainer::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/trainers/{$trainer->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('trainers', ['id' => $trainer->id]);
    }

    public function test_it_prevents_deleting_a_trainer_with_scheduled_classes(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);
        $trainer = Trainer::factory()->create();
        GymClass::factory()->create(['trainer_id' => $trainer->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/trainers/{$trainer->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('trainers', ['id' => $trainer->id]);
    }

    public function test_it_prevents_deleting_a_trainer_with_personal_training_sessions(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);
        $trainer = Trainer::factory()->create();
        PersonalTrainingSession::factory()->create(['trainer_id' => $trainer->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/trainers/{$trainer->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('trainers', ['id' => $trainer->id]);
    }

    public function test_it_uploads_a_trainer_photo_to_storage(): void
    {
        Storage::fake('public');
        $user = $this->userWithFullAccess(['Trainers']);
        $trainer = Trainer::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/trainers/{$trainer->id}/photo", [
            'photo' => UploadedFile::fake()->image('trainer.jpg'),
        ]);

        $response->assertOk();
        $url = $response->json('data.photo');
        $this->assertNotNull($url);
        $this->assertStringContainsString('/storage/trainers/', $url);

        $files = Storage::disk('public')->files('trainers');
        $this->assertCount(1, $files);
    }

    public function test_uploading_a_non_image_file_is_rejected(): void
    {
        Storage::fake('public');
        $user = $this->userWithFullAccess(['Trainers']);
        $trainer = Trainer::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/trainers/{$trainer->id}/photo", [
            'photo' => UploadedFile::fake()->create('document.pdf', 100),
        ]);

        $response->assertStatus(422);
    }

    public function test_it_returns_trainer_statistics(): void
    {
        $user = $this->userWithFullAccess(['Trainers']);
        Trainer::factory()->create(['status' => 'Active']);
        Trainer::factory()->create(['status' => 'On Leave']);
        Trainer::factory()->create(['status' => 'Inactive']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trainers/stats');

        $response->assertOk()
            ->assertJsonPath('data.total', 3)
            ->assertJsonPath('data.active', 1)
            ->assertJsonPath('data.onLeave', 1)
            ->assertJsonPath('data.inactive', 1);
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

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trainers');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_trainers(): void
    {
        $response = $this->getJson('/api/v1/trainers');

        $response->assertStatus(401);
    }
}
