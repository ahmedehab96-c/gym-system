<?php

namespace App\Models;

use Database\Factories\RolePermissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['role', 'module', 'can_view', 'can_create', 'can_edit', 'can_delete'])]
class RolePermission extends Model
{
    /** @use HasFactory<RolePermissionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'can_view' => 'boolean',
            'can_create' => 'boolean',
            'can_edit' => 'boolean',
            'can_delete' => 'boolean',
        ];
    }
}
