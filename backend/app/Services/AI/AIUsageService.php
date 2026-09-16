<?php

namespace App\Services\AI;

use App\Models\AIUsageLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AI\DTO\AIResponse;
use App\Services\SubscriptionLimitService;
use App\Services\SubscriptionService;
use Illuminate\Support\Carbon;

/**
 * The single place that knows whether a tenant may make another AI
 * request and the single place that records one having happened — see
 * Phase 22 §2: "Do not duplicate this logic." Every AI feature service
 * (GymAssistantService, InsightService, ...) and
 * App\Http\Middleware\EnforceAIUsageLimit go through canUseAI()/
 * recordAIUsage() here instead of querying ai_usage_logs themselves.
 */
class AIUsageService
{
    public function __construct(
        private readonly SubscriptionLimitService $limits,
        private readonly SubscriptionService $subscriptions,
    ) {}

    /**
     * False if the tenant's plan disables AI outright (limit 0), or if
     * they've already used this billing period's quota. Reuses
     * SubscriptionLimitService::canUseAI() for the "does this plan allow
     * AI at all" check rather than re-deriving it.
     */
    public function canUseAI(Tenant $tenant): bool
    {
        if (! $this->limits->canUseAI($tenant)) {
            return false;
        }

        $limit = $this->planLimit($tenant);

        if ($limit === null) {
            return true;
        }

        return $this->usageThisPeriod($tenant) < $limit;
    }

    public function recordAIUsage(
        Tenant $tenant,
        ?User $user,
        string $feature,
        string $provider,
        string $status,
        ?AIResponse $response = null,
        ?float $estimatedCost = null,
    ): AIUsageLog {
        return AIUsageLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user?->id,
            'feature' => $feature,
            'provider' => $provider,
            'model' => $response?->model,
            'prompt_tokens' => $response?->promptTokens,
            'completion_tokens' => $response?->completionTokens,
            'total_tokens' => $response?->totalTokens,
            'estimated_cost' => $estimatedCost,
            'status' => $status,
        ]);
    }

    public function usageThisPeriod(Tenant $tenant): int
    {
        return AIUsageLog::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'success')
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->count();
    }

    /**
     * Dashboard-ready usage stats for the /ai/usage endpoint.
     */
    public function stats(Tenant $tenant): array
    {
        $limit = $this->planLimit($tenant);
        $used = $this->usageThisPeriod($tenant);

        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        $totalTokensThisPeriod = (int) AIUsageLog::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->sum('total_tokens');

        $byFeature = AIUsageLog::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->selectRaw('feature, COUNT(*) as total')
            ->groupBy('feature')
            ->pluck('total', 'feature');

        return [
            'used' => $used,
            'limit' => $limit,
            'remaining' => $limit === null ? null : max(0, $limit - $used),
            'totalTokensThisPeriod' => $totalTokensThisPeriod,
            'byFeature' => $byFeature,
            'periodStart' => $periodStart->toDateString(),
            'periodEnd' => $periodEnd->toDateString(),
        ];
    }

    private function planLimit(Tenant $tenant): ?int
    {
        return $this->subscriptions->current($tenant)?->plan?->limit('ai_requests');
    }
}
