<?php

namespace App\Support;

/**
 * Canonical notification `type` values. The column is a plain string (see
 * the migration that widened it), so this is the single source of truth
 * for valid types instead of a DB-level enum.
 */
class NotificationType
{
    public const MEMBERSHIP_EXPIRING = 'Membership Expiring';

    public const MEMBERSHIP_EXPIRED = 'Membership Expired';

    public const PAYMENT_RECEIVED = 'Payment Received';

    public const PAYMENT_PENDING = 'Payment Pending';

    public const PAYMENT_FAILED = 'Payment Failed';

    public const NEW_MEMBER = 'New Member';

    public const CLASS_REMINDER = 'Class Reminder';

    public const CLASS_CANCELLATION = 'Class Cancellation';

    public const MAINTENANCE_DUE = 'Maintenance Due';

    public const MAINTENANCE_OVERDUE = 'Maintenance Overdue';

    public const SYSTEM = 'System Notification';

    public const CHECK_IN = 'Check-In';

    public const INVOICE_DUE_REMINDER = 'Invoice Due Reminder';

    public const SUBSCRIPTION_TRIAL_ENDING = 'Subscription Trial Ending';

    public const SUBSCRIPTION_TRIAL_EXPIRED = 'Subscription Trial Expired';

    public const SUBSCRIPTION_RENEWAL_UPCOMING = 'Subscription Renewal Upcoming';

    public const SUBSCRIPTION_PAST_DUE = 'Subscription Past Due';

    public const SUBSCRIPTION_EXPIRED = 'Subscription Expired';

    public const ALL = [
        self::MEMBERSHIP_EXPIRING,
        self::MEMBERSHIP_EXPIRED,
        self::PAYMENT_RECEIVED,
        self::PAYMENT_PENDING,
        self::PAYMENT_FAILED,
        self::NEW_MEMBER,
        self::CLASS_REMINDER,
        self::CLASS_CANCELLATION,
        self::CHECK_IN,
        self::MAINTENANCE_DUE,
        self::MAINTENANCE_OVERDUE,
        self::SYSTEM,
        self::INVOICE_DUE_REMINDER,
        self::SUBSCRIPTION_TRIAL_ENDING,
        self::SUBSCRIPTION_TRIAL_EXPIRED,
        self::SUBSCRIPTION_RENEWAL_UPCOMING,
        self::SUBSCRIPTION_PAST_DUE,
        self::SUBSCRIPTION_EXPIRED,
    ];
}
