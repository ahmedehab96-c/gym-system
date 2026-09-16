<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckInRequest;
use App\Http\Resources\AttendanceResource;
use App\Http\Responses\ApiResponse;
use App\Models\AttendanceRecord;
use App\Models\Member;
use App\Support\AttendanceDuration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $this->filteredQuery($request);

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(AttendanceResource::collection($paginator));
    }

    public function forMember(Request $request, Member $member): JsonResponse
    {
        $query = $this->filteredQuery($request)->where('member_id', $member->id);

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(AttendanceResource::collection($paginator));
    }

    public function today(): JsonResponse
    {
        $records = AttendanceRecord::query()
            ->with('member')
            ->whereDate('date', Carbon::today())
            ->orderBy('check_in')
            ->get();

        return ApiResponse::item(AttendanceResource::collection($records));
    }

    public function stats(): JsonResponse
    {
        $today = AttendanceRecord::query()->whereDate('date', Carbon::today())->get(['check_out']);

        $daily = $this->dailyCounts(13);
        $weekly = collect($daily)->slice(-7)->values();

        $monthly = $this->monthlyCounts(11);

        return ApiResponse::item([
            'today' => [
                'presentToday' => $today->count(),
                'checkedIn' => $today->whereNull('check_out')->count(),
                'checkedOut' => $today->whereNotNull('check_out')->count(),
                'averageAttendanceRate' => (int) round(Member::query()->avg('attendance_rate') ?? 0),
            ],
            'daily' => $daily,
            'weekly' => $weekly,
            'monthly' => $monthly,
            'averageSessionMinutes' => $this->averageSessionMinutes(30),
        ]);
    }

    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $member = Member::findOrFail($request->validated('member_id'));

        if ($member->status !== 'Active') {
            return ApiResponse::error("This member's membership is {$member->status} and cannot check in.", [], 422);
        }

        $hasActiveCheckIn = AttendanceRecord::query()
            ->where('member_id', $member->id)
            ->whereNull('check_out')
            ->exists();

        if ($hasActiveCheckIn) {
            return ApiResponse::error('This member already has an active check-in. Please check out first.', [], 422);
        }

        $record = AttendanceRecord::create([
            'member_id' => $member->id,
            'date' => Carbon::today()->toDateString(),
            'check_in' => Carbon::now()->format('G:i'),
            'method' => $request->validated('method') ?? 'Manual',
        ]);

        $record->load('member');

        return ApiResponse::item(new AttendanceResource($record), 201);
    }

    public function checkOut(AttendanceRecord $attendance): JsonResponse
    {
        if ($attendance->check_out !== null) {
            return ApiResponse::error('This attendance record is already checked out.', [], 422);
        }

        $checkOut = Carbon::now();
        $attendance->update([
            'check_out' => $checkOut->format('G:i'),
            'duration' => AttendanceDuration::format(AttendanceDuration::minutesBetween($attendance->check_in, $checkOut->format('G:i'))),
        ]);

        $attendance->load('member');

        return ApiResponse::item(new AttendanceResource($attendance));
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = AttendanceRecord::query()->with('member');

        if ($date = $request->string('date')->toString()) {
            $query->whereDate('date', $date);
        }

        if ($memberId = $request->integer('member_id')) {
            $query->where('member_id', $memberId);
        }

        if ($status = $request->string('status')->toString()) {
            $status === 'Checked Out' ? $query->whereNotNull('check_out') : $query->whereNull('check_out');
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->whereHas('member', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
        }

        return $query->orderByDesc('date')->orderByDesc('check_in');
    }

    private function dailyCounts(int $daysBack): array
    {
        $since = Carbon::today()->subDays($daysBack);

        $counts = AttendanceRecord::query()
            ->where('date', '>=', $since->toDateString())
            ->get(['date'])
            ->countBy(fn ($record) => $record->date->toDateString());

        return collect(range(0, $daysBack))
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

    private function monthlyCounts(int $monthsBack): array
    {
        $since = Carbon::today()->startOfMonth()->subMonths($monthsBack);

        $counts = AttendanceRecord::query()
            ->where('date', '>=', $since->toDateString())
            ->get(['date'])
            ->countBy(fn ($record) => $record->date->format('Y-m'));

        return collect(range(0, $monthsBack))
            ->map(function (int $offset) use ($since, $counts) {
                $month = $since->copy()->addMonths($offset);

                return [
                    'month' => $month->format('M'),
                    'visits' => $counts->get($month->format('Y-m'), 0),
                ];
            })
            ->all();
    }

    private function averageSessionMinutes(int $daysBack): int
    {
        $since = Carbon::today()->subDays($daysBack);

        $durations = AttendanceRecord::query()
            ->where('date', '>=', $since->toDateString())
            ->whereNotNull('duration')
            ->pluck('duration');

        if ($durations->isEmpty()) {
            return 0;
        }

        $totalMinutes = $durations->sum(function (string $duration) {
            preg_match('/(\d+)h/', $duration, $hours);
            preg_match('/(\d+)m/', $duration, $minutes);

            return ((int) ($hours[1] ?? 0)) * 60 + (int) ($minutes[1] ?? 0);
        });

        return (int) round($totalMinutes / $durations->count());
    }
}
