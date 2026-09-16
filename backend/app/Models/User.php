<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * tenant_id and is_platform_admin are deliberately NOT fillable — they
 * must never be settable via mass assignment from request input (staff
 * creation always inherits the acting admin's own tenant via
 * BelongsToTenant's creating hook; platform-admin status is only ever
 * set directly, e.g. via tinker, never through an API request).
 */
#[Fillable(['name', 'photo', 'email', 'phone', 'password', 'role', 'status', 'position', 'hire_date', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use BelongsToTenant, HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'hire_date' => 'date',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
        ];
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    /**
     * A platform-level user who manages multiple gyms rather than
     * belonging to one (see Phase 18 §8 — no such dashboard exists yet,
     * this is only the data-model distinction it will build on).
     */
    public function isPlatformAdmin(): bool
    {
        return (bool) $this->is_platform_admin;
    }

    /**
     * Whether this user's role has the given ability on the given module,
     * per the role_permissions matrix (Backend Blueprint §authorization).
     */
    public function hasPermission(string $module, string $ability): bool
    {
        $column = match ($ability) {
            'view' => 'can_view',
            'create' => 'can_create',
            'edit' => 'can_edit',
            'delete' => 'can_delete',
            default => null,
        };

        if ($column === null) {
            return false;
        }

        return RolePermission::query()
            ->where('role', $this->role)
            ->where('module', $module)
            ->where($column, true)
            ->exists();
    }
}
