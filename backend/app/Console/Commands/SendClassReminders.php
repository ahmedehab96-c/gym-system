<?php

namespace App\Console\Commands;

use App\Models\ClassReminderLog;
use App\Models\GymClass;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Reminds staff about classes starting within the next hour (Phase 24
 * §6). Only classes with a concrete `date` are considered — a class
 * template scheduled only by weekday (`day`, no `date`) has no single
 * "next occurrence" to compute without deeper changes to how classes
 * are scheduled, so it's out of scope here; every class this app
 * actually books/displays a day for already carries a `date`.
 *
 * Scheduled hourly (see routes/console.php) — coarser than the 1-hour
 * reminder window it looks for would need `->everyFiveMinutes()` to
 * catch precisely on time, but hourly keeps load low while still
 * reminding on the correct calendar hour; see the ClassReminderLog dedup
 * guard for why running more than once in that window is still safe.
 */
class SendClassReminders extends Command
{
    protected $signature = 'classes:send-reminders';

    protected $description = 'Send a reminder notification for classes starting within the next hour';

    public function __construct(private readonly NotificationService $notifications)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $now = Carbon::now();
        $windowEnd = $now->copy()->addHour();
        $reminded = 0;

        GymClass::query()
            ->where('status', 'Scheduled')
            ->whereDate('date', $now->toDateString())
            ->chunkById(200, function ($classes) use ($now, $windowEnd, &$reminded) {
                foreach ($classes as $class) {
                    if (! $class->date || ! $class->start_time) {
                        continue;
                    }

                    $startsAt = Carbon::parse($class->date->toDateString().' '.$class->start_time);

                    if (! $startsAt->between($now, $windowEnd)) {
                        continue;
                    }

                    $alreadyReminded = ClassReminderLog::query()
                        ->where('gym_class_id', $class->id)
                        ->whereDate('for_date', $class->date->toDateString())
                        ->exists();

                    if ($alreadyReminded) {
                        continue;
                    }

                    $this->notifications->classReminder($class);
                    ClassReminderLog::create(['gym_class_id' => $class->id, 'for_date' => $class->date->toDateString()]);
                    $reminded++;
                }
            });

        $this->info("Sent {$reminded} class reminder(s).");

        return self::SUCCESS;
    }
}
