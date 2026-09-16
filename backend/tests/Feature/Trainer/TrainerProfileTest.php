<?php

namespace Tests\Feature\Trainer;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithTrainerAuth;
use Tests\TestCase;

class TrainerProfileTest extends TestCase
{
    use InteractsWithTrainerAuth, RefreshDatabase;

    public function test_a_trainer_can_view_their_own_profile(): void
    {
        [$user, $trainer] = $this->trainerAccount(trainerAttributes: ['name' => 'Alex Coach']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trainer/profile');

        $response->assertOk()->assertJsonPath('data.id', $trainer->id)->assertJsonPath('data.name', 'Alex Coach');
    }

    public function test_a_trainer_can_update_their_own_bio_and_specialty(): void
    {
        [$user] = $this->trainerAccount();

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/v1/trainer/profile', [
            'bio' => 'Updated bio text.',
            'specialty' => 'Powerlifting',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.bio', 'Updated bio text.')
            ->assertJsonPath('data.specialty', 'Powerlifting');
    }

    public function test_a_trainer_cannot_change_their_own_status_or_rating_via_self_service_update(): void
    {
        [$user, $trainer] = $this->trainerAccount(trainerAttributes: ['status' => 'Active', 'rating' => 4.5]);

        $this->actingAs($user, 'sanctum')->putJson('/api/v1/trainer/profile', [
            'status' => 'Inactive',
            'rating' => 5,
        ])->assertOk();

        $trainer->refresh();
        $this->assertSame('Active', $trainer->status);
        $this->assertEquals(4.5, (float) $trainer->rating);
    }

    public function test_a_trainer_can_upload_their_own_photo(): void
    {
        Storage::fake('public');
        [$user] = $this->trainerAccount();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/trainer/profile/photo', [
            'photo' => UploadedFile::fake()->image('trainer.jpg'),
        ]);

        $response->assertOk();
        $this->assertNotNull($response->json('data.photo'));
        Storage::disk('public')->assertExists('trainers/'.basename($response->json('data.photo')));
    }
}
