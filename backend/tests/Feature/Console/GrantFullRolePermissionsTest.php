<?php

namespace Tests\Feature\Console;

use App\Models\RolePermission;
use App\Support\PermissionModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrantFullRolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_grants_a_role_full_access_to_every_module(): void
    {
        $this->artisan('permissions:grant-all', ['role' => 'Super Admin'])->assertSuccessful();

        foreach (PermissionModule::ALL as $module) {
            $this->assertDatabaseHas('role_permissions', [
                'role' => 'Super Admin',
                'module' => $module,
                'can_view' => true,
                'can_create' => true,
                'can_edit' => true,
                'can_delete' => true,
            ]);
        }
    }

    public function test_it_defaults_to_super_admin_when_no_role_is_given(): void
    {
        $this->artisan('permissions:grant-all')->assertSuccessful();

        $this->assertDatabaseHas('role_permissions', ['role' => 'Super Admin', 'module' => 'Settings', 'can_view' => true]);
    }

    public function test_it_is_idempotent_and_never_downgrades_an_existing_grant(): void
    {
        RolePermission::factory()->create([
            'role' => 'Super Admin', 'module' => 'Members',
            'can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false,
        ]);

        $this->artisan('permissions:grant-all', ['role' => 'Super Admin'])->assertSuccessful();

        $this->assertDatabaseHas('role_permissions', [
            'role' => 'Super Admin', 'module' => 'Members',
            'can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true,
        ]);
        $this->assertSame(1, RolePermission::where('role', 'Super Admin')->where('module', 'Members')->count());
    }

    public function test_it_only_grants_the_named_role_never_others(): void
    {
        // A prior data migration (add_ai_module_role_permissions) already
        // backfills every role's 'AI' row, so "no Super Admin row at all"
        // isn't the right assertion here — "Members" is never touched by
        // that migration, so it's a clean signal this command didn't
        // touch any role but the one it was asked for.
        $this->artisan('permissions:grant-all', ['role' => 'Receptionist'])->assertSuccessful();

        $this->assertDatabaseMissing('role_permissions', ['role' => 'Super Admin', 'module' => 'Members']);
        $this->assertDatabaseHas('role_permissions', ['role' => 'Receptionist', 'module' => 'Members', 'can_view' => true]);
    }
}
