<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRolePermissionsRequest;
use App\Http\Resources\RolePermissionResource;
use App\Http\Responses\ApiResponse;
use App\Models\RolePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RolePermissionController extends Controller
{
    private const ROLES = ['Super Admin', 'Admin', 'Manager', 'Receptionist', 'Trainer', 'Accountant'];

    /**
     * The full permission matrix, grouped by role: [{role, permissions: [...]}].
     */
    public function index(): JsonResponse
    {
        $grouped = RolePermission::query()
            ->orderBy('role')
            ->orderBy('module')
            ->get()
            ->groupBy('role')
            ->map(fn ($rows, $role) => [
                'role' => $role,
                'permissions' => RolePermissionResource::collection($rows),
            ])
            ->values()
            ->all();

        return ApiResponse::item($grouped);
    }

    /**
     * Bulk-upserts every module row for one role in a single request, e.g.
     * PUT /roles/Trainer { "permissions": [{ "module": "Classes", "canView": true, ... }] }.
     */
    public function update(UpdateRolePermissionsRequest $request, string $role): JsonResponse
    {
        if (! in_array($role, self::ROLES, true)) {
            return ApiResponse::error('Unknown role.', [], 404);
        }

        DB::transaction(function () use ($request, $role) {
            foreach ($request->validated('permissions') as $entry) {
                RolePermission::query()->updateOrCreate(
                    ['role' => $role, 'module' => $entry['module']],
                    [
                        'can_view' => $entry['canView'],
                        'can_create' => $entry['canCreate'],
                        'can_edit' => $entry['canEdit'],
                        'can_delete' => $entry['canDelete'],
                    ]
                );
            }
        });

        $rows = RolePermission::query()->where('role', $role)->orderBy('module')->get();

        return ApiResponse::item([
            'role' => $role,
            'permissions' => RolePermissionResource::collection($rows),
        ]);
    }
}
