import { useEffect, useState } from "react";
import { ChartCard } from "../ui/ChartCard";
import { LoadingState } from "../ui/LoadingState";
import { ErrorState } from "../ui/ErrorState";
import { communicationService, type ChannelDefaults, type CommunicationChannel, type PreferenceOverride } from "../../services/communicationService";
import { ApiError } from "../../services/apiClient";
import type { NotificationType } from "../../types";
import { cn } from "../../../utils/cn";

const GYM_NOTIFICATION_TYPES: NotificationType[] = [
  "Membership Expiring", "Membership Expired", "Payment Received", "Payment Pending", "Payment Failed",
  "New Member", "Class Reminder", "Class Cancellation", "Maintenance Due", "Maintenance Overdue",
  "Invoice Due Reminder",
];

const CHANNELS: { key: CommunicationChannel; label: string }[] = [
  { key: "email", label: "Email" },
  { key: "whatsapp", label: "WhatsApp" },
  { key: "push", label: "Push" },
];

function effective(overrides: PreferenceOverride[], channels: ChannelDefaults, type: string, channel: CommunicationChannel): boolean {
  const override = overrides.find((o) => o.type === type && o.channel === channel);
  return override ? override.enabled : channels[channel];
}

/**
 * Per-(notification type, channel) enable/disable matrix — the tenant-
 * default toggles live in the "Notifications" card above this one
 * (bound to GymSetting.notify_*); this card only stores explicit
 * deviations from those defaults (Phase 24 §5).
 */
export function NotificationPreferencesCard() {
  const [channels, setChannels] = useState<ChannelDefaults | null>(null);
  const [overrides, setOverrides] = useState<PreferenceOverride[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [pending, setPending] = useState<string | null>(null);

  function load() {
    setLoading(true);
    setError(null);
    communicationService
      .getPreferences()
      .then((prefs) => {
        setChannels(prefs.channels);
        setOverrides(prefs.overrides);
      })
      .catch((err) => setError(err instanceof ApiError ? err.message : "Could not load notification preferences."))
      .finally(() => setLoading(false));
  }

  useEffect(load, []);

  async function toggle(type: string, channel: CommunicationChannel) {
    if (!channels) return;
    const key = `${type}:${channel}`;
    const nextValue = !effective(overrides, channels, type, channel);
    setPending(key);
    try {
      const prefs = await communicationService.setPreference(type, channel, nextValue);
      setOverrides(prefs.overrides);
    } catch {
      // silently keep prior state; the checkbox simply won't reflect the change
    } finally {
      setPending(null);
    }
  }

  return (
    <ChartCard title="Notification Type Preferences" subtitle="Fine-tune which channel each event uses, per type">
      {loading ? (
        <LoadingState rows={4} />
      ) : error || !channels ? (
        <ErrorState message={error ?? undefined} onRetry={load} />
      ) : (
        <div className="overflow-x-auto">
          <table className="w-full min-w-[480px] text-sm">
            <thead>
              <tr className="text-left text-xs text-a-muted dark:text-a-dark-muted">
                <th className="pb-2 font-medium">Event</th>
                {CHANNELS.map((c) => (
                  <th key={c.key} className="pb-2 text-center font-medium">{c.label}</th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-a-border dark:divide-a-dark-border">
              {GYM_NOTIFICATION_TYPES.map((type) => (
                <tr key={type}>
                  <td className="py-2.5 text-a-text dark:text-a-dark-text">{type}</td>
                  {CHANNELS.map((c) => {
                    const key = `${type}:${c.key}`;
                    const checked = effective(overrides, channels, type, c.key);
                    return (
                      <td key={c.key} className="py-2.5 text-center">
                        <input
                          type="checkbox"
                          checked={checked}
                          disabled={pending === key}
                          onChange={() => toggle(type, c.key)}
                          className={cn("h-4 w-4 cursor-pointer rounded accent-a-accent", pending === key && "opacity-50")}
                        />
                      </td>
                    );
                  })}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </ChartCard>
  );
}
