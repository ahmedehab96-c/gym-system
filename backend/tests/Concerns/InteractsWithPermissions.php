<?php

namespace Tests\Concerns;

use App\Models\RolePermission;
use App\Models\Tenant;
use App\Models\User;

trait InteractsWithPermissions
{
    /**
     * A user whose role has full CRUD permission on the given modules.
     * Role permissions are platform-wide (not tenant-scoped, by design —
     * see Phase 18), so $tenantId only controls which gym the *user*
     * belongs to, not the RolePermission rows created here.
     */
    protected function userWithFullAccess(array $modules, string $role = 'Super Admin', ?int $tenantId = null): User
    {
        foreach ($modules as $module) {
            RolePermission::factory()->create([
                'role' => $role,
                'module' => $module,
                'can_view' => true,
                'can_create' => true,
                'can_edit' => true,
                'can_delete' => true,
            ]);
        }

        return User::factory()->create(array_filter([
            'role' => $role,
            'tenant_id' => $tenantId,
        ], fn ($value) => $value !== null));
    }

    protected function otherTenant(): Tenant
    {
        return Tenant::factory()->create();
    }
}
