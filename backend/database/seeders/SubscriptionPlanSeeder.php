<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\SubscriptionService;
use Illuminate\Database\Seeder;

/**
 * The platform's own pricing catalog, plus an initial subscription for
 * the default development tenant so existing seeded gym data (members,
 * staff, etc.) isn't immediately blocked by a low plan limit — see
 * Phase 19 §6 "Existing Data Migration"-equivalent concern.
 */
class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $free = SubscriptionPlan::factory()->create([
            'name' => 'Free', 'slug' => 'free',
            'description' => 'For a single small gym just getting started.',
            'monthly_price' => 0, 'yearly_price' => 0, 'trial_days' => 0,
            'features' => ['1 staff account', 'Up to 10 members', 'Core gym management'],
            'limits' => ['max_members' => 10, 'max_staff' => 1, 'max_trainers' => 1, 'max_classes' => 5, 'storage_mb' => 50, 'ai_requests' => 0],
            'sort_order' => 1,
        ]);

        $basic = SubscriptionPlan::factory()->create([
            'name' => 'Basic', 'slug' => 'basic',
            'description' => 'For a growing single-location gym.',
            'monthly_price' => 29, 'yearly_price' => 290, 'trial_days' => 14,
            'features' => ['Up to 5 staff accounts', 'Up to 100 members', 'Class scheduling', 'Basic reports'],
            'limits' => ['max_members' => 100, 'max_staff' => 5, 'max_trainers' => 3, 'max_classes' => 20, 'storage_mb' => 500, 'ai_requests' => 0],
            'sort_order' => 2,
        ]);

        $pro = SubscriptionPlan::factory()->create([
            'name' => 'Pro', 'slug' => 'pro',
            'description' => 'For an established gym with a full team.',
            'monthly_price' => 79, 'yearly_price' => 790, 'trial_days' => 14,
            'features' => ['Up to 20 staff accounts', 'Up to 500 members', 'Advanced analytics', 'Priority support'],
            'limits' => ['max_members' => 500, 'max_staff' => 20, 'max_trainers' => 10, 'max_classes' => 100, 'storage_mb' => 5000, 'ai_requests' => 100],
            'sort_order' => 3,
        ]);

        $enterprise = SubscriptionPlan::factory()->create([
            'name' => 'Enterprise', 'slug' => 'enterprise',
            'description' => 'For multi-location gyms and chains with no fixed limits.',
            'monthly_price' => 199, 'yearly_price' => 1990, 'trial_days' => 14,
            'features' => ['Unlimited staff accounts', 'Unlimited members', 'Dedicated support', 'Custom onboarding'],
            'limits' => ['max_members' => null, 'max_staff' => null, 'max_trainers' => null, 'max_classes' => null, 'storage_mb' => null, 'ai_requests' => null],
            'sort_order' => 4,
        ]);

        app(SubscriptionService::class)->start(Tenant::default(), $enterprise, 'Monthly');
    }
}
