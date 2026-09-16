<?php

namespace Tests\Concerns;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Services\AI\Contracts\AIProviderContract;
use App\Services\AI\Exceptions\AIProviderException;
use Tests\Fakes\FakeAIProvider;

trait InteractsWithAI
{
    /** Swaps the real AI provider for a fake one — no test ever makes a real network call. */
    protected function bindFakeAIProvider(string $content = 'This is a fake AI response.', ?AIProviderException $throw = null): FakeAIProvider
    {
        $fake = new FakeAIProvider($content, $throw);
        $this->app->instance(AIProviderContract::class, $fake);

        return $fake;
    }

    /** A tenant with an Active subscription on a plan allowing $aiLimit AI requests this period (null = unlimited). */
    protected function tenantWithAIPlan(?int $aiLimit = 50, ?Tenant $tenant = null): Tenant
    {
        $tenant ??= Tenant::default();

        $plan = SubscriptionPlan::factory()->create([
            'limits' => [
                'max_members' => null, 'max_staff' => null, 'max_trainers' => null,
                'max_classes' => null, 'storage_mb' => null, 'ai_requests' => $aiLimit,
            ],
        ]);

        TenantSubscription::factory()->active()->create(['tenant_id' => $tenant->id, 'plan_id' => $plan->id]);

        return $tenant;
    }

    /** A staff user (default role has full AI access — see the 'AI' RolePermission rows) on a tenant with AI enabled. */
    protected function aiEnabledAdmin(?int $aiLimit = 50, string $role = 'Super Admin', ?Tenant $tenant = null): User
    {
        $tenant = $this->tenantWithAIPlan($aiLimit, $tenant);

        return User::factory()->create(['role' => $role, 'tenant_id' => $tenant->id]);
    }
}
