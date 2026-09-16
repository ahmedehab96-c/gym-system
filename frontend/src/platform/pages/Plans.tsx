import { useEffect, useState } from "react";
import { Check, Plus, Pencil, Trash2, Power } from "lucide-react";
import { PageHeader } from "../../admin/components/layout/PageHeader";
import { Button } from "../../admin/components/ui/Button";
import { Badge } from "../../admin/components/ui/Badge";
import { ConfirmDialog } from "../../admin/components/ui/ConfirmDialog";
import { LoadingState } from "../../admin/components/ui/LoadingState";
import { ErrorState } from "../../admin/components/ui/ErrorState";
import { EmptyState } from "../../admin/components/ui/EmptyState";
import { useToast } from "../../admin/context/ToastContext";
import { ApiError } from "../../admin/services/apiClient";
import { formatCurrency } from "../../admin/utils/format";
import { PlanFormModal } from "../components/plans/PlanFormModal";
import { platformPlanService, type PlanInput } from "../services/platformPlanService";
import type { PlatformPlan } from "../types";

export default function Plans() {
  const [plans, setPlans] = useState<PlatformPlan[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<PlatformPlan | null>(null);
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<PlatformPlan | null>(null);
  const [saving, setSaving] = useState(false);
  const [formErrors, setFormErrors] = useState<Record<string, string[]> | undefined>(undefined);
  const { showToast } = useToast();

  function load() {
    setLoading(true);
    setError(null);
    platformPlanService
      .list()
      .then(setPlans)
      .catch((err) => setError(err instanceof ApiError ? err.message : "Something went wrong. Please try again."))
      .finally(() => setLoading(false));
  }

  useEffect(load, []);

  function openCreate() {
    setEditing(null);
    setFormErrors(undefined);
    setFormOpen(true);
  }

  function openEdit(plan: PlatformPlan) {
    setEditing(plan);
    setFormErrors(undefined);
    setFormOpen(true);
  }

  async function handleSave(input: PlanInput) {
    setSaving(true);
    setFormErrors(undefined);
    try {
      if (editing) {
        await platformPlanService.update(editing.id, input);
        showToast("Plan updated");
      } else {
        await platformPlanService.create(input);
        showToast("Plan created");
      }
      setFormOpen(false);
      setEditing(null);
      load();
    } catch (err) {
      if (err instanceof ApiError && err.errors) setFormErrors(err.errors);
      else showToast(err instanceof ApiError ? err.message : "Could not save plan.", "error");
    } finally {
      setSaving(false);
    }
  }

  async function toggleStatus(plan: PlatformPlan) {
    try {
      await platformPlanService.update(plan.id, { status: plan.status === "Active" ? "Inactive" : "Active" });
      showToast(`${plan.name} plan ${plan.status === "Active" ? "deactivated" : "activated"}`);
      load();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not update plan.", "error");
    }
  }

  async function handleDelete() {
    if (!deleteTarget) return;
    try {
      await platformPlanService.remove(deleteTarget.id);
      showToast("Plan deleted", "error");
      setDeleteTarget(null);
      load();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not delete plan.", "error");
    }
  }

  return (
    <div>
      <PageHeader
        title="SaaS Plans"
        description="The platform's own pricing catalog — what every gym subscribes to"
        action={<Button icon={<Plus size={16} />} onClick={openCreate}>Create Plan</Button>}
      />

      {loading ? (
        <LoadingState rows={4} />
      ) : error ? (
        <ErrorState message={error} onRetry={load} />
      ) : plans.length === 0 ? (
        <EmptyState title="No plans yet" description="Create your first SaaS pricing plan to start onboarding gyms." />
      ) : (
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
          {plans.map((plan) => (
            <div key={plan.id} className="admin-card flex flex-col overflow-hidden rounded-2xl p-6 shadow-sm transition-transform hover:-translate-y-1">
              <h3 className="text-lg font-bold text-a-text dark:text-a-dark-text">{plan.name}</h3>
              <p className="text-xs text-a-muted dark:text-a-dark-muted">{plan.description}</p>
              <div className="mt-4 flex items-baseline gap-1">
                <span className="text-3xl font-bold text-a-text dark:text-a-dark-text">{formatCurrency(plan.monthlyPrice)}</span>
                <span className="text-xs text-a-muted dark:text-a-dark-muted">/ month</span>
              </div>
              <p className="text-xs text-a-muted dark:text-a-dark-muted">{formatCurrency(plan.yearlyPrice)} / year · {plan.trialDays}-day trial</p>

              <ul className="mt-5 flex-1 space-y-2.5">
                {plan.features.length === 0 ? (
                  <li className="text-xs text-a-muted dark:text-a-dark-muted">No features listed.</li>
                ) : (
                  plan.features.map((f) => (
                    <li key={f} className="flex items-start gap-2 text-sm text-a-text dark:text-a-dark-text">
                      <Check size={15} className="mt-0.5 shrink-0 text-emerald-500" />
                      {f}
                    </li>
                  ))
                )}
              </ul>

              <div className="mt-4 space-y-1 border-t border-a-border pt-4 text-xs text-a-muted dark:border-a-dark-border dark:text-a-dark-muted">
                <p>Members: {plan.limits.max_members ?? "Unlimited"}</p>
                <p>Staff: {plan.limits.max_staff ?? "Unlimited"}</p>
                <p>Trainers: {plan.limits.max_trainers ?? "Unlimited"}</p>
              </div>

              <div className="mt-4 flex items-center justify-between border-t border-a-border pt-4 text-xs dark:border-a-dark-border">
                <span className="text-a-muted dark:text-a-dark-muted">Order #{plan.sortOrder}</span>
                <Badge tone={plan.status === "Active" ? "success" : "neutral"}>{plan.status}</Badge>
              </div>

              <div className="mt-4 flex gap-2">
                <Button variant="secondary" size="sm" className="flex-1" icon={<Pencil size={13} />} onClick={() => openEdit(plan)}>Edit</Button>
                <Button variant="secondary" size="icon" onClick={() => toggleStatus(plan)}><Power size={14} /></Button>
                <Button variant="danger" size="icon" onClick={() => setDeleteTarget(plan)}><Trash2 size={14} /></Button>
              </div>
            </div>
          ))}
        </div>
      )}

      <ConfirmDialog
        open={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        onConfirm={handleDelete}
        title="Delete plan?"
        description={`${deleteTarget?.name} will be removed. Gyms already subscribed to it are unaffected until they change plans.`}
        confirmLabel="Delete"
        danger
      />

      <PlanFormModal
        open={formOpen}
        onClose={() => { setFormOpen(false); setEditing(null); setFormErrors(undefined); }}
        onSave={handleSave}
        initial={editing}
        saving={saving}
        errors={formErrors}
      />
    </div>
  );
}
