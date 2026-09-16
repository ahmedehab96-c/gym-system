<?php

namespace App\Services;

use App\Models\GymSetting;
use App\Models\NotificationTypePreference;
use App\Models\Tenant;

/**
 * Two-tier preference resolution: a per-(type, channel) row in
 * notification_type_preferences, if one exists, always wins; otherwise
 * the tenant's blanket gym_settings.notify_* toggle applies. Most
 * tenants never create any override rows, so this stays cheap — one
 * query per channel per dispatch, not one row per type per tenant.
 */
class NotificationPreferenceService
{
    public function typeEnabled(int $tenantId, string $type, string $channel): bool
    {
        $override = NotificationTypePreference::query()
            ->where('tenant_id', $tenantId)
            ->where('type', $type)
            ->where('channel', $channel)
            ->first();

        if ($override) {
            return $override->enabled;
        }

        return $this->channelEnabledForTenant($tenantId, $channel);
    }

    public function channelEnabledForTenant(int $tenantId, string $channel): bool
    {
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return false;
        }

        $setting = GymSetting::query()->where('tenant_id', $tenant->id)->first();

        if (! $setting) {
            // No settings row yet -> the same defaults GymSetting's own
            // migration/factory ship with (email+push on, sms/whatsapp off).
            return in_array($channel, ['email', 'push'], true);
        }

        return match ($channel) {
            'email' => (bool) $setting->notify_email,
            'push' => (bool) $setting->notify_push,
            'whatsapp' => (bool) $setting->notify_whatsapp,
            default => false,
        };
    }

    /**
     * @return array<int, array{type: string, channel: string, enabled: bool}>
     */
    public function overridesForTenant(int $tenantId): array
    {
        return NotificationTypePreference::query()
            ->where('tenant_id', $tenantId)
            ->get(['type', 'channel', 'enabled'])
            ->map(fn ($row) => ['type' => $row->type, 'channel' => $row->channel, 'enabled' => $row->enabled])
            ->all();
    }

    public function setTypePreference(int $tenantId, string $type, string $channel, bool $enabled): NotificationTypePreference
    {
        return NotificationTypePreference::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'type' => $type, 'channel' => $channel],
            ['enabled' => $enabled],
        );
    }
}
