<?php

namespace App\Http\Resources;

use App\Models\NotificationDelivery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NotificationDelivery */
class NotificationDeliveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'channel' => $this->channel,
            // Masked — this is a shared cross-user settings view, not a
            // per-recipient inbox, so full emails/phone numbers/device
            // tokens don't need to be exposed here.
            'recipient' => $this->maskedRecipient(),
            'status' => $this->status,
            'error' => $this->status === 'Failed' ? $this->error : null,
            'sentAt' => $this->sent_at,
            'createdAt' => $this->created_at,
        ];
    }

    private function maskedRecipient(): string
    {
        $value = (string) $this->recipient;

        if ($this->channel === 'push') {
            return mb_substr($value, 0, 8).'…';
        }

        if (str_contains($value, '@')) {
            [$local, $domain] = explode('@', $value, 2);

            return mb_substr($local, 0, 2).'***@'.$domain;
        }

        return mb_substr($value, 0, 4).str_repeat('*', max(mb_strlen($value) - 6, 0)).mb_substr($value, -2);
    }
}
