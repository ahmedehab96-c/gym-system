<?php

namespace Tests\Concerns;

use App\Models\RolePermission;
use App\Models\Tenant;
use App\Models\Trainer;
use App\Models\User;

trait InteractsWithTrainerAuth
{
    /**
     * @return array{0: User, 1: Trainer} the staff account and its linked roster row
     */
    protected function trainerAccount(?Tenant $tenant = null, array $userAttributes = [], array $trainerAttributes = []): array
    {
        $tenant ??= Tenant::default();
        $this->seedTrainerRolePermissions();

        $user = User::factory()->create(array_merge(['tenant_id' => $tenant->id, 'role' => 'Trainer', 'status' => 'Active'], $userAttributes));
        $trainer = Trainer::factory()->create(array_merge(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'status' => 'Active'], $trainerAttributes));

        return [$user, $trainer];
    }

    /**
     * Mirrors database/seeders/StaffSeeder's real Trainer role matrix
     * exactly (view-only on Members/Attendance/Classes/Trainers, no
     * create/edit/delete anywhere) — a fresh RefreshDatabase test never
     * runs seeders, so without this every permission:-gated route would
     * 403 regardless of what's actually being tested.
     *
     * updateOrCreate per module (not a blanket "skip if any row exists"
     * guard) because a data migration (Phase 22's AI-module backfill)
     * already inserts a Trainer/AI row at migration time — a guard that
     * only checked "does any Trainer row exist" would short-circuit on
     * that one row and skip seeding every other module entirely.
     */
    private function seedTrainerRolePermissions(): void
    {
        $viewableModules = ['Members', 'Attendance', 'Classes', 'Trainers'];
        $allModules = ['Members', 'Memberships', 'Attendance', 'Trainers', 'Classes', 'Equipment', 'Maintenance', 'Payments', 'Invoices', 'Expenses', 'Reports', 'Staff', 'Settings', 'AI'];

        foreach ($allModules as $module) {
            RolePermission::query()->updateOrCreate(
                ['role' => 'Trainer', 'module' => $module],
                [
                    'can_view' => in_array($module, $viewableModules, true),
                    'can_create' => false,
                    'can_edit' => false,
                    'can_delete' => false,
                ],
            );
        }
    }
}
