<?php

namespace App\Support;

/**
 * Shared "H:i" check-in/check-out duration math — used by both the
 * staff-facing manual check-out (AttendanceController) and the QR
 * check-out endpoint (Phase 28), so the calculation only ever lives in
 * one place.
 */
class AttendanceDuration
{
    public static function minutesBetween(string $start, string $end): int
    {
        [$sh, $sm] = array_map('intval', explode(':', $start));
        [$eh, $em] = array_map('intval', explode(':', $end));

        $diff = ($eh * 60 + $em) - ($sh * 60 + $sm);

        return $diff < 0 ? $diff + 1440 : $diff;
    }

    public static function format(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return $hours === 0 ? "{$mins}m" : "{$hours}h {$mins}m";
    }
}
