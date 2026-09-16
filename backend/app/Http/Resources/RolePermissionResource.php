<?php

namespace App\Http\Resources;

use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RolePermission */
class RolePermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'module' => $this->module,
            'canView' => $this->can_view,
            'canCreate' => $this->can_create,
            'canEdit' => $this->can_edit,
            'canDelete' => $this->can_delete,
        ];
    }
}
