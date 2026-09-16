<?php

namespace App\Http\Resources;

use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'photo' => $this->photo,
            'role' => $this->role,
            'status' => $this->status,
            'lastLoginAt' => $this->last_login_at,
            'isPlatformAdmin' => $this->isPlatformAdmin(),
            'tenant' => $this->whenLoaded('tenant', fn () => $this->tenant ? [
                'id' => $this->tenant->id,
                'name' => $this->tenant->name,
                'slug' => $this->tenant->slug,
                'status' => $this->tenant->status,
            ] : null),
            'permissions' => RolePermission::query()
                ->where('role', $this->role)
                ->orderBy('module')
                ->get()
                ->map(fn (RolePermission $p) => [
                    'module' => $p->module,
                    'canView' => $p->can_view,
                    'canCreate' => $p->can_create,
                    'canEdit' => $p->can_edit,
                    'canDelete' => $p->can_delete,
                ]),
        ];
    }
}
