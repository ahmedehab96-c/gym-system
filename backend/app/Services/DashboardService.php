<?php

namespace App\Services;

use App\Http\Resources\GymClassResource;
use App\Http\Resources\MemberResource;
use App\Http\Resources\MembershipResource;
use App\Http\Resources\PaymentResource;
use App\Models\AttendanceRecord;
use App\Models\ClassBooking;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\Trainer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Backend calculations for the main admin Dashboard: the stat cards, the
 * "expiring soon" and "upcoming" widgets, and the recent-record lists.
 * Every figure is derived live from the database — nothing is hardcoded.
 */
class DashboardService
{
    public function summary(): array
    {
        $today = Carbon::today();
        $expiringUntil = $today->copy()->addDays(7);

        return [
            'stats' => [
                'totalMembers' => Member::query()->count(),
                'activeMembers' => Member::query()->where('status', 'Active')->count(),
                'newMembers' => Member::query()->where('join_date', '>=', $today->copy()->subDays(30)->toDateString())->count(),
                'expiredMemberships' => Membership::query()->where('status', 'Expired')->count(),
                'todayAttendance' => AttendanceRecord::query()->whereDate('date', $today)->count(),
                'monthlyRevenue' => (int) Payment::query()->where('status', 'Paid')->whereBetween('date', [
                    $today->copy()->startOfMonth()->toDateString(),
                    $today->copy()->endOfMonth()->toDateString(),
                ])->sum('amount'),
                'pendingPayments' => Payment::query()->where('status', 'Pending')->count(),
                'activeTrainers' => Trainer::query()->where('status', 'Active')->count(),
                'expiringMemberships' => Membership::query()->where('status', 'Expiring Soon')->count(),
            ],
            'recentMembers' => MemberResource::collection(
                Member::query()->with(['plan'])->latest('join_date')->latest('id')->take(5)->get()
            ),
            'recentPayments' => PaymentResource::collection(
                Payment::query()->with('member')->latest('date')->latest('id')->take(5)->get()
            ),
            'upcomingClasses' => GymClassResource::collection(
                GymClass::query()->with('trainer')->withCount('bookings')
                    ->where('date', '>=', $today->toDateString())
                    ->where('status', '!=', 'Cancelled')
                    ->orderBy('date')->orderBy('start_time')
                    ->take(5)->get()
            ),
            'expiringMemberships' => MembershipResource::collection(
                Membership::query()->with(['member', 'plan'])
                    ->where('status', 'Expiring Soon')
                    ->whereBetween('expiry_date', [$today->toDateString(), $expiringUntil->toDateString()])
                    ->orderBy('expiry_date')
                    ->take(5)->get()
            ),
        ];
    }

    /**
     * A merged, most-recent-first feed of member joins, payments, class
     * bookings and check-ins — there is no dedicated activity log table, so
     * this is assembled from the natural "recent records" of each module.
     */
    public function activity(int $limit = 10): array
    {
        $members = Member::query()->latest('join_date')->latest('id')->take($limit)->get(['id', 'name', 'join_date'])
            ->map(fn (Member $member) => [
                'type' => 'member_joined',
                'title' => 'New member joined',
                'description' => $member->name,
                'memberId' => $member->id,
                'date' => optional($member->join_date)->toDateString(),
            ]);

        $payments = Payment::query()->with('member')->where('status', 'Paid')->latest('date')->latest('id')->take($limit)->get()
            ->map(fn (Payment $payment) => [
                'type' => 'payment_received',
                'title' => 'Payment received',
                'description' => ($payment->member?->name ?? 'A member')." paid {$payment->amount}",
                'memberId' => $payment->member_id,
                'date' => optional($payment->date)->toDateString(),
            ]);

        $bookings = ClassBooking::query()->with(['member', 'gymClass'])->latest('booked_at')->take($limit)->get()
            ->map(fn (ClassBooking $booking) => [
                'type' => 'class_booked',
                'title' => 'Class booked',
                'description' => ($booking->member?->name ?? 'A member').' booked '.($booking->gymClass?->name ?? 'a class'),
                'memberId' => $booking->member_id,
                'date' => optional($booking->booked_at)->toDateString(),
            ]);

        $checkIns = AttendanceRecord::query()->with('member')->latest('date')->latest('id')->take($limit)->get()
            ->map(fn (AttendanceRecord $record) => [
                'type' => 'check_in',
                'title' => 'Member checked in',
                'description' => $record->member?->name ?? 'A member',
                'memberId' => $record->member_id,
                'date' => optional($record->date)->toDateString(),
            ]);

        return $this->mergeMostRecent($limit, $members, $payments, $bookings, $checkIns);
    }

    private function mergeMostRecent(int $limit, Collection ...$feeds): array
    {
        return collect($feeds)
            ->collapse()
            ->sortByDesc('date')
            ->take($limit)
            ->values()
            ->map(fn (array $event, int $index) => ['id' => $index + 1, ...$event])
            ->all();
    }
}
