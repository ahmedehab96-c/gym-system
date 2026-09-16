<?php

namespace App\Services;

use App\Models\GymClass;

/**
 * Detects trainer double-booking: two non-cancelled classes for the same
 * trainer, on the same calendar date (or the same recurring day when
 * neither has a specific date), whose time ranges overlap.
 */
class ClassScheduleService
{
    public function findConflict(
        int $trainerId,
        ?string $date,
        string $day,
        string $startTime,
        string $endTime,
        ?int $excludeClassId = null
    ): ?GymClass {
        $query = GymClass::query()
            ->where('trainer_id', $trainerId)
            ->where('status', '!=', 'Cancelled')
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime);

        if ($date) {
            $query->whereDate('date', $date);
        } else {
            $query->whereNull('date')->where('day', $day);
        }

        if ($excludeClassId) {
            $query->where('id', '!=', $excludeClassId);
        }

        return $query->first();
    }
}
