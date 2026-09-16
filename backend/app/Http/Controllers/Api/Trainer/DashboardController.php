<?php

namespace App\Http\Controllers\Api\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Resources\GymClassResource;
use App\Http\Resources\TrainerResource;
use App\Http\Responses\ApiResponse;
use App\Models\AppNotification;
use App\Models\AttendanceRecord;
use App\Models\ClassBooking;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Trainer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * One aggregated "home screen" read for the Flutter Trainer App (Phase
 * 26 §2) — same composition-only approach as Member\DashboardController
 * in Phase 25: no new business logic, just existing tenant-scoped
 * models queried and assembled into a single response.
 */
class DashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $trainer = Trainer::query()->where('user_id', $request->user()->id)->firstOrFail();
        $today = Carbon::today();

        $todaysClasses = GymClass::query()
            ->where('trainer_id', $trainer->id)
            ->whereDate('date', $today)
            ->withCount('bookings')
            ->orderBy('start_time')
            ->get();

        $upcomingClasses = GymClass::query()
            ->where('trainer_id', $trainer->id)
            ->whereDate('date', '>', $today->toDateString())
            ->withCount('bookings')
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(5)
            ->get();

        $assignedMemberIds = Member::query()->where('trainer_id', $trainer->id)->pluck('id');

        $todaysAttendance = AttendanceRecord::query()
            ->whereIn('member_id', $assignedMemberIds)
            ->whereDate('date', $today)
            ->count();

        $unreadNotifications = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->where('read', false)
            ->count();

        $recentBookings = ClassBooking::query()
            ->whereHas('gymClass', fn ($q) => $q->where('trainer_id', $trainer->id))
            ->with(['member', 'gymClass'])
            ->orderByDesc('booked_at')
            ->limit(5)
            ->get()
            ->map(fn (ClassBooking $booking) => [
                'memberName' => $booking->member?->name,
                'className' => $booking->gymClass?->name,
                'bookedAt' => $booking->booked_at,
            ]);

        return ApiResponse::item([
            'trainer' => new TrainerResource($trainer),
            'todaysClasses' => GymClassResource::collection($todaysClasses),
            'upcomingClasses' => GymClassResource::collection($upcomingClasses),
            'assignedMembersCount' => $assignedMemberIds->count(),
            'todaysAttendance' => $todaysAttendance,
            'unreadNotifications' => $unreadNotifications,
            'recentActivity' => $recentBookings,
        ]);
    }
}
