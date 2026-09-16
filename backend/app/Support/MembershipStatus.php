<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Derives a membership/member lifecycle status from an expiry date, per the
 * same rule the dev seeders use: expired if past, "expiring" within 7 days
 * of expiry, active otherwise.
 */
class MembershipStatus
{
    public static function resolveMembershipStatus(?string $expiryDate, ?string $currentStatus = null): string
    {
        if ($currentStatus === 'Suspended') {
            return 'Suspended';
        }

        if (! $expiryDate) {
            return 'Active';
        }

        $daysUntil = Carbon::now()->startOfDay()->diffInDays(Carbon::parse($expiryDate)->startOfDay(), false);

        return match (true) {
            $daysUntil < 0 => 'Expired',
            $daysUntil <= 7 => 'Expiring Soon',
            default => 'Active',
        };
    }

    public static function resolveMemberStatus(?string $expiryDate, ?string $currentStatus = null): string
    {
        if (in_array($currentStatus, ['Suspended', 'Inactive'], true)) {
            return $currentStatus;
        }

        if (! $expiryDate) {
            return 'Active';
        }

        return Carbon::parse($expiryDate)->startOfDay()->isPast() ? 'Expired' : 'Active';
    }
}
