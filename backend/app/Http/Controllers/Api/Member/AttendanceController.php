<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Http\Responses\ApiResponse;
use App\Models\AttendanceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 20), 1), 100);

        $paginator = AttendanceRecord::query()
            ->where('member_id', $request->user()->id)
            ->orderByDesc('date')
            ->paginate($perPage)
            ->appends($request->query());

        return ApiResponse::paginated(AttendanceResource::collection($paginator));
    }

    public function summary(Request $request): JsonResponse
    {
        $memberId = $request->user()->id;
        $monthStart = Carbon::now()->startOfMonth();

        $thisMonth = AttendanceRecord::where('member_id', $memberId)
            ->whereBetween('date', [$monthStart, Carbon::now()->endOfMonth()])
            ->count();

        $total = AttendanceRecord::where('member_id', $memberId)->count();

        $lastVisit = AttendanceRecord::where('member_id', $memberId)->orderByDesc('date')->first();

        $openCheckIn = AttendanceRecord::where('member_id', $memberId)
            ->whereDate('date', Carbon::today())
            ->whereNull('check_out')
            ->orderByDesc('id')
            ->first();

        return ApiResponse::item([
            'visitsThisMonth' => $thisMonth,
            'totalVisits' => $total,
            'lastVisitDate' => $lastVisit?->date,
            'currentlyCheckedIn' => (bool) $openCheckIn,
            'attendanceRate' => (int) ($request->user()->attendance_rate ?? 0),
        ]);
    }
}
