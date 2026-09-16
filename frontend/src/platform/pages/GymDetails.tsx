import { useState, type ReactNode } from "react";
import { useParams } from "react-router-dom";
import { Ban, CheckCircle2, Pencil, RotateCcw, Users, UserRound, Dumbbell, Wallet } from "lucide-react";
import { PageHeader } from "../../admin/components/layout/PageHeader";
import { Button } from "../../admin/components/ui/Button";
import { StatCard } from "../../admin/components/ui/StatCard";
import { StatusBadge } from "../../admin/components/ui/Badge";
import { LoadingState } from "../../admin/components/ui/LoadingState";
import { ErrorState } from "../../admin/components/ui/ErrorState";
import { ConfirmDialog } from "../../admin/components/ui/ConfirmDialog";
import { useApiResource } from "../../admin/hooks/useApiResource";
import { useToast } from "../../admin/context/ToastContext";
import { ApiError } from "../../admin/services/apiClient";
import { formatCurrency, formatDate, timeAgo } from "../../admin/utils/format";
import { GymFormModal } from "../components/gyms/GymFormModal";
import { platformGymService, type GymInput } from "../services/platformGymService";

export default function GymDetails() {
  const { id } = useParams<{ id: string }>();
  const { showToast } = useToast();
  const [formOpen, setFormOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [formErrors, setFormErrors] = useState<Record<string, string[]> | undefined>(undefined);
  const [confirmAction, setConfirmAction] = useState<"suspend" | "activate" | "reactivate" | null>(null);

  const { data, loading, error, refetch } = useApiResource(
    (signal) => platformGymService.get(id!, signal).then((result) => ({ data: result })),
    [id],
  );

  async function handleSave(input: GymInput) {
    if (!id) return;
    setSaving(true);
    setFormErrors(undefined);
    try {
      await platformGymService.update(id, input);
      showToast("Gym updated");
      setFormOpen(false);
      refetch();
    } catch (err) {
      if (err instanceof ApiError && err.errors) setFormErrors(err.errors);
      else showToast(err instanceof ApiError ? err.message : "Could not save gym.", "error");
    } finally {
      setSaving(false);
    }
  }

  async function handleConfirmedAction() {
    if (!id || !confirmAction) return;
    try {
      if (confirmAction === "suspend") await platformGymService.suspend(id);
      else if (confirmAction === "activate") await platformGymService.activate(id);
      else await platformGymService.reactivate(id);
      showToast(`Gym ${confirmAction === "suspend" ? "suspended" : confirmAction === "activate" ? "activated" : "reactivated"}`);
      refetch();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not update gym status.", "error");
    }
  }

  if (loading) return <LoadingState rows={8} />;
  if (error || !data) return <ErrorState message={error ?? undefined} onRetry={refetch} />;

  const { gym, recentActivity } = data;

  return (
    <div>
      <PageHeader
        title={gym.name}
        breadcrumb={[{ label: "Gyms", to: "/platform/gyms" }, { label: gym.name }]}
        description={`${gym.slug} · Joined ${formatDate(gym.createdAt)}`}
        action={
          <div className="flex items-center gap-2">
            <StatusBadge status={gym.status} />
            <Button variant="secondary" icon={<Pencil size={15} />} onClick={() => setFormOpen(true)}>Edit</Button>
            {gym.status === "Suspended" ? (
              <Button icon={<RotateCcw size={15} />} onClick={() => setConfirmAction("reactivate")}>Reactivate</Button>
            ) : gym.status === "Active" ? (
              <Button variant="danger" icon={<Ban size={15} />} onClick={() => setConfirmAction("suspend")}>Suspend</Button>
            ) : (
              <Button icon={<CheckCircle2 size={15} />} onClick={() => setConfirmAction("activate")}>Activate</Button>
            )}
          </div>
        }
      />

      <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
        <StatCard label="Members" value={String(gym.usage?.members ?? 0)} icon={<Users size={18} />} />
        <StatCard label="Staff" value={String(gym.usage?.staff ?? 0)} icon={<UserRound size={18} />} />
        <StatCard label="Trainers" value={String(gym.usage?.trainers ?? 0)} icon={<Dumbbell size={18} />} />
        <StatCard label="Revenue" value={formatCurrency(gym.revenue ?? 0)} icon={<Wallet size={18} />} accent="from-emerald-400/20 to-emerald-500/10" />
      </div>

      <div className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div className="admin-card rounded-2xl p-5 shadow-sm">
          <h3 className="mb-4 text-sm font-semibold text-a-text dark:text-a-dark-text">Gym Info</h3>
          <dl className="space-y-3 text-sm">
            <Row label="Email" value={gym.email ?? "—"} />
            <Row label="Phone" value={gym.phone ?? "—"} />
            <Row label="Address" value={gym.address ?? "—"} />
            <Row label="Timezone" value={gym.timezone} />
            <Row label="Currency" value={gym.currency} />
          </dl>

          <h3 className="mb-4 mt-6 text-sm font-semibold text-a-text dark:text-a-dark-text">Owner</h3>
          {gym.owner ? (
            <dl className="space-y-3 text-sm">
              <Row label="Name" value={gym.owner.name} />
              <Row label="Email" value={gym.owner.email} />
              <Row label="Phone" value={gym.owner.phone ?? "—"} />
            </dl>
          ) : (
            <p className="text-xs text-a-muted dark:text-a-dark-muted">No owner account yet.</p>
          )}

          <h3 className="mb-4 mt-6 text-sm font-semibold text-a-text dark:text-a-dark-text">Subscription</h3>
          {gym.subscription ? (
            <dl className="space-y-3 text-sm">
              <Row label="Plan" value={gym.subscription.planName ?? "—"} />
              <Row label="Status" value={<StatusBadge status={gym.subscription.status} />} />
              <Row label="Billing Cycle" value={gym.subscription.billingCycle} />
              <Row label="Next Billing" value={gym.subscription.nextBillingAt ? formatDate(gym.subscription.nextBillingAt) : "—"} />
            </dl>
          ) : (
            <p className="text-xs text-a-muted dark:text-a-dark-muted">No subscription yet.</p>
          )}
        </div>

        <div className="admin-card rounded-2xl p-5 shadow-sm">
          <h3 className="mb-4 text-sm font-semibold text-a-text dark:text-a-dark-text">Recent Activity</h3>
          {recentActivity.length === 0 ? (
            <p className="text-xs text-a-muted dark:text-a-dark-muted">No activity recorded for this gym yet.</p>
          ) : (
            <div className="space-y-3">
              {recentActivity.map((entry) => (
                <div key={entry.id} className="border-b border-a-border/60 pb-3 last:border-0 last:pb-0 dark:border-a-dark-border/60">
                  <p className="text-sm text-a-text dark:text-a-dark-text">{entry.description}</p>
                  <p className="mt-1 text-xs text-a-muted dark:text-a-dark-muted">{entry.actorName} · {timeAgo(entry.createdAt)}</p>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      <GymFormModal open={formOpen} onClose={() => { setFormOpen(false); setFormErrors(undefined); }} onSave={handleSave} initial={gym} saving={saving} errors={formErrors} />

      <ConfirmDialog
        open={!!confirmAction}
        onClose={() => setConfirmAction(null)}
        onConfirm={handleConfirmedAction}
        title={confirmAction === "suspend" ? "Suspend this gym?" : confirmAction === "activate" ? "Activate this gym?" : "Reactivate this gym?"}
        description={`${gym.name} ${confirmAction === "suspend" ? "will lose access to the admin console immediately." : "will regain full access to the admin console."}`}
        confirmLabel={confirmAction === "suspend" ? "Suspend" : confirmAction === "activate" ? "Activate" : "Reactivate"}
        danger={confirmAction === "suspend"}
      />
    </div>
  );
}

function Row({ label, value }: { label: string; value: ReactNode }) {
  return (
    <div className="flex items-center justify-between gap-4">
      <dt className="text-a-muted dark:text-a-dark-muted">{label}</dt>
      <dd className="font-medium text-a-text dark:text-a-dark-text">{value}</dd>
    </div>
  );
}
