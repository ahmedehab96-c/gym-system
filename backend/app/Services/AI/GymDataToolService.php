<?php

namespace App\Services\AI;

use App\Models\MaintenanceRecord;
use App\Models\Member;
use App\Models\Membership;
use App\Services\AnalyticsService;
use App\Services\FinanceReportService;
use Illuminate\Support\Carbon;

/**
 * The ONLY way any AI feature reads gym data — a fixed set of named,
 * whitelisted queries, each already scoped to the authenticated
 * tenant by App\Models\Concerns\TenantScope (every model here uses
 * BelongsToTenant). No AI prompt or response ever builds or runs a
 * query itself (Phase 22 §3/§8: "no arbitrary database access through
 * AI prompts"); it only ever sees the structured arrays this class
 * returns. Reuses AnalyticsService/FinanceReportService for figures
 * that already exist rather than recomputing them.
 */
class GymDataToolService
{
    public function __construct(
        private readonly AnalyticsService $analytics,
        private readonly FinanceReportService $finance,
    ) {}

    public function activeMemberCount(): int
    {
        return Member::query()->where('status', 'Active')->count();
    }

    /**
     * @return array{count: int, members: array<int, array{name: string, expiryDate: string, planName: ?string}>}
     */
    public function membershipsExpiringThisWeek(): array
    {
        $today = Carbon::today();
        $memberships = Membership::query()
            ->with(['member', 'plan'])
            ->where('status', '!=', 'Expired')
            ->whereBetween('expiry_date', [$today->toDateString(), $today->copy()->addDays(7)->toDateString()])
            ->orderBy('expiry_date')
            ->get();

        return [
            'count' => $memberships->count(),
            'members' => $memberships->map(fn (Membership $m) => [
                'name' => $m->member?->name,
                'expiryDate' => $m->expiry_date->toDateString(),
                'planName' => $m->plan?->name,
            ])->all(),
        ];
    }

    public function thisMonthRevenue(): int
    {
        return $this->finance->overview()['monthly'];
    }

    /**
     * @return array<int, array{name: string, category: ?string, booked: int, capacity: int, fillRate: int}>
     */
    public function popularClasses(int $limit = 5): array
    {
        return collect($this->analytics->classPerformance())
            ->take($limit)
            ->map(fn (array $c) => [
                'name' => $c['name'],
                'category' => $c['category'],
                'booked' => $c['booked'],
                'capacity' => $c['capacity'],
                'fillRate' => $c['fillRate'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{overdueCount: int, upcomingCount: int, equipment: array<int, array{name: string, status: string, dueDate: ?string}>}
     */
    public function equipmentNeedingMaintenance(int $limit = 10): array
    {
        $today = Carbon::today();

        $overdueQuery = fn () => MaintenanceRecord::query()->where(function ($query) use ($today) {
            $query->where('status', 'Overdue')
                ->orWhere(function ($q) use ($today) {
                    $q->where('status', 'Upcoming')->whereDate('date', '<', $today);
                });
        });

        $overdueCount = $overdueQuery()->count();
        $overdue = $overdueQuery()->with('equipment')->orderBy('date')->limit($limit)->get();

        $upcomingCount = MaintenanceRecord::query()
            ->where('status', 'Upcoming')
            ->whereDate('date', '>=', $today)
            ->count();

        return [
            'overdueCount' => $overdueCount,
            'upcomingCount' => $upcomingCount,
            'equipment' => $overdue->map(fn (MaintenanceRecord $r) => [
                'name' => $r->equipment?->name,
                'status' => $r->status,
                'dueDate' => $r->date?->toDateString(),
            ])->all(),
        ];
    }

    /**
     * @return array<int, array{name: string, attendanceRate: int}>
     */
    public function lowAttendanceMembers(int $threshold = 30, int $limit = 10): array
    {
        return Member::query()
            ->where('status', 'Active')
            ->where('attendance_rate', '<', $threshold)
            ->orderBy('attendance_rate')
            ->limit($limit)
            ->get(['name', 'attendance_rate'])
            ->map(fn (Member $m) => ['name' => $m->name, 'attendanceRate' => $m->attendance_rate])
            ->all();
    }

    /**
     * A bounded snapshot of the gym's current state, fed to the AI
     * assistant as its only source of truth (Phase 22 §3).
     */
    public function snapshot(): array
    {
        return [
            'activeMembers' => $this->activeMemberCount(),
            'membershipsExpiringThisWeek' => $this->membershipsExpiringThisWeek(),
            'thisMonthRevenue' => $this->thisMonthRevenue(),
            'popularClasses' => $this->popularClasses(),
            'equipmentNeedingMaintenance' => $this->equipmentNeedingMaintenance(),
            'lowAttendanceMembers' => $this->lowAttendanceMembers(),
        ];
    }
}
