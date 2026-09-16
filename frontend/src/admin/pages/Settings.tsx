import { useEffect, useState } from "react";
import { Sun, Moon, Save } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { Input } from "../components/ui/Input";
import { Select } from "../components/ui/Select";
import { Button } from "../components/ui/Button";
import { ImageUploader } from "../components/ui/ImageUploader";
import { ChartCard } from "../components/ui/ChartCard";
import { NotificationPreferencesCard } from "../components/settings/NotificationPreferencesCard";
import { DeliveryLogCard } from "../components/settings/DeliveryLogCard";
import { LoadingState } from "../components/ui/LoadingState";
import { ErrorState } from "../components/ui/ErrorState";
import { useTheme } from "../context/ThemeContext";
import { useToast } from "../context/ToastContext";
import { settingsService } from "../services/settingsService";
import { ApiError } from "../services/apiClient";
import { cn } from "../../utils/cn";

const days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
const colors = ["#d4a72f", "#5b8def", "#22c55e", "#e0263c", "#8b5cf6", "#0ea5e9"];

function Toggle({ checked, onChange }: { checked: boolean; onChange: (v: boolean) => void }) {
  return (
    <button
      onClick={() => onChange(!checked)}
      className={cn("relative h-6 w-11 rounded-full transition-colors", checked ? "bg-a-accent" : "bg-a-surface-2 dark:bg-a-dark-surface-2")}
    >
      <span className={cn("absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform", checked ? "translate-x-[22px]" : "translate-x-0.5")} />
    </button>
  );
}

