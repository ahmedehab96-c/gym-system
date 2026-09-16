<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Reminds staff of an unpaid invoice exactly 3 days before it's due
 * (Phase 24 §6) — the same exact-day-match idiom UpdateSubscriptionStatuses
 * uses, so no extra "already reminded" column/table is needed. Never
 * changes an Invoice's status (Phase 24 explicitly says not to touch
 * existing payment logic) — this command only reads and notifies.
 */
class SendInvoicePaymentReminders extends Command
{
    private const REMINDER_DAYS_BEFORE = 3;

    protected $signature = 'invoices:send-payment-reminders';

    protected $description = 'Remind staff about unpaid invoices due soon';

    public function __construct(private readonly NotificationService $notifications)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $today = Carbon::today();
        $targetDueDate = $today->copy()->addDays(self::REMINDER_DAYS_BEFORE);
        $reminded = 0;

        Invoice::query()
            ->where('status', 'Unpaid')
            ->whereDate('due_date', $targetDueDate->toDateString())
            ->chunkById(200, function ($invoices) use (&$reminded) {
                foreach ($invoices as $invoice) {
                    $this->notifications->invoiceDueReminder($invoice);
                    $reminded++;
                }
            });

        $this->info("Sent {$reminded} invoice payment reminder(s).");

        return self::SUCCESS;
    }
}
