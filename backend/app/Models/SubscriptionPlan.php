<?php

namespace App\Models;

use Database\Factories\SubscriptionPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A platform-level pricing tier (Free/Basic/Pro/Enterprise) a Tenant can
 * subscribe to. Deliberately NOT tenant-scoped — this is the shared
 * catalog every gym on the platform chooses from, same as RolePermission
 * is a shared, platform-wide capability matrix rather than per-tenant.
 */
#[Fillable(['name', 'slug', 'description', 'monthly_price', 'yearly_price', 'trial_days', 'features', 'limits', 'status', 'sort_order'])]
class SubscriptionPlan extends Model
{
    /** @use HasFactory<SubscriptionPlanFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'limits' => 'array',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class, 'plan_id');
    }

    /**
     * A single limit's ceiling, or null for "unlimited". See
     * App\Services\SubscriptionLimitService for the only place that
     * should ever read this.
     */
    public function limit(string $key): ?int
    {
        $value = $this->limits[$key] ?? null;

        return $value === null ? null : (int) $value;
    }
}
