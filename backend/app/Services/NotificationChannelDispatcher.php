<?php

namespace App\Services;

use App\Jobs\SendEmailNotificationJob;
use App\Jobs\SendPushNotificationJob;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Fans a single business event out to every configured external channel
 * for each recipient — called once from NotificationService::dispatch(),
 * right after the existing in-app (database channel) notification, so
 * no existing call site changes (Phase 24 §1/§6).
 *
 * WhatsApp and push are additionally gated on the PLATFORM having a
 * provider configured at all (an access token present), not just the
 * tenant's preference — the same "no key, no attempt" pattern used by
 * App\Services\AI and App\Services\Payment, so an unconfigured
 * environment (e.g. every automated test) never tries a real network
 * call. Email has no such gate: MAIL_MAILER defaults to "log" locally
 * and "array" in tests, both already side-effect-free.
 */
class NotificationChannelDispatcher
{
    public function __construct(private readonly NotificationPreferenceService $preferences) {}

    public function fanOut(int $tenantId, Collection $recipients, string $type, string $title, string $message): void
    {
        $whatsappConfigured = filled(config('communication.whatsapp.whatsapp_cloud_api.access_token'));
        $pushConfigured = filled(config('communication.push.fcm.access_token'));

        foreach ($recipients as $recipient) {
            /** @var User $recipient */
            if ($recipient->email && $this->preferences->typeEnabled($tenantId, $type, 'email')) {
                SendEmailNotificationJob::dispatch($tenantId, $recipient->id, $recipient->email, $title, $message, $type);
            }

            if ($whatsappConfigured && $recipient->phone && $this->preferences->typeEnabled($tenantId, $type, 'whatsapp')) {
                SendWhatsAppNotificationJob::dispatch($tenantId, $recipient->id, $recipient->phone, $title, $message, $type);
            }

            if ($pushConfigured && $this->preferences->typeEnabled($tenantId, $type, 'push')) {
                $tokens = DeviceToken::query()->where('user_id', $recipient->id)->pluck('token');

                foreach ($tokens as $token) {
                    SendPushNotificationJob::dispatch($tenantId, $recipient->id, $token, $title, $message, $type);
                }
            }
        }
    }
}
