<?php

namespace App\Services;

use App\Models\NotificationDelivery;
use Illuminate\Support\Carbon;

/**
 * Records every external-channel delivery attempt — the single place
 * App\Jobs\Send*NotificationJob report success/failure to, so the
 * Settings "notification history" / "failed notifications" views (Phase
 * 24 §8) have one consistent source instead of each job writing its own
 * ad-hoc log shape.
 */
class NotificationDeliveryService
{
    public function recordPending(int $tenantId, ?int $userId, string $type, string $channel, string $recipient): NotificationDelivery
    {
        return NotificationDelivery::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => $type,
            'channel' => $channel,
            'recipient' => $recipient,
            'status' => 'Pending',
        ]);
    }

    public function markSent(NotificationDelivery $delivery): NotificationDelivery
    {
        $delivery->update(['status' => 'Sent', 'sent_at' => Carbon::now(), 'error' => null]);

        return $delivery->fresh();
    }

    public function markFailed(NotificationDelivery $delivery, string $error): NotificationDelivery
    {
        // Truncated defensively — a provider's error message should never
        // contain a secret, but this keeps the stored log bounded either way.
        $delivery->update(['status' => 'Failed', 'error' => mb_substr($error, 0, 500)]);

        return $delivery->fresh();
    }
}
