<?php

namespace App\Console\Commands;

use App\Models\TenantSubscription;
use App\Services\NotificationService;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Advances every tenant's subscription lifecycle (Phase 19 §8):
 *   Trial expiration        -> Trial subscriptions past trial_ends_at become Expired.
 *   Past-due detection      -> Active subscriptions past next_billing_at become
 *                               Past Due (no real payment gateway exists yet to
 *                               actually attempt a charge — see SubscriptionService).
 *   Past-due expiration     -> Past Due subscriptions older than the grace
 *                               period become Expired.
 *   Upcoming-renewal/trial-ending notices fire exactly once, on the single
 *   day they're N days out, rather than needing extra "already notified"
 *   state — the same trick UpdateMembershipStatuses uses by only notifying
 *   on an actual status transition.
 */
class UpdateSubscriptionStatuses extends Command
{
    private const PAST_DUE_GRACE_DAYS = 7;

    private const REMINDER_DAYS_BEFORE = 3;

    protected $signature = 'subscriptions:update-statuses';

    protected $description = 'Advance tenant subscription lifecycles and generate related notifications';

    public function __construct(
        private readonly SubscriptionService $subscriptions,
        private readonly NotificationService $notifications,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $today = Carbon::today();
        $transitioned = 0;

        TenantSubscription::query()->with('tenant')->chunkById(200, function ($subscriptions) use ($today, &$transitioned) {
            foreach ($subscriptions as $subscription) {
                if ($this->handleTrial($subscription, $today)) {
                    $transitioned++;

                    continue;
                }

                if ($this->handlePastDueDetection($subscription, $today)) {
                    $transitioned++;

                    continue;
                }

                if ($this->handlePastDueExpiration($subscription, $today)) {
                    $transitioned++;

                    continue;
                }

                $this->sendUpcomingReminders($subscription, $today);
            }
        });

        $this->info("Transitioned {$transitioned} subscription(s).");

        return self::SUCCESS;
    }

    private function handleTrial(TenantSubscription $subscription, Carbon $today): bool
    {
        if ($subscription->status !== 'Trial' || ! $subscription->trial_ends_at) {
            return false;
        }

        if (Carbon::parse($subscription->trial_ends_at)->startOfDay()->lt($today)) {
            $this->subscriptions->expire($subscription);
            $this->notifications->subscriptionTrialExpired($subscription);

            return true;
        }

        if (Carbon::parse($subscription->trial_ends_at)->startOfDay()->equalTo($today->copy()->addDays(self::REMINDER_DAYS_BEFORE))) {
            $this->notifications->subscriptionTrialEnding($subscription);
        }

        return false;
    }

    private function handlePastDueDetection(TenantSubscription $subscription, Carbon $today): bool
    {
        if ($subscription->status !== 'Active' || ! $subscription->next_billing_at) {
            return false;
        }

        if (Carbon::parse($subscription->next_billing_at)->startOfDay()->lt($today)) {
            $this->subscriptions->markPastDue($subscription);
            $this->notifications->subscriptionPastDue($subscription);

            return true;
        }

        return false;
    }

    private function handlePastDueExpiration(TenantSubscription $subscription, Carbon $today): bool
    {
        if ($subscription->status !== 'Past Due' || ! $subscription->next_billing_at) {
            return false;
        }

        $graceDeadline = Carbon::parse($subscription->next_billing_at)->addDays(self::PAST_DUE_GRACE_DAYS)->startOfDay();

        if ($graceDeadline->lt($today)) {
            $this->subscriptions->expire($subscription);
            $this->notifications->subscriptionExpired($subscription);

            return true;
        }

        return false;
    }

    private function sendUpcomingReminders(TenantSubscription $subscription, Carbon $today): void
    {
        if ($subscription->status === 'Active' && $subscription->next_billing_at
            && Carbon::parse($subscription->next_billing_at)->startOfDay()->equalTo($today->copy()->addDays(self::REMINDER_DAYS_BEFORE))) {
            $this->notifications->subscriptionRenewalUpcoming($subscription);
        }
    }
}
