<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\GymClassResource;
use App\Http\Resources\MemberResource;
use App\Http\Resources\PaymentResource;
use App\Http\Responses\ApiResponse;
use App\Models\AppNotification;
use App\Models\AttendanceRecord;
use App\Models\GymClass;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * One aggregated "home screen" read for the Flutter Member Mobile App
 * (Phase 25 §3) — everything the dashboard needs in a single round trip,
 * built entirely from existing tenant-scoped relations/models; no new
 * business logic, just a member-scoped read.
 */
class DashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = $request->user()->load(['plan', 'trainer']);

        $attendanceThisMonth = AttendanceRecord::query()
            ->where('member_id', $member->id)
            ->whereBetween('date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->count();

        $upcomingClasses = GymClass::query()
            ->with(['trainer'])
            ->whereHas('bookings', fn ($q) => $q->where('member_id', $member->id))
            ->where(function ($q) {
                $q->whereNull('date')->orWhere('date', '>=', Carbon::today()->toDateString());
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(5)
            ->get();

        $recentPayments = $member->payments()->orderByDesc('date')->limit(5)->get();

        $unreadNotifications = AppNotification::query()->where('member_id', $member->id)->where('read', false)->count();

        return ApiResponse::item([
            'member' => new MemberResource($member),
            'attendanceThisMonth' => $attendanceThisMonth,
            'upcomingClasses' => GymClassResource::collection($upcomingClasses),
            'recentPayments' => PaymentResource::collection($recentPayments),
            'unreadNotifications' => $unreadNotifications,
        ]);
    }
}
