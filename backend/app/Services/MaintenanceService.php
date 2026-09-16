<?php

namespace App\Services;

use App\Models\MaintenanceRecord;
use Illuminate\Support\Carbon;

/**
 * Keeps an Equipment row's condition/status/maintenance dates in sync with
 * the lifecycle of its maintenance records: starting work marks it Under
 * Maintenance, completing work refreshes last/next maintenance dates and
 * clears a "Needs Maintenance" condition.
 */
class MaintenanceService
{
    private const FOLLOW_UP_DAYS = 90;

    public function syncEquipment(MaintenanceRecord $record): void
    {
        $equipment = $record->equipment;

        if (! $equipment) {
            return;
        }

        if ($record->status === 'In Progress') {
            $equipment->status = 'Under Maintenance';
        } elseif ($record->status === 'Completed') {
            $equipment->last_maintenance = $record->date;
            $equipment->next_maintenance = Carbon::parse($record->date)->addDays(self::FOLLOW_UP_DAYS)->toDateString();

            if ($equipment->condition === 'Needs Maintenance') {
                $equipment->condition = 'Good';
            }

            if ($equipment->status === 'Under Maintenance') {
                $equipment->status = 'In Use';
            }
        }

        $equipment->save();
    }
}
