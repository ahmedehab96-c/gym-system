<?php

namespace App\Notifications\Channels;

use App\Models\AppNotification;
use Illuminate\Notifications\Notification;

/**
 * Persists a notification into the existing `notifications` table (the
 * AppNotification model) instead of Laravel's default notifications
 * schema. Swapping in mail/SMS/push later is just adding channels to a
 * notification's via(); this one stays as-is.
 */
class AppNotificationChannel
{
    public function send(object $notifiable, Notification $notification): AppNotification
    {
        $payload = $notification->toDatabase($notifiable);

        return AppNotification::create([
            'type' => $payload['type'],
            'title' => $payload['title'],
            'message' => $payload['message'],
            'user_id' => $notifiable->getKey(),
        ]);
    }
}
