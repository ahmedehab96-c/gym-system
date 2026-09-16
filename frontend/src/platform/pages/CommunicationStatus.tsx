import { Mail, MessageCircle, Smartphone, CheckCircle2, XCircle } from "lucide-react";
import { PageHeader } from "../../admin/components/layout/PageHeader";
import { ChartCard } from "../../admin/components/ui/ChartCard";
import { LoadingState } from "../../admin/components/ui/LoadingState";
import { ErrorState } from "../../admin/components/ui/ErrorState";
import { useApiResource } from "../../admin/hooks/useApiResource";
import { platformCommunicationService, type ProviderStatus } from "../services/platformCommunicationService";

const ROWS: { key: "email" | "whatsapp" | "push"; label: string; icon: typeof Mail; description: string }[] = [
  { key: "email", label: "Email", icon: Mail, description: "Transactional email (welcome, receipts, reminders)" },
  { key: "whatsapp", label: "WhatsApp", icon: MessageCircle, description: "WhatsApp Business Cloud API" },
  { key: "push", label: "Push Notifications", icon: Smartphone, description: "Firebase Cloud Messaging (for future mobile clients)" },
];

/**
 * Read-only visibility into which communication providers are
 * configured (Phase 24 §9) — credentials are environment-only (the
 * same pattern as the SaaS payment gateway in Phase 23), so there is
 * nothing to edit here, only to confirm is set up correctly per
 * environment. Never displays an actual secret value.
 */
export default function CommunicationStatus() {
  const { data: status, loading, error, refetch } = useApiResource((signal) => platformCommunicationService.status(signal).then((s) => ({ data: s })), []);

  return (
    <div>
      <PageHeader
        title="Communication Providers"
        description="Which channels are configured on this environment. Credentials are set via server environment variables, never here."
      />

      <ChartCard title="Provider Status">
        {loading ? (
          <LoadingState rows={3} />
        ) : error || !status ? (
          <ErrorState message={error ?? undefined} onRetry={refetch} />
        ) : (
          <div className="divide-y divide-a-border dark:divide-a-dark-border">
            {ROWS.map((row) => {
              const s: ProviderStatus = status[row.key];
              const Icon = row.icon;
              return (
                <div key={row.key} className="flex items-center justify-between py-4">
                  <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-a-surface-2 text-a-accent-2 dark:bg-a-dark-surface-2 dark:text-a-accent">
                      <Icon size={18} />
                    </div>
                    <div>
                      <p className="text-sm font-medium text-a-text dark:text-a-dark-text">{row.label}</p>
                      <p className="text-xs text-a-muted dark:text-a-dark-muted">
                        {row.description}
                        {(s.provider || s.driver) && ` · ${s.provider ?? s.driver}`}
                      </p>
                    </div>
                  </div>
                  {s.configured ? (
                    <span className="flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                      <CheckCircle2 size={14} /> Configured
                    </span>
                  ) : (
                    <span className="flex items-center gap-1.5 rounded-full bg-a-surface-2 px-3 py-1 text-xs font-medium text-a-muted dark:bg-a-dark-surface-2 dark:text-a-dark-muted">
                      <XCircle size={14} /> Not configured
                    </span>
                  )}
                </div>
              );
            })}
          </div>
        )}
      </ChartCard>
    </div>
  );
}