export default function Settings() {
  const { theme, setTheme } = useTheme();
  const { showToast } = useToast();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  const [gym, setGym] = useState({ name: "", phone: "", email: "", address: "", website: "", logo: undefined as string | undefined });
  const [workingDays, setWorkingDays] = useState<string[]>([]);
  const [hours, setHours] = useState({ open: "06:00", close: "23:00" });
  const [accent, setAccent] = useState(colors[0]);
  const [notif, setNotif] = useState({ email: true, push: true, sms: false, whatsapp: false });
  const [general, setGeneral] = useState({ currency: "EGP", timezone: "Africa/Cairo", language: "English" });

  function load() {
    setLoading(true);
    setError(null);
    settingsService
      .get()
      .then((s) => {
        setGym({ name: s.name, phone: s.phone, email: s.email, address: s.address, website: s.website, logo: s.logoUrl || undefined });
        setWorkingDays(s.workingDays);
        setHours({ open: s.openTime || "06:00", close: s.closeTime || "23:00" });
        setAccent(s.accentColor || colors[0]);
        setNotif({ email: s.notifyEmail, push: s.notifyPush, sms: s.notifySms, whatsapp: s.notifyWhatsapp });
        setGeneral({ currency: s.currency, timezone: s.timezone, language: s.language });
      })
      .catch((err) => setError(err instanceof ApiError ? err.message : "Something went wrong. Please try again."))
      .finally(() => setLoading(false));
  }

  useEffect(load, []);

  async function save() {
    setSaving(true);
    try {
      await settingsService.update({
        name: gym.name,
        phone: gym.phone,
        email: gym.email,
        address: gym.address,
        website: gym.website,
        workingDays,
        openTime: hours.open,
        closeTime: hours.close,
        accentColor: accent,
        currency: general.currency,
        timezone: general.timezone,
        language: general.language,
        notifyEmail: notif.email,
        notifyPush: notif.push,
        notifySms: notif.sms,
        notifyWhatsapp: notif.whatsapp,
      });
      showToast("Settings saved successfully");
    } catch (err) {
      showToast(err instanceof ApiError ? err.fieldError ?? err.message : "Could not save settings.", "error");
    } finally {
      setSaving(false);
    }
  }

  if (loading) return <LoadingState rows={8} />;
  if (error) return <ErrorState message={error} onRetry={load} />;

  return (
    <div>
      <PageHeader title="Settings" description="Configure your gym's profile and preferences" action={<Button icon={<Save size={16} />} disabled={saving} onClick={save}>{saving ? "Saving..." : "Save Changes"}</Button>} />

      <div className="space-y-5">
        <ChartCard title="Gym Information" subtitle="Basic details shown across the platform">
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="sm:col-span-2">
              <ImageUploader value={gym.logo} onChange={(v) => setGym((g) => ({ ...g, logo: v }))} uploadFn={(file) => settingsService.uploadLogo(file)} className="h-28" />
            </div>
            <Input label="Gym Name" value={gym.name} onChange={(e) => setGym((g) => ({ ...g, name: e.target.value }))} />
            <Input label="Phone" value={gym.phone} onChange={(e) => setGym((g) => ({ ...g, phone: e.target.value }))} />
            <Input label="Email" value={gym.email} onChange={(e) => setGym((g) => ({ ...g, email: e.target.value }))} />
            <Input label="Website" value={gym.website} onChange={(e) => setGym((g) => ({ ...g, website: e.target.value }))} />
            <Input label="Address" value={gym.address} onChange={(e) => setGym((g) => ({ ...g, address: e.target.value }))} className="sm:col-span-2" />
          </div>
        </ChartCard>

        <ChartCard title="Business Hours" subtitle="Opening hours and working days">
          <div className="grid grid-cols-2 gap-4 sm:max-w-sm">
            <Input label="Opening Time" type="time" value={hours.open} onChange={(e) => setHours((h) => ({ ...h, open: e.target.value }))} />
            <Input label="Closing Time" type="time" value={hours.close} onChange={(e) => setHours((h) => ({ ...h, close: e.target.value }))} />
          </div>
          <div className="mt-4 flex flex-wrap gap-2">
            {days.map((d) => (
              <button
                key={d}
                onClick={() => setWorkingDays((prev) => (prev.includes(d) ? prev.filter((x) => x !== d) : [...prev, d]))}
                className={cn(
                  "rounded-full border px-3.5 py-1.5 text-xs font-medium transition-colors",
                  workingDays.includes(d)
                    ? "border-a-accent bg-a-accent/10 text-a-accent-2 dark:text-a-accent"
                    : "border-a-border text-a-muted dark:border-a-dark-border dark:text-a-dark-muted",
                )}
              >
                {d}
              </button>
            ))}
          </div>
        </ChartCard>

        <ChartCard title="Appearance" subtitle="Theme and brand color">
          <div className="flex flex-wrap items-center gap-6">
            <div>
              <p className="mb-2 text-xs font-medium text-a-muted dark:text-a-dark-muted">Mode</p>
              <div className="flex gap-2">
                <button onClick={() => setTheme("light")} className={cn("flex items-center gap-2 rounded-xl border px-4 py-2 text-sm", theme === "light" ? "border-a-accent bg-a-accent/10 text-a-accent-2" : "border-a-border text-a-muted dark:border-a-dark-border dark:text-a-dark-muted")}>
                  <Sun size={15} /> Light
                </button>
                <button onClick={() => setTheme("dark")} className={cn("flex items-center gap-2 rounded-xl border px-4 py-2 text-sm", theme === "dark" ? "border-a-accent bg-a-accent/10 text-a-accent" : "border-a-border text-a-muted dark:border-a-dark-border dark:text-a-dark-muted")}>
                  <Moon size={15} /> Dark
                </button>
              </div>
            </div>
            <div>
              <p className="mb-2 text-xs font-medium text-a-muted dark:text-a-dark-muted">Primary Color</p>
              <div className="flex gap-2">
                {colors.map((c) => (
                  <button key={c} onClick={() => setAccent(c)} className={cn("h-8 w-8 rounded-full ring-offset-2 transition-all", accent === c && "ring-2 ring-a-accent")} style={{ background: c }} />
                ))}
              </div>
            </div>
          </div>
        </ChartCard>

        <ChartCard title="Notifications" subtitle="Choose how you receive alerts">
          <div className="space-y-4">
            {(["email", "push", "sms", "whatsapp"] as const).map((key) => (
              <div key={key} className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium capitalize text-a-text dark:text-a-dark-text">{key} Notifications</p>
                  <p className="text-xs text-a-muted dark:text-a-dark-muted">Receive alerts via {key}</p>
                </div>
                <Toggle checked={notif[key]} onChange={(v) => setNotif((n) => ({ ...n, [key]: v }))} />
              </div>
            ))}
          </div>
        </ChartCard>

        <NotificationPreferencesCard />
        <DeliveryLogCard />

        <ChartCard title="General" subtitle="Currency, timezone and language">
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <Select label="Currency" value={general.currency} onChange={(e) => setGeneral((g) => ({ ...g, currency: e.target.value }))} options={["EGP", "USD", "EUR", "SAR"].map((c) => ({ label: c, value: c }))} />
            <Select label="Timezone" value={general.timezone} onChange={(e) => setGeneral((g) => ({ ...g, timezone: e.target.value }))} options={["Africa/Cairo", "Asia/Dubai", "Europe/London"].map((t) => ({ label: t, value: t }))} />
            <Select label="Language" value={general.language} onChange={(e) => setGeneral((g) => ({ ...g, language: e.target.value }))} options={["English", "Arabic"].map((l) => ({ label: l, value: l }))} />
          </div>
        </ChartCard>
      </div>
    </div>
  );
}
