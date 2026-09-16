<?php

namespace App\Services\AI;

use App\Models\Tenant;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\FinanceReportService;
use InvalidArgumentException;

/**
 * Structured, dashboard-ready business insights for one domain at a
 * time (Phase 22 §4). The "metrics" are always real, already-computed
 * figures reused from AnalyticsService/FinanceReportService/
 * GymDataToolService — the AI only ever narrates them, it never
 * generates a number itself.
 */
class InsightService
{
    public const DOMAINS = ['members', 'attendance', 'revenue', 'classes', 'equipment'];

    public function __construct(
        private readonly AIRequestRunner $runner,
        private readonly AnalyticsService $analytics,
        private readonly FinanceReportService $finance,
        private readonly GymDataToolService $tools,
    ) {}

    public function generate(Tenant $tenant, ?User $user, string $domain): array
    {
        if (! in_array($domain, self::DOMAINS, true)) {
            throw new InvalidArgumentException("Unknown insight domain: {$domain}");
        }

        $metrics = $this->metricsFor($domain);
        $messages = PromptLibrary::insightMessagesV1($domain, $metrics);

        $response = $this->runner->run($tenant, $user, "insight:{$domain}", $messages);

        return [
            'domain' => $domain,
            'metrics' => $metrics,
            'summary' => $response->content,
            'generatedAt' => now()->toIso8601String(),
        ];
    }

    private function metricsFor(string $domain): array
    {
        return match ($domain) {
            'members' => [
                'growth' => $this->analytics->memberGrowth(6),
                'membershipDistribution' => $this->analytics->membershipDistribution(),
                'expiringThisWeek' => $this->tools->membershipsExpiringThisWeek()['count'],
                'activeMembers' => $this->tools->activeMemberCount(),
            ],
            'attendance' => [
                'trend' => $this->analytics->attendanceTrends(30),
                'lowAttendanceMembers' => $this->tools->lowAttendanceMembers(),
            ],
            'revenue' => [
                'overview' => $this->finance->overview(),
                'revenueTrend' => $this->analytics->revenueTrends(6),
                'expenseTrend' => $this->analytics->expenseTrends(6),
                'netRevenue' => $this->analytics->netRevenue(6),
            ],
            'classes' => [
                'performance' => $this->analytics->classPerformance(),
            ],
            'equipment' => [
                'stats' => $this->analytics->equipmentStats(),
            ],
            default => [],
        };
    }
}
