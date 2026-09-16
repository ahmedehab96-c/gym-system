<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Support\MembershipStatus;
use Illuminate\Support\Carbon;

/**
 * Centralizes membership lifecycle transitions (create/renew/change plan/
 * suspend/cancel) so both MemberController and MembershipController drive
 * the same logic and keep the Member row's denormalized plan/date/status
 * columns in sync with its latest Membership record.
 */
class MembershipService
{
    public function create(Member $member, MembershipPlan $plan, ?string $startDate = null, ?int $price = null): Membership
    {
        $start = $startDate ? Carbon::parse($startDate) : Carbon::today();
        $expiry = $start->copy()->addDays($plan->duration_days);

        $membership = Membership::create([
            'member_id' => $member->id,
            'plan_id' => $plan->id,
            'start_date' => $start->toDateString(),
            'expiry_date' => $expiry->toDateString(),
            'price' => $price ?? $plan->price,
            'status' => MembershipStatus::resolveMembershipStatus($expiry->toDateString()),
        ]);

        $this->syncMember($member, $membership);

        return $membership;
    }

    public function renew(Membership $membership): Membership
    {
        $plan = $membership->plan;
        $base = Carbon::parse($membership->expiry_date)->isFuture()
            ? Carbon::parse($membership->expiry_date)
            : Carbon::today();
        $expiry = $base->copy()->addDays($plan->duration_days);

        $membership->update([
            'start_date' => Carbon::today()->toDateString(),
            'expiry_date' => $expiry->toDateString(),
            'price' => $plan->price,
            'status' => MembershipStatus::resolveMembershipStatus($expiry->toDateString()),
        ]);

        $this->syncMember($membership->member, $membership->fresh());

        return $membership->fresh();
    }

    public function changePlan(Membership $membership, MembershipPlan $newPlan): Membership
    {
        $start = Carbon::today();
        $expiry = $start->copy()->addDays($newPlan->duration_days);

        $membership->update([
            'plan_id' => $newPlan->id,
            'start_date' => $start->toDateString(),
            'expiry_date' => $expiry->toDateString(),
            'price' => $newPlan->price,
            'status' => MembershipStatus::resolveMembershipStatus($expiry->toDateString()),
        ]);

        $this->syncMember($membership->member, $membership->fresh());

        return $membership->fresh();
    }

    public function suspend(Membership $membership): Membership
    {
        $membership->update(['status' => 'Suspended']);
        $membership->member->update(['status' => 'Suspended']);

        return $membership->fresh();
    }

    public function cancel(Membership $membership): Membership
    {
        $membership->update([
            'status' => 'Expired',
            'expiry_date' => Carbon::today()->toDateString(),
        ]);
        $membership->member->update(['status' => 'Inactive']);

        return $membership->fresh();
    }

    private function syncMember(Member $member, Membership $membership): void
    {
        $member->update([
            'plan_id' => $membership->plan_id,
            'start_date' => $membership->start_date,
            'expiry_date' => $membership->expiry_date,
            'status' => MembershipStatus::resolveMemberStatus($membership->expiry_date),
        ]);
    }
}
