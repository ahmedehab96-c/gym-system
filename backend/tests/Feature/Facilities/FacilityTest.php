<?php

namespace Tests\Feature\Facilities;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FacilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_index_lists_facilities_without_auth(): void
    {
        Facility::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/public/facilities');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_it_creates_a_facility(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/facilities', [
            'name' => 'Rooftop Track',
            'capacity' => 40,
            'area' => '300 m²',
        ]);

        $response->assertCreated()->assertJsonPath('data.name', 'Rooftop Track')->assertJsonPath('data.status', 'Open');
    }

    public function test_it_updates_a_facility(): void
    {
        $user = User::factory()->create();
        $facility = Facility::factory()->create(['status' => 'Open']);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/facilities/{$facility->id}", [
            'status' => 'Maintenance',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'Maintenance');
    }

    public function test_it_deletes_a_facility(): void
    {
        $user = User::factory()->create();
        $facility = Facility::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/facilities/{$facility->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('facilities', ['id' => $facility->id]);
    }

    public function test_it_uploads_a_facility_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $facility = Facility::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/facilities/{$facility->id}/image", [
            'image' => UploadedFile::fake()->image('facility.jpg'),
        ]);

        $response->assertOk();
        $this->assertNotNull($response->json('data.image'));
    }

    public function test_guest_cannot_create_a_facility(): void
    {
        $response = $this->postJson('/api/v1/facilities', ['name' => 'X']);

        $response->assertStatus(401);
    }
}
