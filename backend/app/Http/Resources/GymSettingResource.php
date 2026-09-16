<?php

namespace App\Http\Resources;

use App\Models\GymSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GymSetting */
class GymSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'website' => $this->website,
            'logoUrl' => $this->logo_url,
            'workingDays' => $this->working_days ?? [],
            'openTime' => $this->open_time,
            'closeTime' => $this->close_time,
            'accentColor' => $this->accent_color,
            'currency' => $this->currency,
            'timezone' => $this->timezone,
            'language' => $this->language,
            'notifyEmail' => $this->notify_email,
            'notifyPush' => $this->notify_push,
            'notifySms' => $this->notify_sms,
            'notifyWhatsapp' => $this->notify_whatsapp,
        ];
    }
}
