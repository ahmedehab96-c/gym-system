<?php

namespace Tests\Feature\Staff;

use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class StaffTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_lists_staff_with_pagination(): void
    {
        $user = $this->userWithFullAccess(['Staff']);
        User::factory()->count(19)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/staff?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.perPage', 5);
    }

    public function test_it_never_exposes_the_password_or_remember_token(): void
    {
        $user = $this->userWithFullAccess(['Staff']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/staff');

        $response->assertOk();
        $keys = array_keys($response->json('data.0'));
        $this->assertNotContains('password', $keys);
        $this->assertNotContains('remember_token', $keys);
        $this->assertNotContains('rememberToken', $keys);
    }

    public function test_it_searches_staff_by_name_email_or_phone(): void
    {
        $user = $this->userWithFullAccess(['Staff']);
        User::factory()->create(['name' => 'Ziad Karim', 'email' => 'ziad@example.com']);
        User::factory()->create(['name' => 'Somebody Else', 'email' => 'else@example.com']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/staff?search=Ziad');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Ziad Karim');
    }

    public function test_it_filters_staff_by_role_and_status(): void
    {
        $user = $this->userWithFullAccess(['Staff']);
        User::factory()->create(['role' => 'Trainer', 'status' => 'Active']);
        User::factory()->create(['role' => 'Trainer', 'status' => 'Inactive']);
        User::factory()->create(['role' => 'Receptionist', 'status' => 'Active']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/staff?role=Trainer&status=Active');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_it_sorts_staff_by_name(): void
    {
        $user = $this->userWithFullAccess(['Staff']);
        User::factory()->create(['name' => 'Zzz Zara']);
        User::factory()->create(['name' => 'Zzz Amir']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/staff?sort_by=name&sort_dir=desc&search=Zzz');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Zzz Zara')
            ->assertJsonPath('data.1.name', 'Zzz Amir');
    }

    public function test_it_creates_a_staff_member(): void
    {
        $user = $this->userWithFullAccess(['Staff']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/staff', [
            'name' => 'New Staff',
            'email' => 'new.staff@example.com',
            'phone' => '+20 100 555 0000',
            'password' => 'password123',
            'role' => 'Receptionist',
            'position' => 'Front Desk',
            'hire_date' => '2026-01-15',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Staff')
            ->assertJsonPath('data.role', 'Receptionist')
            ->assertJsonPath('data.status', 'Active')
            ->assertJsonPath('data.position', 'Front Desk')
            ->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('users', ['email' => 'new.staff@example.com', 'role' => 'Receptionist']);
    }

    public function test_creating_a_staff_member_requires_a_unique_email(): void
    {
        $user = $this->userWithFullAccess(['Staff']);
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/staff', [
            'name' => 'Someone',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'role' => 'Trainer',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_it_shows_a_staff_member(): void
    {
        $user = $this->userWithFullAccess(['Staff']);
        $staff = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/staff/{$staff->id}");

        $response->assertOk()->assertJsonPath('data.id', $staff->id);
    }

    public function test_it_updates_a_staff_member(): void
    {
        $user = $this->userWithFullAccess(['Staff']);
        $staff = User::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/staff/{$staff->id}", [
            'name' => 'Updated Name',
            'position' => 'Manager on Duty',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.position', 'Manager on Duty');
    }

    public function test_updating_without_a_password_keeps_the_existing_one(): void
    {
        $user = $this->userWithFullAccess(['Staff']);
        $staff = User::factory()->create();
        $originalHash = $staff->password;

        $this->actingAs($user, 'sanctum')->putJson("/api/v1/staff/{$staff->id}", ['name' => 'Renamed'])
            ->assertOk();

        $this->assertSame($originalHash, $staff->fresh()->password);
    }

    public function test_it_deletes_a_staff_member(): void
    {
        $user = $this->userWithFullAccess(['Staff']);
        $staff = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/staff/{$staff->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $staff->id]);
    }

    public function test_a_staff_member_cannot_delete_their_own_account(): void
    {
        $user = $this->userWithFullAccess(['Staff']);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/staff/{$user->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_it_activates_and_deactivates_a_staff_member(): void
    {
        $user = $this->userWithFullAccess(['Staff']);
        $staff = User::factory()->create(['status' => 'Active']);

        $deactivate = $this->actingAs($user, 'sanctum')->patchJson("/api/v1/staff/{$staff->id}/status", ['status' => 'Inactive']);
        $deactivate->assertOk()->assertJsonPath('data.status', 'Inactive');

        $activate = $this->actingAs($user, 'sanctum')->patchJson("/api/v1/staff/{$staff->id}/status", ['status' => 'Active']);
        $activate->assertOk()->assertJsonPath('data.status', 'Active');
    }

    public function test_a_staff_member_cannot_deactivate_their_own_account(): void
    {
        $user = $this->userWithFullAccess(['Staff']);

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/v1/staff/{$user->id}/status", ['status' => 'Inactive']);

        $response->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'Active']);
    }

    public function test_it_assigns_a_new_role_to_a_staff_member(): void
    {
        $user = $this->userWithFullAccess(['Staff']);
        $staff = User::factory()->create(['role' => 'Receptionist']);

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/v1/staff/{$staff->id}/role", ['role' => 'Manager']);

        $response->assertOk()->assertJsonPath('data.role', 'Manager');
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'role' => 'Manager']);
    }

    public function test_a_staff_member_cannot_change_their_own_role(): void
    {
        $user = $this->userWithFullAccess(['Staff'], 'Admin');

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/v1/staff/{$user->id}/role", ['role' => 'Super Admin']);

        $response->assertStatus(422);
    }

    public function test_an_admin_cannot_promote_another_staff_member_to_super_admin(): void
    {
        $user = $this->userWithFullAccess(['Staff'], 'Admin');
        $staff = User::factory()->create(['role' => 'Manager']);

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/v1/staff/{$staff->id}/role", ['role' => 'Super Admin']);

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'role' => 'Manager']);
    }

    public function test_a_super_admin_can_promote_another_staff_member_to_super_admin(): void
    {
        $user = $this->userWithFullAccess(['Staff'], 'Super Admin');
        $staff = User::factory()->create(['role' => 'Manager']);

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/v1/staff/{$staff->id}/role", ['role' => 'Super Admin']);

        $response->assertOk()->assertJsonPath('data.role', 'Super Admin');
    }

    public function test_it_uploads_a_staff_photo_to_storage(): void
    {
        Storage::fake('public');
        $user = $this->userWithFullAccess(['Staff']);
        $staff = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/staff/{$staff->id}/photo", [
            'photo' => UploadedFile::fake()->image('staff.jpg'),
        ]);

        $response->assertOk();
        $url = $response->json('data.photo');
        $this->assertNotNull($url);
        $this->assertStringContainsString('/storage/staff/', $url);

        $files = Storage::disk('public')->files('staff');
        $this->assertCount(1, $files);
    }

    public function test_a_role_without_permission_is_forbidden(): void
    {
        RolePermission::factory()->create([
            'role' => 'Trainer', 'module' => 'Staff',
            'can_view' => false, 'can_create' => false, 'can_edit' => false, 'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Trainer']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/staff');
        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_staff_endpoints(): void
    {
        $this->getJson('/api/v1/staff')->assertStatus(401);
    }
}
