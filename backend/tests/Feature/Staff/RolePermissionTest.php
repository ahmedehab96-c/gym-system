<?php

namespace Tests\Feature\Staff;

use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_returns_the_permission_matrix_grouped_by_role(): void
    {
        $user = $this->userWithFullAccess(['Settings']);
        RolePermission::factory()->create([
            'role' => 'Trainer', 'module' => 'Classes',
            'can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false,
        ]);
        RolePermission::factory()->create([
            'role' => 'Trainer', 'module' => 'Members',
            'can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/roles');

        $response->assertOk();
        $trainerGroup = collect($response->json('data'))->firstWhere('role', 'Trainer');
        $this->assertNotNull($trainerGroup);
        // 2 explicit rows above + the 'AI' module row every role already
        // has, backfilled by the add_ai_module_role_permissions migration.
        $this->assertCount(3, $trainerGroup['permissions']);
        $classesPermission = collect($trainerGroup['permissions'])->firstWhere('module', 'Classes');
        $this->assertTrue($classesPermission['canView']);
        $this->assertFalse($classesPermission['canEdit']);
    }

    public function test_it_bulk_updates_a_roles_permissions(): void
    {
        $user = $this->userWithFullAccess(['Settings']);
        RolePermission::factory()->create([
            'role' => 'Receptionist', 'module' => 'Members',
            'can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/v1/roles/Receptionist', [
            'permissions' => [
                ['module' => 'Members', 'canView' => true, 'canCreate' => true, 'canEdit' => true, 'canDelete' => false],
                ['module' => 'Payments', 'canView' => true, 'canCreate' => false, 'canEdit' => false, 'canDelete' => false],
            ],
        ]);

        $response->assertOk()->assertJsonPath('data.role', 'Receptionist');
        $this->assertDatabaseHas('role_permissions', [
            'role' => 'Receptionist', 'module' => 'Members', 'can_create' => true, 'can_edit' => true,
        ]);
        $this->assertDatabaseHas('role_permissions', [
            'role' => 'Receptionist', 'module' => 'Payments', 'can_view' => true,
        ]);
    }

    public function test_updating_an_unknown_role_returns_not_found(): void
    {
        $user = $this->userWithFullAccess(['Settings']);

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/v1/roles/NotARole', [
            'permissions' => [
                ['module' => 'Members', 'canView' => true, 'canCreate' => false, 'canEdit' => false, 'canDelete' => false],
            ],
        ]);

        $response->assertStatus(404);
    }

    public function test_a_role_without_settings_permission_is_forbidden(): void
    {
        RolePermission::factory()->create([
            'role' => 'Trainer', 'module' => 'Settings',
            'can_view' => false, 'can_create' => false, 'can_edit' => false, 'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Trainer']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/roles');
        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_role_endpoints(): void
    {
        $this->getJson('/api/v1/roles')->assertStatus(401);
    }
}
