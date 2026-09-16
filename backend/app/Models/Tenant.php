<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A gym using the platform (a SaaS tenant). Every tenant-owned model
 * (Member, Payment, Staff/User, ...) belongs to exactly one Tenant via
 * the BelongsToTenant trait, which scopes all queries to the current
 * request's tenant automatically — see App\Support\CurrentTenant and
 * App\Models\Concerns\TenantScope.
 */
#[Fillable(['name', 'slug', 'email', 'phone', 'address', 'logo', 'status', 'timezone', 'currency'])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    /**
     * The single tenant every development seeder/factory attaches records
     * to unless a test explicitly creates a second tenant to verify
     * isolation. Idempotent by slug, so repeated calls (across seeders,
     * factories, and tests within the same database) always resolve to
     * the same row instead of creating duplicates.
     */
    public static function default(): self
    {
        return static::query()->firstOrCreate(
            ['slug' => 'default-dev-gym'],
            [
                'name' => 'Default Development Gym',
                'email' => 'dev@default-gym.test',
                'status' => 'Active',
                'timezone' => 'UTC',
                'currency' => 'USD',
            ],
        );
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(TenantSubscription::class);
    }

    /**
     * The tenant's own gym-admin account — the first user ever created
     * for it. There's no explicit owner_id column; the platform Gym
     * Management screens (Phase 21) treat "first user, oldest first" as
     * the owner, the same convention StaffSeeder/registration already
     * implies by always creating the admin account first.
     */
    public function owner(): HasOne
    {
        return $this->hasOne(User::class)->oldestOfMany();
    }
}
