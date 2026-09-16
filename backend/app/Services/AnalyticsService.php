<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Equipment;
use App\Models\GymClass;
use App\Models\MaintenanceRecord;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Trainer;
use Illuminate\Support\Carbon;

/**
 * Chart-ready aggregates for the Analytics page. Time-series methods reuse
 * the FinanceReportService for revenue/expense figures rather than
 * recomputing them, keeping a single source of truth for those numbers.
 */
class AnalyticsService
{
    private const MEMBERSHIP_STATUS_COLORS = [
        'Active' => '#22c55e',
        'Expiring Soon' => '#d4a72f',
        'Expired' => '#e11d48',
        'Suspended' => '#6b7280',
    ];

    public function __construct(private readonly FinanceReportService $finance) {}

    public function memberGrowth(int $months = 12): array
    {
        $start = Carbon::today()->startOfMonth()->subMonths($months - 1);

        $joinedByMonth = Member::query()
            ->where('join_date', '>=', $start->toDateString())
            ->get(['join_date'])
            ->countBy(fn (Member $member) => $member->join_date->format('Y-m'));

        $running = Member::query()->where('join_date', '<', $start->toDateString())->count();

        return collect(range(0, $months - 1))
            ->map(function (int $offset) use ($start, $joinedByMonth, &$running) {
                $month = $start->copy()->addMonths($offset);
                $key = $month->format('Y-m');
                $running += $joinedByMonth->get($key, 0);

                return [
                    'month' => $month->format('M'),
                    'members' => $running,
                    'newMembers' => $joinedByMonth->get($key, 0),
                ];
            })
            ->all();
    }

    public function attendanceTrends(int $days = 30): array
    {
        $since = Carbon::today()->subDays($days - 1);

        $counts = AttendanceRecord::query()
            ->where('date', '>=', $since->toDateString())
            ->get(['date'])
            ->countBy(fn (AttendanceRecord $record) => $record->date->toDateString());

        return collect(range(0, $days - 1))
            ->map(function (int $offset) use ($since, $counts) {
                $date = $since->copy()->addDays($offset);

                return [
                    'date' => $date->toDateString(),
                    'day' => $date->format('D'),
                    'visits' => $counts->get($date->toDateString(), 0),
                ];
            })
            ->all();
    }

    public function revenueTrends(int $months = 12): array
    {
        return collect($this->finance->revenueOverTime($months))
            ->map(fn (array $row) => ['month' => $row['month'], 'revenue' => $row['revenue']])
            ->all();
    }

    public function expenseTrends(int $months = 12): array
    {
        return collect($this->finance->revenueOverTime($months))
            ->map(fn (array $row) => ['month' => $row['month'], 'expenses' => $row['expenses']])
            ->all();
    }

    public function netRevenue(int $months = 12): array
    {
        $overview = $this->finance->overview();

        return [
            'totalRevenue' => $overview['totalRevenue'],
            'totalExpenses' => $overview['totalExpenses'],
            'netRevenue' => $overview['netRevenue'],
            'trend' => collect($this->finance->revenueOverTime($months))
                ->map(fn (array $row) => [
                    'month' => $row['month'],
                    'net' => $row['revenue'] - $row['expenses'],
                ])
                ->all(),
        ];
    }

    public function membershipDistribution(): array
    {
        $counts = Membership::query()->get(['status'])->countBy('status');

        return collect(self::MEMBERSHIP_STATUS_COLORS)
            ->map(fn (string $color, string $status) => [
                'name' => $status,
                'value' => $counts->get($status, 0),
                'color' => $color,
            ])
            ->values()
            ->all();
    }

    public function revenueByMethod(): array
    {
        return $this->finance->overview()['revenueByMethod']->all();
    }

    public function classPerformance(): array
    {
        return GymClass::query()
            ->with('trainer')
            ->withCount('bookings')
            ->get()
            ->map(fn (GymClass $class) => [
                'id' => $class->id,
                'name' => $class->name,
                'category' => $class->category,
                'trainerName' => $class->trainer?->name,
                'capacity' => $class->capacity,
                'booked' => $class->bookings_count,
                'fillRate' => $class->capacity > 0 ? (int) round($class->bookings_count / $class->capacity * 100) : 0,
                'status' => $class->status,
            ])
            ->sortByDesc('fillRate')
            ->values()
            ->all();
    }

    public function trainerPerformance(): array
    {
        return Trainer::query()
            ->withCount(['members as assigned_members_count', 'gymClasses as classes_count'])
            ->get()
            ->map(fn (Trainer $trainer) => [
                'id' => $trainer->id,
                'name' => $trainer->name,
                'specialty' => $trainer->specialty,
                'status' => $trainer->status,
                'rating' => (float) $trainer->rating,
                'sessionsCompleted' => $trainer->sessions_completed,
                'assignedMembers' => $trainer->assigned_members_count,
                'classesCount' => $trainer->classes_count,
            ])
            ->sortByDesc('rating')
            ->values()
            ->all();
    }

    public function equipmentStats(): array
    {
        $today = Carbon::today();

        $byStatus = Equipment::query()->get(['status'])->countBy('status')
            ->map(fn (int $count, string $status) => ['status' => $status, 'count' => $count])
            ->values();

        $byCondition = Equipment::query()->get(['condition'])->countBy('condition')
            ->map(fn (int $count, string $condition) => ['condition' => $condition, 'count' => $count])
            ->values();

        return [
            'total' => Equipment::query()->count(),
            'byStatus' => $byStatus,
            'byCondition' => $byCondition,
            'maintenance' => [
                'upcoming' => MaintenanceRecord::query()->where('status', 'Upcoming')->whereDate('date', '>=', $today)->count(),
                'overdue' => MaintenanceRecord::query()
                    ->where(function ($query) use ($today) {
                        $query->where('status', 'Overdue')
                            ->orWhere(function ($q) use ($today) {
                                $q->where('status', 'Upcoming')->whereDate('date', '<', $today);
                            });
                    })->count(),
                'completed' => MaintenanceRecord::query()->where('status', 'Completed')->count(),
                'totalCost' => (int) MaintenanceRecord::query()->sum('cost'),
            ],
        ];
    }
}
