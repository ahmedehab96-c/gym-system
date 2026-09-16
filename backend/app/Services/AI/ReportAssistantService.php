<?php

namespace App\Services\AI;

use App\Models\Tenant;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\FinanceReportService;
use InvalidArgumentException;

/**
 * Summarizes an already-computed report in plain language (Phase 22
 * §6). Same rule as everywhere else in this namespace: the AI narrates
 * numbers that were already calculated by AnalyticsService/
 * FinanceReportService, it never computes or invents one.
 */
class ReportAssistantService
{
    public const REPORT_TYPES = ['revenue', 'attendance', 'membership_growth', 'expenses'];

    public function __construct(
        private readonly AIRequestRunner $runner,
        private readonly AnalyticsService $analytics,
        private readonly FinanceReportService $finance,
    ) {}

    public function summarize(Tenant $tenant, ?User $user, string $reportType, int $months = 6): array
    {
        if (! in_array($reportType, self::REPORT_TYPES, true)) {
            throw new InvalidArgumentException("Unknown report type: {$reportType}");
        }

        $data = $this->dataFor($reportType, $months);
        $messages = PromptLibrary::reportSummaryMessagesV1($reportType, $data);

        $response = $this->runner->run($tenant, $user, "report_summary:{$reportType}", $messages);

        return [
            'reportType' => $reportType,
            'data' => $data,
            'summary' => $response->content,
            'generatedAt' => now()->toIso8601String(),
        ];
    }

    private function dataFor(string $reportType, int $months): array
    {
        return match ($reportType) {
            'revenue' => [
                'trend' => $this->analytics->revenueTrends($months),
                'overview' => $this->finance->overview(),
            ],
            'expenses' => [
                'trend' => $this->analytics->expenseTrends($months),
                'totalExpenses' => $this->finance->overview()['totalExpenses'],
            ],
            'attendance' => [
                'trend' => $this->analytics->attendanceTrends($months * 30),
            ],
            'membership_growth' => [
                'trend' => $this->analytics->memberGrowth($months),
                'distribution' => $this->analytics->membershipDistribution(),
            ],
            default => [],
        };
    }
}
