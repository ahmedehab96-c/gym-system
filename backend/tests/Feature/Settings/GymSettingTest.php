<?php

namespace Tests\Feature\Settings;

use App\Models\GymSetting;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class GymSettingTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_returns_the_current_settings(): void
    {
        $user = $this->userWithFullAccess(['Settings']);
        GymSetting::factory()->create(['name' => 'Premium Gym', 'currency' => 'EGP']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/settings');

        $response->assertOk()
            ->assertJsonPath('data.name', 'Premium Gym')
            ->assertJsonPath('data.currency', 'EGP');
    }

    public function test_it_creates_a_default_settings_row_when_none_exists(): void
    {
        $user = $this->userWithFullAccess(['Settings']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/settings');

        $response->assertOk()->assertJsonPath('data.name', 'My Gym');
        $this->assertDatabaseCount('gym_settings', 1);
    }

    public function test_it_updates_settings(): void
    {
        $user = $this->userWithFullAccess(['Settings']);
        GymSetting::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/v1/settings', [
            'name' => 'New Gym Name',
            'phone' => '+20 111 222 3333',
            'working_days' => ['Monday', 'Tuesday', 'Wednesday'],
            'open_time' => '07:00',
            'close_time' => '22:00',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'language' => 'Arabic',
            'notify_email' => false,
            'notify_sms' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Gym Name')
            ->assertJsonPath('data.phone', '+20 111 222 3333')
            ->assertJsonPath('data.currency', 'USD')
            ->assertJsonPath('data.timezone', 'UTC')
            ->assertJsonPath('data.notifyEmail', false)
            ->assertJsonPath('data.notifySms', true);

        $this->assertSame(['Monday', 'Tuesday', 'Wednesday'], $response->json('data.workingDays'));
    }

    public function test_it_rejects_an_invalid_working_day(): void
    {
        $user = $this->userWithFullAccess(['Settings']);
        GymSetting::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/v1/settings', [
            'working_days' => ['Funday'],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('working_days.0');
    }

    public function test_it_uploads_a_logo_to_storage(): void
    {
        Storage::fake('public');
        $user = $this->userWithFullAccess(['Settings']);
        GymSetting::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/settings/logo', [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);

        $response->assertOk();
        $url = $response->json('data.logoUrl');
        $this->assertNotNull($url);
        $this->assertStringContainsString('/storage/settings/', $url);

        $files = Storage::disk('public')->files('settings');
        $this->assertCount(1, $files);
    }

    public function test_a_role_without_settings_permission_is_forbidden(): void
    {
        RolePermission::factory()->create([
            'role' => 'Trainer', 'module' => 'Settings',
            'can_view' => false, 'can_create' => false, 'can_edit' => false, 'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Trainer']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/settings');
        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_settings_endpoints(): void
    {
        $this->getJson('/api/v1/settings')->assertStatus(401);
    }
}
