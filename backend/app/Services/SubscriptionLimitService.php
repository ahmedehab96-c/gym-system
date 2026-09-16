<?php

namespace App\Services;

use App\Models\GymClass;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\Trainer;
use App\Models\User;

/**
 * The single place that knows how to check a tenant's plan limits and
 * current usage — see Phase 19 §4: "Do NOT scatter plan checks throughout
 * controllers." Controllers/middleware call canCreate*() or usage();
 * nothing else in the app reads SubscriptionPlan::limits directly.
 */
class SubscriptionLimitService
{
    private const RESOURCES = [
        'members' => ['model' => Member::class, 'limit_key' => 'max_members'],
        'staff' => ['model' => User::class, 'limit_key' => 'max_staff'],
        'trainers' => ['model' => Trainer::class, 'limit_key' => 'max_trainers'],
        'classes' => ['model' => GymClass::class, 'limit_key' => 'max_classes'],
    ];

    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function canCreateMember(Tenant $tenant): bool
    {
        return $this->canCreate($tenant, 'members');
    }

    public function canCreateStaff(Tenant $tenant): bool
    {
        return $this->canCreate($tenant, 'staff');
    }

    public function canCreateTrainer(Tenant $tenant): bool
    {
        return $this->canCreate($tenant, 'trainers');
    }

    public function canCreateClass(Tenant $tenant): bool
    {
        return $this->canCreate($tenant, 'classes');
    }

    /**
     * No AI feature exists yet (see Phase 19 constraints), so there is no
     * real usage counter to check against — this only reflects whether
     * the tenant's plan allows AI at all (limit 0 = disabled on this
     * plan, null = unlimited, any positive number = enabled). A real
     * per-request usage counter can be added here later without changing
     * this method's signature.
     */
    public function canUseAI(Tenant $tenant): bool
    {
        $limit = $this->planLimit($tenant, 'ai_requests');

        return $limit === null || $limit > 0;
    }

    public function canCreate(Tenant $tenant, string $resource): bool
    {
        if (! isset(self::RESOURCES[$resource])) {
            return true;
        }

        $limit = $this->planLimit($tenant, self::RESOURCES[$resource]['limit_key']);

        if ($limit === null) {
            return true;
        }

        return $this->used($tenant, $resource) < $limit;
    }

    /**
     * Dashboard-ready usage across every configurable limit. `limit: null`
     * means unlimited on that tenant's current plan (or no plan catalog
     * exists yet at all — see SubscriptionService::current()).
     */
    public function usage(Tenant $tenant): array
    {
        $plan = $this->subscriptions->current($tenant)?->plan;

        if (! $plan) {
            $usage = [];
            foreach (self::RESOURCES as $resource => $config) {
                $usage[$resource] = ['used' => $this->used($tenant, $resource), 'limit' => null];
            }
            $usage['storage'] = ['used' => 0, 'limit' => null];
            $usage['ai'] = ['used' => 0, 'limit' => null];

            return $usage;
        }

        $usage = [];
        foreach (self::RESOURCES as $resource => $config) {
            $usage[$resource] = [
                'used' => $this->used($tenant, $resource),
                'limit' => $plan->limit($config['limit_key']),
            ];
        }

        // Storage and AI usage aren't metered anywhere in the app yet
        // (uploads aren't stored per-tenant on disk, and no AI feature
        // exists) — reported as 0 rather than fabricated, so the
        // dashboard has a stable shape to render against once they are.
        $usage['storage'] = ['used' => 0, 'limit' => $plan->limit('storage_mb')];
        $usage['ai'] = ['used' => 0, 'limit' => $plan->limit('ai_requests')];

        return $usage;
    }

    private function used(Tenant $tenant, string $resource): int
    {
        $model = self::RESOURCES[$resource]['model'];

        return $model::query()->where('tenant_id', $tenant->id)->count();
    }

    private function planLimit(Tenant $tenant, string $key): ?int
    {
        // No resolvable subscription/plan (e.g. the platform's plan
        // catalog hasn't been seeded yet) -> treat as unlimited rather
        // than blocking every create action in the app.
        return $this->subscriptions->current($tenant)?->plan?->limit($key);
    }
}
