<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\AttendanceRecord;
use App\Models\GymClass;
use App\Models\Invoice;
use App\Models\MaintenanceRecord;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Notifications\GymEventNotification;
use App\Support\NotificationType;
use Illuminate\Support\Facades\Notification;

/**
 * Creates a notification for every active staff user when a business
 * event happens (new member, payment, membership lifecycle, class
 * change, maintenance due/overdue). Dispatch goes through Laravel's
 * notification pipeline via GymEventNotification, so wiring up a real
 * mail/SMS/push channel later doesn't require touching any caller here.
 */
class NotificationService
{
    public function __construct(private readonly NotificationChannelDispatcher $channels) {}

    public function membershipExpiring(Membership $membership): void
    {
        $name = $membership->member?->name ?? 'A member';
        $date = optional($membership->expiry_date)->toFormattedDateString();

        $this->dispatch(
            $membership->tenant_id,
            NotificationType::MEMBERSHIP_EXPIRING,
            'Membership Expiring Soon',
            "{$name}'s membership expires on {$date}."
        );

        $this->notifyMember(
            $membership->member,
            NotificationType::MEMBERSHIP_EXPIRING,
            'Membership Expiring Soon',
            "Your membership expires on {$date}. Renew to keep your access."
        );
    }

    public function membershipExpired(Membership $membership): void
    {
        $name = $membership->member?->name ?? 'A member';

        $this->dispatch(
            $membership->tenant_id,
            NotificationType::MEMBERSHIP_EXPIRED,
            'Membership Expired',
            "{$name}'s membership has expired."
        );

        $this->notifyMember(
            $membership->member,
            NotificationType::MEMBERSHIP_EXPIRED,
            'Membership Expired',
            'Your membership has expired. Renew to restore your access.'
        );
    }

    public function paymentReceived(Payment $payment): void
    {
        $name = $payment->member?->name ?? 'A member';

        $this->dispatch(
            $payment->tenant_id,
            NotificationType::PAYMENT_RECEIVED,
            'Payment Received',
            "Received a payment of {$payment->amount} from {$name}."
        );

        $this->notifyMember(
            $payment->member,
            NotificationType::PAYMENT_RECEIVED,
            'Payment Received',
            "We received your payment of {$payment->amount}. Thank you!"
        );
    }

    public function paymentPending(Payment $payment): void
    {
        $name = $payment->member?->name ?? 'A member';

        $this->dispatch(
            $payment->tenant_id,
            NotificationType::PAYMENT_PENDING,
            'Payment Pending',
            "A payment of {$payment->amount} from {$name} is pending."
        );
    }

    public function paymentFailed(Payment $payment): void
    {
        $name = $payment->member?->name ?? 'A member';

        $this->dispatch(
            $payment->tenant_id,
            NotificationType::PAYMENT_FAILED,
            'Payment Failed',
            "A payment of {$payment->amount} from {$name} has failed."
        );

        $this->notifyMember(
            $payment->member,
            NotificationType::PAYMENT_FAILED,
            'Payment Failed',
            "Your payment of {$payment->amount} could not be processed. Please try again or contact your gym."
        );
    }

    public function newMember(Member $member): void
    {
        $this->dispatch(
            $member->tenant_id,
            NotificationType::NEW_MEMBER,
            'New Member',
            "{$member->name} just joined the gym."
        );

        $this->notifyMember(
            $member,
            NotificationType::NEW_MEMBER,
            'Welcome!',
            "Welcome to the gym, {$member->name}! We're glad to have you."
        );
    }

    /**
     * Member-only — a QR/front-desk check-in is a per-member event, not a
     * gym-wide one, so unlike most other events here this never fans out
     * to staff via dispatch() (Phase 28 §8: "member check-in
     * confirmation").
     */
    public function checkedIn(AttendanceRecord $record): void
    {
        $this->notifyMember(
            $record->member,
            NotificationType::CHECK_IN,
            'Checked In',
            "You checked in at {$record->check_in}. Have a great workout!"
        );
    }

    public function classReminder(GymClass $class): void
    {
        $this->dispatch(
            $class->tenant_id,
            NotificationType::CLASS_REMINDER,
            'Class Reminder',
            "{$class->name} is scheduled for {$class->day} at {$class->start_time}."
        );

        foreach ($class->members as $member) {
            $this->notifyMember(
                $member,
                NotificationType::CLASS_REMINDER,
                'Class Reminder',
                "{$class->name} starts at {$class->start_time} today. See you there!"
            );
        }
    }

    public function classCancellation(GymClass $class): void
    {
        $this->dispatch(
            $class->tenant_id,
            NotificationType::CLASS_CANCELLATION,
            'Class Cancelled',
            "{$class->name} on {$class->day} has been cancelled."
        );
    }

