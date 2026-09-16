<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfills the 'AI' module into role_permissions for every existing
 * install — StaffSeeder only runs on a fresh database, so a tenant that
 * already ran it needs this data migration to get AI gated at all
 * (see App\Http\Middleware\CheckPermission). Mirrors
 * database/seeders/StaffSeeder.php's permissionsFor() defaults exactly;
 * kept inline (not shared code) since migrations are a historical
 * snapshot, not something that should shift if the seeder changes later.
 */
return new class extends Migration
{
    public function up(): void
    {
        $roles = ['Super Admin', 'Admin', 'Manager', 'Receptionist', 'Trainer', 'Accountant'];

        foreach ($roles as $role) {
            $permissions = match ($role) {
                'Super Admin' => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
                'Admin' => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
                'Manager' => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false],
                // Mirrors Accountant's existing 'Reports' access — report
                // summarization/insights are squarely their use case.
                'Accountant' => ['can_view' => true, 'can_create' => true, 'can_edit' => false, 'can_delete' => false],
                default => ['can_view' => false, 'can_create' => false, 'can_edit' => false, 'can_delete' => false],
            };

            DB::table('role_permissions')->updateOrInsert(
                ['role' => $role, 'module' => 'AI'],
                array_merge($permissions, ['created_at' => now(), 'updated_at' => now()]),
            );
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')->where('module', 'AI')->delete();
    }
};
