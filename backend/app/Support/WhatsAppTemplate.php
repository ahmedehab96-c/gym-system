<?php

namespace App\Support;

/**
 * Maps an internal NotificationType to the pre-approved WhatsApp Business
 * template name it should send (Phase 24 §4). WhatsApp's official API
 * only allows sending business-initiated messages through templates
 * that have already been submitted to and approved by Meta — these
 * names are conventions this app expects to exist in the connected
 * WhatsApp Business Account; they are NOT created by this app.
 */
class WhatsAppTemplate
{
    private const MAP = [
        NotificationType::MEMBERSHIP_EXPIRING => 'membership_expiry_reminder',
        NotificationType::MEMBERSHIP_EXPIRED => 'membership_expiry_reminder',
        NotificationType::PAYMENT_RECEIVED => 'payment_confirmation',
        NotificationType::CLASS_REMINDER => 'class_reminder',
        NotificationType::CLASS_CANCELLATION => 'class_reminder',
        NotificationType::SUBSCRIPTION_RENEWAL_UPCOMING => 'membership_renewal_reminder',
    ];

    private const DEFAULT = 'gym_notification';

    public static function forType(string $type): string
    {
        return self::MAP[$type] ?? self::DEFAULT;
    }
}