    public function maintenanceDue(MaintenanceRecord $record): void
    {
        $equipment = $record->equipment?->name ?? 'Equipment';
        $date = optional($record->date)->toFormattedDateString();

        $this->dispatch(
            $record->tenant_id,
            NotificationType::MAINTENANCE_DUE,
            'Maintenance Due',
            "{$equipment} maintenance ({$record->type}) is due on {$date}."
        );
    }

    public function maintenanceOverdue(MaintenanceRecord $record): void
    {
        $equipment = $record->equipment?->name ?? 'Equipment';
        $date = optional($record->date)->toFormattedDateString();

        $this->dispatch(
            $record->tenant_id,
            NotificationType::MAINTENANCE_OVERDUE,
            'Maintenance Overdue',
            "{$equipment} maintenance ({$record->type}) was due on {$date} and is now overdue."
        );
    }

    public function subscriptionTrialEnding(TenantSubscription $subscription): void
    {
        $date = optional($subscription->trial_ends_at)->toFormattedDateString();

        $this->dispatch(
            $subscription->tenant_id,
            NotificationType::SUBSCRIPTION_TRIAL_ENDING,
            'Trial Ending Soon',
            "Your trial ends on {$date}. Choose a plan to keep using the platform without interruption."
        );
    }

    public function subscriptionTrialExpired(TenantSubscription $subscription): void
    {
        $this->dispatch(
            $subscription->tenant_id,
            NotificationType::SUBSCRIPTION_TRIAL_EXPIRED,
            'Trial Expired',
            'Your trial has ended. Choose a plan to continue using the platform.'
        );
    }

    public function subscriptionRenewalUpcoming(TenantSubscription $subscription): void
    {
        $date = optional($subscription->next_billing_at)->toFormattedDateString();

        $this->dispatch(
            $subscription->tenant_id,
            NotificationType::SUBSCRIPTION_RENEWAL_UPCOMING,
            'Upcoming Renewal',
            "Your subscription renews on {$date}."
        );
    }

    public function subscriptionPastDue(TenantSubscription $subscription): void
    {
        $this->dispatch(
            $subscription->tenant_id,
            NotificationType::SUBSCRIPTION_PAST_DUE,
            'Subscription Past Due',
            'Your subscription renewal is past due. Please update your billing to avoid service interruption.'
        );
    }

    public function invoiceDueReminder(Invoice $invoice): void
    {
        $name = $invoice->member?->name ?? 'A member';
        $date = optional($invoice->due_date)->toFormattedDateString();

        $this->dispatch(
            $invoice->tenant_id,
            NotificationType::INVOICE_DUE_REMINDER,
            'Invoice Due Soon',
            "{$name}'s invoice of {$invoice->total} is due on {$date}."
        );
    }

    public function subscriptionExpired(TenantSubscription $subscription): void
    {
        $this->dispatch(
            $subscription->tenant_id,
            NotificationType::SUBSCRIPTION_EXPIRED,
            'Subscription Expired',
            'Your subscription has expired. Renew to restore full access.'
        );
    }

    /**
     * Recipients are resolved by an explicit tenant_id taken from the
     * business record that triggered the notification — never from the
     * ambient CurrentTenant context. This method is called from both real
     * HTTP requests (where CurrentTenant happens to be resolved already)
     * and console-run scheduled commands (where it never is), so relying
     * on ambient scoping here would leak every tenant's notifications to
     * every other tenant's staff whenever a background job fires one.
     */
    private function dispatch(int $tenantId, string $type, string $title, string $message): void
    {
        $recipients = User::query()->where('tenant_id', $tenantId)->where('status', 'Active')->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new GymEventNotification($type, $title, $message));

        // Fans out to email/WhatsApp/push per tenant+recipient preference
        // (Phase 24) — additive only; the in-app notification above is
        // unchanged from every prior phase.
        $this->channels->fanOut($tenantId, $recipients, $type, $title, $message);
    }

    /**
     * The member-facing counterpart to dispatch() (Phase 25 — the
     * Flutter Member Mobile App) — writes directly into the same
     * `notifications` table via member_id instead of user_id, so the
     * mobile app's notification inbox has real data. Deliberately does
     * NOT go through the email/WhatsApp/push channel dispatcher above:
     * that fan-out is staff-preference-driven (App\Models\GymSetting),
     * which has no member-facing equivalent yet — in-app only for now.
     */
    private function notifyMember(?Member $member, string $type, string $title, string $message): void
    {
        if (! $member) {
            return;
        }

        AppNotification::create([
            // Set explicitly (not left to BelongsToTenant's ambient-CurrentTenant
            // auto-fill) so this is correct whether called from an HTTP
            // request or a console/scheduled command — see class docblock.
            'tenant_id' => $member->tenant_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'member_id' => $member->id,
        ]);
    }
}
