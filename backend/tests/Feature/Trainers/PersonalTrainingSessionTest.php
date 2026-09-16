<?php

namespace Tests\Feature\Trainers;

use App\Models\Member;
use App\Models\PersonalTrainingSession;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalTrainingSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_sessions_with_member_and_trainer(): void
    {
        $user = User::factory()->create();
        $session = PersonalTrainingSession::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/personal-training');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $session->id)
            ->assertJsonPath('data.0.memberName', $session->member->name)
            ->assertJsonPath('data.0.trainerName', $session->trainer->name);
    }

    public function test_it_creates_a_session(): void
    {
        $user = User::factory()->create();
        $member = Member::factory()->create();
        $trainer = Trainer::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/personal-training', [
            'member_id' => $member->id,
            'trainer_id' => $trainer->id,
            'goal' => 'Fat Loss',
            'sessions_per_week' => 3,
        ]);

        $response->assertCreated()->assertJsonPath('data.goal', 'Fat Loss');
        $this->assertDatabaseHas('personal_training_sessions', ['member_id' => $member->id, 'trainer_id' => $trainer->id]);
    }

    public function test_a_member_can_only_have_one_active_session(): void
    {
        $user = User::factory()->create();
        $existing = PersonalTrainingSession::factory()->create();
        $trainer = Trainer::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/personal-training', [
            'member_id' => $existing->member_id,
            'trainer_id' => $trainer->id,
            'goal' => 'Strength',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('member_id');
    }

    public function test_it_updates_a_session(): void
    {
        $user = User::factory()->create();
        $session = PersonalTrainingSession::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/personal-training/{$session->id}", [
            'goal' => 'Rehab',
        ]);

        $response->assertOk()->assertJsonPath('data.goal', 'Rehab');
    }

    public function test_it_deletes_a_session(): void
    {
        $user = User::factory()->create();
        $session = PersonalTrainingSession::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/personal-training/{$session->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('personal_training_sessions', ['id' => $session->id]);
    }

    public function test_guest_cannot_access_personal_training(): void
    {
        $response = $this->getJson('/api/v1/personal-training');

        $response->assertStatus(401);
    }
}
