<?php

namespace App\Console\Commands;

use App\Models\RolePermission;
use App\Support\PermissionModule;
use Illuminate\Console\Command;

/**
 * Production bootstrap for a fresh deployment (Phase 30 audit): every
 * permission-gated route calls User::hasPermission(), which is 100%
 * data-driven off `role_permissions` — there is no "Super Admin bypasses
 * every check" shortcut. `database/seeders/StaffSeeder` populates a full
 * matrix, but it's the demo seeder and — correctly — is never meant to
 * run in production (see DEPLOYMENT.md/RELEASE_CHECKLIST.md). Without
 * it, a freshly created production Super Admin has an empty permission
 * matrix and can't do anything permission-gated — including opening the
 * Roles & Permissions screen itself (`PUT /roles/{role}` requires
 * `permission:Settings`), so there is no way to self-service the very
 * first grant through the UI.
 *
 * This command is the safe, idempotent, non-demo way to bootstrap that:
 * it only ever grants full access to ONE named role for every real
 * module (App\Support\PermissionModule::ALL) — no fake staff/members/
 * payments are created, unlike StaffSeeder.
 */
class GrantFullRolePermissions extends Command
{
    protected $signature = 'permissions:grant-all {role? : The role to grant full access to every module (default: Super Admin)}';

    protected $description = 'Grant one role full (view/create/edit/delete) access to every module — for bootstrapping a fresh production deployment\'s first Super Admin';

    public function handle(): int
    {
        // A default containing a space doesn't survive the signature
        // string's own parsing reliably, so it's handled here instead of
        // via `{role=Super Admin}`.
        $role = $this->argument('role') ?? 'Super Admin';

        foreach (PermissionModule::ALL as $module) {
            RolePermission::query()->updateOrCreate(
                ['role' => $role, 'module' => $module],
                ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            );
        }

        $this->info('Granted "'.$role.'" full access to '.count(PermissionModule::ALL).' modules: '.implode(', ', PermissionModule::ALL));

        return self::SUCCESS;
    }
}
