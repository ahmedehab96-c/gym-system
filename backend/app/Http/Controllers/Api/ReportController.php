<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\EquipmentResource;
use App\Http\Resources\ExpenseResource;
use App\Http\Resources\GymClassResource;
use App\Http\Resources\MemberResource;
use App\Http\Resources\MembershipResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\TrainerResource;
use App\Http\Responses\ApiResponse;
use App\Models\AttendanceRecord;
use App\Models\Equipment;
use App\Models\Expense;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\Trainer;
use App\Services\ReportService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function members(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request, 365);

        $query = Member::query()->with(['plan', 'trainer']);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('member_id', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($planId = $request->integer('plan_id')) {
            $query->where('plan_id', $planId);
        }

        if ($trainerId = $request->integer('trainer_id')) {
            $query->where('trainer_id', $trainerId);
        }

        $query->whereDate('join_date', '>=', $from)->whereDate('join_date', '<=', $to);

        $paginator = (clone $query)->orderByDesc('join_date')->paginate($this->perPage($request))->appends($request->query());

        $summary = [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('status', 'Active')->count(),
            'inactive' => (clone $query)->where('status', 'Inactive')->count(),
            'suspended' => (clone $query)->where('status', 'Suspended')->count(),
            'expired' => (clone $query)->where('status', 'Expired')->count(),
            'averageAttendanceRate' => (int) round((clone $query)->avg('attendance_rate') ?? 0),
            'totalBalanceDue' => (int) (clone $query)->sum('balance_due'),
        ];

        $chart = $this->reports->series(clone $query, 'join_date', null, 'count', $this->group($request), $from, $to);

        return ApiResponse::item([
            'summary' => $summary,
            'chart' => $chart,
            'meta' => $this->meta($paginator),
            'data' => MemberResource::collection($paginator->items()),
        ]);
    }

    public function memberships(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request, 365);

        $query = Membership::query()->with(['member', 'plan']);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->whereHas('member', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($planId = $request->integer('plan_id')) {
            $query->where('plan_id', $planId);
        }

        $query->whereDate('start_date', '>=', $from)->whereDate('start_date', '<=', $to);

        $paginator = (clone $query)->orderByDesc('start_date')->paginate($this->perPage($request))->appends($request->query());

        $summary = [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('status', 'Active')->count(),
            'expiringSoon' => (clone $query)->where('status', 'Expiring Soon')->count(),
            'expired' => (clone $query)->where('status', 'Expired')->count(),
            'suspended' => (clone $query)->where('status', 'Suspended')->count(),
            'totalValue' => (int) (clone $query)->sum('price'),
        ];

        $chart = $this->reports->series(clone $query, 'start_date', 'price', 'sum', $this->group($request), $from, $to);

        return ApiResponse::item([
            'summary' => $summary,
            'chart' => $chart,
            'meta' => $this->meta($paginator),
            'data' => MembershipResource::collection($paginator->items()),
        ]);
    }

    public function attendance(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request, 30);

        $query = AttendanceRecord::query()->with('member');

        if ($search = $request->string('search')->trim()->toString()) {
            $query->whereHas('member', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
        }

        if ($memberId = $request->integer('member_id')) {
            $query->where('member_id', $memberId);
        }

        if ($method = $request->string('method')->toString()) {
            $query->where('method', $method);
        }

        $query->whereDate('date', '>=', $from)->whereDate('date', '<=', $to);

        $paginator = (clone $query)->orderByDesc('date')->orderByDesc('check_in')->paginate($this->perPage($request))->appends($request->query());

        $summary = [
            'totalVisits' => (clone $query)->count(),
            'uniqueMembers' => (clone $query)->distinct('member_id')->count('member_id'),
            'checkedIn' => (clone $query)->whereNull('check_out')->count(),
            'checkedOut' => (clone $query)->whereNotNull('check_out')->count(),
        ];

        $chart = $this->reports->series(clone $query, 'date', null, 'count', $this->group($request, 'daily'), $from, $to);

        return ApiResponse::item([
            'summary' => $summary,
            'chart' => $chart,
            'meta' => $this->meta($paginator),
            'data' => AttendanceResource::collection($paginator->items()),
        ]);
    }

    public function revenue(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request, 365);

        $query = Payment::query()->with('member');

        if ($search = $request->string('search')->trim()->toString()) {
            $query->whereHas('member', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
        }

        $status = $request->string('status')->toString() ?: 'Paid';
        $query->where('status', $status);

        if ($method = $request->string('method')->toString()) {
            $query->where('method', $method);
        }

        $query->whereDate('date', '>=', $from)->whereDate('date', '<=', $to);

        $paginator = (clone $query)->orderByDesc('date')->paginate($this->perPage($request))->appends($request->query());

        $count = (clone $query)->count();
        $total = (int) (clone $query)->sum('amount');

        $summary = [
            'total' => $total,
            'count' => $count,
            'average' => $count > 0 ? (int) round($total / $count) : 0,
            'byMethod' => $this->reports->groupedSums(clone $query, 'method', 'amount'),
        ];

        $chart = $this->reports->series(clone $query, 'date', 'amount', 'sum', $this->group($request), $from, $to);

        return ApiResponse::item([
            'summary' => $summary,
            'chart' => $chart,
            'meta' => $this->meta($paginator),
            'data' => PaymentResource::collection($paginator->items()),
        ]);
    }

    public function expenses(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request, 365);

        $query = Expense::query();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('vendor', 'like', "%{$search}%");
            });
        }

        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }

        $query->whereDate('date', '>=', $from)->whereDate('date', '<=', $to);

        $paginator = (clone $query)->orderByDesc('date')->paginate($this->perPage($request))->appends($request->query());

        $count = (clone $query)->count();
        $total = (int) (clone $query)->sum('amount');

        $summary = [
            'total' => $total,
            'count' => $count,
            'average' => $count > 0 ? (int) round($total / $count) : 0,
            'byCategory' => $this->reports->groupedSums(clone $query, 'category', 'amount'),
        ];

        $chart = $this->reports->series(clone $query, 'date', 'amount', 'sum', $this->group($request), $from, $to);

        return ApiResponse::item([
            'summary' => $summary,
            'chart' => $chart,
            'meta' => $this->meta($paginator),
            'data' => ExpenseResource::collection($paginator->items()),
        ]);
    }

    public function trainers(Request $request): JsonResponse
    {
        $query = Trainer::query()->withCount(['members as assigned_members_count', 'gymClasses as classes_count']);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('specialty', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($specialty = $request->string('specialty')->toString()) {
            $query->where('specialty', $specialty);
        }

        $paginator = (clone $query)->orderByDesc('rating')->paginate($this->perPage($request))->appends($request->query());

        $summary = [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('status', 'Active')->count(),
            'onLeave' => (clone $query)->where('status', 'On Leave')->count(),
            'inactive' => (clone $query)->where('status', 'Inactive')->count(),
            'averageRating' => round((float) ((clone $query)->avg('rating') ?? 0), 2),
            'totalSessionsCompleted' => (int) (clone $query)->sum('sessions_completed'),
        ];

        return ApiResponse::item([
            'summary' => $summary,
            'chart' => [],
            'meta' => $this->meta($paginator),
            'data' => TrainerResource::collection($paginator->items()),
        ]);
    }

    public function classes(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request, 30, 30);

        $query = GymClass::query()->with('trainer')->withCount('bookings');

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }

        if ($trainerId = $request->integer('trainer_id')) {
            $query->where('trainer_id', $trainerId);
        }

        $query->whereDate('date', '>=', $from)->whereDate('date', '<=', $to);

        $paginator = (clone $query)->orderBy('date')->orderBy('start_time')->paginate($this->perPage($request))->appends($request->query());

        $classes = (clone $query)->get();
        $totalBookings = $classes->sum('bookings_count');
        $averageFillRate = $classes->count() > 0
            ? (int) round($classes->avg(fn ($class) => $class->capacity > 0 ? $class->bookings_count / $class->capacity * 100 : 0))
            : 0;

        $summary = [
            'total' => (clone $query)->count(),
            'scheduled' => (clone $query)->where('status', 'Scheduled')->count(),
            'full' => (clone $query)->where('status', 'Full')->count(),
            'cancelled' => (clone $query)->where('status', 'Cancelled')->count(),
            'completed' => (clone $query)->where('status', 'Completed')->count(),
            'totalBookings' => $totalBookings,
            'averageFillRate' => $averageFillRate,
        ];

        $chart = $this->reports->series(clone $query, 'date', null, 'count', $this->group($request), $from, $to);

        return ApiResponse::item([
            'summary' => $summary,
            'chart' => $chart,
            'meta' => $this->meta($paginator),
            'data' => GymClassResource::collection($paginator->items()),
        ]);
    }

    public function equipment(Request $request): JsonResponse
    {
        $query = Equipment::query();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($condition = $request->string('condition')->toString()) {
            $query->where('condition', $condition);
        }

        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }

        $paginator = (clone $query)->orderBy('name')->paginate($this->perPage($request))->appends($request->query());

        $summary = [
            'total' => (clone $query)->count(),
            'byStatus' => $this->reports->counts(clone $query, 'status', ['In Use', 'Under Maintenance', 'Retired']),
            'byCondition' => $this->reports->counts(clone $query, 'condition', ['Excellent', 'Good', 'Needs Maintenance', 'Out of Service']),
        ];

        return ApiResponse::item([
            'summary' => $summary,
            'chart' => [],
            'meta' => $this->meta($paginator),
            'data' => EquipmentResource::collection($paginator->items()),
        ]);
    }

    /**
     * @return array{0: string, 1: string} [from, to] date strings
     */
    private function range(Request $request, int $defaultDaysBack, int $defaultDaysForward = 0): array
    {
        $to = $request->string('to')->toString() ?: Carbon::today()->addDays($defaultDaysForward)->toDateString();
        $from = $request->string('from')->toString() ?: Carbon::parse($to)->subDays($defaultDaysBack + $defaultDaysForward)->toDateString();

        return [$from, $to];
    }

    private function group(Request $request, string $default = 'monthly'): string
    {
        $group = $request->string('group')->lower()->toString();

        return in_array($group, ['daily', 'weekly', 'monthly', 'yearly'], true) ? $group : $default;
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 15), 1), 100);
    }

    private function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'page' => $paginator->currentPage(),
            'perPage' => $paginator->perPage(),
            'total' => $paginator->total(),
            'totalPages' => $paginator->lastPage(),
        ];
    }
}
