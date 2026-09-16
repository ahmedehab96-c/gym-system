<?php

namespace App\Notifications;

use App\Notifications\Channels\AppNotificationChannel;
use Illuminate\Notifications\Notification;

/**
 * A single generic notification envelope for every system event
 * (membership/payment/member/class/maintenance). Only the database
 * channel is wired up for now — add 'mail' or a broadcast channel to
 * via() later without touching any of the call sites in NotificationService.
 */
class GymEventNotification extends Notification
{
    public function __construct(
        public readonly string $type,
        public readonly string $title,
        public readonly string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return [AppNotificationChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
        ];
    }
}
