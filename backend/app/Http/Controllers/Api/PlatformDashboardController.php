<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Http\Resources\SubscriptionInvoiceResource;
use App\Http\Resources\TenantResource;
use App\Http\Resources\TenantSubscriptionResource;
use App\Http\Responses\ApiResponse;
use App\Models\AuditLog;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Platform-wide aggregates for the Super Admin dashboard — every number
 * here is computed live from Tenant/TenantSubscription/SubscriptionInvoice,
 * never hardcoded (Phase 21 requirement).
 */
class PlatformDashboardController extends Controller
{
    public function summary(): JsonResponse
    {
        $activeSubscriptions = TenantSubscription::query()->where('status', 'Active')->get(['billing_cycle', 'price']);

        $mrr = $activeSubscriptions->sum(
            fn (TenantSubscription $s) => $s->billing_cycle === 'Yearly' ? $s->price / 12 : $s->price,
        );

        $yearlyRevenue = SubscriptionInvoice::query()
            ->where('status', 'Paid')
            ->whereYear('paid_date', now()->year)
            ->sum('amount');

        return ApiResponse::item([
            'totalGyms' => Tenant::query()->count(),
            'activeGyms' => Tenant::query()->where('status', 'Active')->count(),
            'trialGyms' => Tenant::query()->where('status', 'Trial')->count(),
            'suspendedGyms' => Tenant::query()->where('status', 'Suspended')->count(),
            'activeSubscriptions' => $activeSubscriptions->count(),
            'expiredSubscriptions' => TenantSubscription::query()->where('status', 'Expired')->count(),
            'mrr' => round($mrr),
            'yearlyRevenue' => (int) $yearlyRevenue,
        ]);
    }

    public function charts(): JsonResponse
    {
        return ApiResponse::item([
            'tenantGrowth' => $this->monthlyCounts(Tenant::query()),
            'subscriptionGrowth' => $this->monthlyCounts(TenantSubscription::query()),
            'revenueTrend' => $this->monthlyRevenue(),
            'planDistribution' => $this->planDistribution(),
        ]);
    }

    public function activity(): JsonResponse
    {
        return ApiResponse::item([
            'recentGyms' => TenantResource::collection(Tenant::query()->latest()->limit(5)->get()),
            'recentSubscriptions' => TenantSubscriptionResource::collection(
                TenantSubscription::query()->with(['tenant', 'plan'])->latest()->limit(5)->get(),
            ),
            'recentBilling' => SubscriptionInvoiceResource::collection(
                SubscriptionInvoice::query()->with(['tenant', 'plan'])->latest('issue_date')->limit(5)->get(),
            ),
            'recentActivity' => AuditLogResource::collection(
                AuditLog::query()->with('tenant')->latest()->limit(10)->get(),
            ),
        ]);
    }

    /**
     * Buckets by calendar month in PHP rather than a driver-specific SQL
     * date-format function — this needs to run unchanged against both
     * MySQL (production) and SQLite (the test suite).
     *
     * @return array<int, array{month: string, count: int}>
     */
    private function monthlyCounts(Builder $query): array
    {
        $dates = $query->where('created_at', '>=', now()->subMonths(11)->startOfMonth())->pluck('created_at');
        $counts = $dates->countBy(fn ($date) => Carbon::parse($date)->format('Y-m'));

        return $this->fillLastTwelveMonths($counts);
    }

    /** @return array<int, array{month: string, revenue: int}> */
    private function monthlyRevenue(): array
    {
        $rows = SubscriptionInvoice::query()
            ->where('status', 'Paid')
            ->whereNotNull('paid_date')
            ->where('paid_date', '>=', now()->subMonths(11)->startOfMonth())
            ->get(['paid_date', 'amount']);

        $sums = $rows
            ->groupBy(fn (SubscriptionInvoice $invoice) => Carbon::parse($invoice->paid_date)->format('Y-m'))
            ->map(fn ($group) => $group->sum('amount'));

        return array_map(
            fn (array $point) => ['month' => $point['month'], 'revenue' => (int) $point['count']],
            $this->fillLastTwelveMonths($sums),
        );
    }

    /** @param Collection<string, int> $counts */
    private function fillLastTwelveMonths($counts): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $months[] = ['month' => $date->format('M Y'), 'count' => (int) ($counts[$key] ?? 0)];
        }

        return $months;
    }

    /** @return array<int, array{plan: string, count: int}> */
    private function planDistribution(): array
    {
        return TenantSubscription::query()
            ->join('subscription_plans', 'subscription_plans.id', '=', 'tenant_subscriptions.plan_id')
            ->where('tenant_subscriptions.status', 'Active')
            ->groupBy('subscription_plans.id', 'subscription_plans.name')
            ->orderBy('subscription_plans.sort_order')
            ->select('subscription_plans.name as plan', DB::raw('COUNT(*) as count'))
            ->get()
            ->map(fn ($row) => ['plan' => $row->plan, 'count' => (int) $row->count])
            ->all();
    }
}
