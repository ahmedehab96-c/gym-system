<?php

namespace Tests\Feature\Announcements;

use App\Models\Announcement;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_lists_announcements_with_pagination(): void
    {
        $user = $this->userWithFullAccess(['Announcements']);
        Announcement::factory()->count(15)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/announcements?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 15)
            ->assertJsonPath('meta.perPage', 5);
    }

    public function test_it_searches_announcements_by_title_or_description(): void
    {
        $user = $this->userWithFullAccess(['Announcements']);
        Announcement::factory()->create(['title' => 'Holiday Closure', 'description' => 'We are closed Friday.']);
        Announcement::factory()->create(['title' => 'New Equipment', 'description' => 'New treadmills arrived.']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/announcements?search=Holiday');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Holiday Closure');
    }

    public function test_it_filters_by_audience_and_status(): void
    {
        $user = $this->userWithFullAccess(['Announcements']);
        Announcement::factory()->create(['audience' => 'Trainers', 'status' => 'Published']);
        Announcement::factory()->create(['audience' => 'All Members', 'status' => 'Draft']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/announcements?audience=Trainers&status=Published');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.audience', 'Trainers');
    }

    public function test_it_creates_an_announcement(): void
    {
        $user = $this->userWithFullAccess(['Announcements']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/announcements', [
            'title' => 'Gym Closed for Maintenance',
            'description' => 'The gym will be closed this Sunday.',
            'audience' => 'All Members',
            'publish_date' => now()->toDateString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Gym Closed for Maintenance')
            ->assertJsonPath('data.audience', 'All Members')
            ->assertJsonPath('data.status', 'Draft');
    }

    public function test_it_requires_a_plan_when_targeting_a_specific_plan(): void
    {
        $user = $this->userWithFullAccess(['Announcements']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/announcements', [
            'title' => 'Premium Members Only',
            'audience' => 'Specific Plan',
            'publish_date' => now()->toDateString(),
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('plan_id');
    }

    public function test_it_creates_an_announcement_targeting_a_specific_plan(): void
    {
        $user = $this->userWithFullAccess(['Announcements']);
        $plan = MembershipPlan::factory()->create(['name' => 'VIP']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/announcements', [
            'title' => 'VIP Perk',
            'audience' => 'Specific Plan',
            'plan_id' => $plan->id,
            'publish_date' => now()->toDateString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.planId', $plan->id)
            ->assertJsonPath('data.planName', 'VIP');
    }

    public function test_it_shows_an_announcement(): void
    {
        $user = $this->userWithFullAccess(['Announcements']);
        $announcement = Announcement::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/announcements/{$announcement->id}");

        $response->assertOk()->assertJsonPath('data.id', $announcement->id);
    }

    public function test_it_updates_an_announcement(): void
    {
        $user = $this->userWithFullAccess(['Announcements']);
        $announcement = Announcement::factory()->create(['title' => 'Old Title']);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/announcements/{$announcement->id}", [
            'title' => 'New Title',
        ]);

        $response->assertOk()->assertJsonPath('data.title', 'New Title');
    }

    public function test_it_deletes_an_announcement(): void
    {
        $user = $this->userWithFullAccess(['Announcements']);
        $announcement = Announcement::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/announcements/{$announcement->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
    }

    public function test_it_publishes_and_unpublishes_an_announcement(): void
    {
        $user = $this->userWithFullAccess(['Announcements']);
        $announcement = Announcement::factory()->create(['status' => 'Draft']);

        $publish = $this->actingAs($user, 'sanctum')->postJson("/api/v1/announcements/{$announcement->id}/publish");
        $publish->assertOk()->assertJsonPath('data.status', 'Published');

        $unpublish = $this->actingAs($user, 'sanctum')->postJson("/api/v1/announcements/{$announcement->id}/unpublish");
        $unpublish->assertOk()->assertJsonPath('data.status', 'Draft');
    }

    public function test_it_uploads_an_announcement_image_to_storage(): void
    {
        Storage::fake('public');
        $user = $this->userWithFullAccess(['Announcements']);
        $announcement = Announcement::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/announcements/{$announcement->id}/image", [
            'image' => UploadedFile::fake()->image('announcement.jpg'),
        ]);

        $response->assertOk();
        $url = $response->json('data.image');
        $this->assertNotNull($url);
        $this->assertStringContainsString('/storage/announcements/', $url);

        $files = Storage::disk('public')->files('announcements');
        $this->assertCount(1, $files);
    }

    public function test_uploading_a_non_image_file_is_rejected(): void
    {
        Storage::fake('public');
        $user = $this->userWithFullAccess(['Announcements']);
        $announcement = Announcement::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/announcements/{$announcement->id}/image", [
            'image' => UploadedFile::fake()->create('document.pdf', 100),
        ]);

        $response->assertStatus(422);
    }

    public function test_guest_cannot_access_announcement_endpoints(): void
    {
        $this->getJson('/api/v1/announcements')->assertStatus(401);
    }

    public function test_a_role_without_announcements_permission_is_forbidden(): void
    {
        $user = User::factory()->create(['role' => 'Trainer']);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/announcements')->assertStatus(403);
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/announcements', [
            'title' => 'Unauthorized', 'audience' => 'All Members', 'publish_date' => now()->toDateString(),
        ])->assertStatus(403);
    }
}
