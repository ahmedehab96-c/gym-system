<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\GymSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name', 'phone', 'email', 'address', 'website', 'logo_url', 'working_days',
    'open_time', 'close_time', 'accent_color', 'currency', 'timezone', 'language',
    'notify_email', 'notify_push', 'notify_sms', 'notify_whatsapp',
])]
class GymSetting extends Model
{
    /** @use HasFactory<GymSettingFactory> */
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'working_days' => 'array',
            'notify_email' => 'boolean',
            'notify_push' => 'boolean',
            'notify_sms' => 'boolean',
            'notify_whatsapp' => 'boolean',
        ];
    }
}
