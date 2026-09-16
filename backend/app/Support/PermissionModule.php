<?php

namespace App\Support;

/**
 * Canonical list of `role_permissions.module` values — the single source
 * of truth for both the dev/demo seeder (Database\Seeders\StaffSeeder)
 * and the production-safe bootstrap command
 * (App\Console\Commands\GrantFullRolePermissions), so the two can never
 * silently drift apart (a module added to one and forgotten in the
 * other is exactly how Announcements shipped with no permission gate
 * at all — see the Phase 30 audit).
 */
class PermissionModule
{
    public const ALL = [
        'Members', 'Memberships', 'Attendance', 'Trainers', 'Classes', 'Equipment',
        'Maintenance', 'Payments', 'Invoices', 'Expenses', 'Reports', 'Staff', 'Settings', 'AI',
        'Announcements',
    ];
}
