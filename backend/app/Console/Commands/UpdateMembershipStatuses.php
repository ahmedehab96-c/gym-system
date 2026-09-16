<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\Membership;
use App\Services\NotificationService;
use App\Support\MembershipStatus;
use Illuminate\Console\Command;

/**
 * Recomputes membership/member lifecycle statuses from their expiry dates
 * (Active / Expiring Soon / Expired), without disturbing records a staff
 * member has explicitly Suspended or made Inactive. Notifies staff exactly
 * once per transition, via the same NotificationService calls the manual
 * renew/change-plan/cancel actions already use.
 */
class UpdateMembershipStatuses extends Command
{
    protected $signature = 'memberships:update-statuses';

    protected $description = 'Recalculate membership and member statuses based on expiry dates';

    public function __construct(private readonly NotificationService $notifications)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $membershipsUpdated = 0;

        Membership::query()->with('member')->chunkById(200, function ($memberships) use (&$membershipsUpdated) {
            foreach ($memberships as $membership) {
                $status = MembershipStatus::resolveMembershipStatus($membership->expiry_date, $membership->status);

                if ($status !== $membership->status) {
                    $membership->update(['status' => $status]);
                    $membershipsUpdated++;

                    match ($status) {
                        'Expiring Soon' => $this->notifications->membershipExpiring($membership),
                        'Expired' => $this->notifications->membershipExpired($membership),
                        default => null,
                    };
                }
            }
        });

        $membersUpdated = 0;

        Member::query()->whereNotNull('expiry_date')->chunkById(200, function ($members) use (&$membersUpdated) {
            foreach ($members as $member) {
                $status = MembershipStatus::resolveMemberStatus($member->expiry_date, $member->status);

                if ($status !== $member->status) {
                    $member->update(['status' => $status]);
                    $membersUpdated++;
                }
            }
        });

        $this->info("Updated {$membershipsUpdated} membership(s) and {$membersUpdated} member(s).");

        return self::SUCCESS;
    }
}
