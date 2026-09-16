import { useEffect, useState } from "react";
import { Check, Plus, Pencil, Trash2, Power } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { Button } from "../components/ui/Button";
import { Badge } from "../components/ui/Badge";
import { ConfirmDialog } from "../components/ui/ConfirmDialog";
import { LoadingState } from "../components/ui/LoadingState";
import { ErrorState } from "../components/ui/ErrorState";
import { EmptyState } from "../components/ui/EmptyState";
import { membershipPlanService, type PlanInput } from "../services/membershipPlanService";
import { PlanFormModal } from "../components/membership-plans/PlanFormModal";
import type { MembershipPlan } from "../types";
import { formatCurrency } from "../utils/format";
import { useToast } from "../context/ToastContext";
import { ApiError } from "../services/apiClient";
import { cn } from "../../utils/cn";

export default function MembershipPlans() {
  const [plans, setPlans] = useState<MembershipPlan[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<MembershipPlan | null>(null);
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<MembershipPlan | null>(null);
  const [saving, setSaving] = useState(false);
  const [formErrors, setFormErrors] = useState<Record<string, string[]> | undefined>(undefined);
  const { showToast } = useToast();

  function openCreate() {
    setEditing(null);
    setFormErrors(undefined);
    setFormOpen(true);
  }

  function openEdit(plan: MembershipPlan) {
    setEditing(plan);
    setFormErrors(undefined);
    setFormOpen(true);
  }

  async function handleSave(input: PlanInput) {
    setSaving(true);
    setFormErrors(undefined);
    try {
      if (editing) {
        await membershipPlanService.update(editing.id, input);
        showToast("Plan updated");
      } else {
        await membershipPlanService.create(input);
        showToast("Plan added");
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

  function load() {
    setLoading(true);
    setError(null);
    membershipPlanService
      .list()
      .then(setPlans)
      .catch((err) => setError(err instanceof ApiError ? err.message : "Something went wrong. Please try again."))
      .finally(() => setLoading(false));
  }

  useEffect(load, []);

  async function toggleStatus(plan: MembershipPlan) {
    try {
      await membershipPlanService.update(plan.id, {
        name: plan.name,
        tagline: plan.tagline,
        price: plan.price,
        duration: plan.duration,
        durationDays: plan.durationDays,
        features: plan.features,
        status: plan.status === "Active" ? "Inactive" : "Active",
        color: plan.color,
        popular: plan.popular,
      });
      showToast(`${plan.name} plan ${plan.status === "Active" ? "deactivated" : "activated"}`);
      load();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not update plan.", "error");
    }
  }

  async function handleDelete() {
    if (!deleteTarget) return;
    try {
      await membershipPlanService.remove(deleteTarget.id);
      showToast("Plan deleted", "error");
      load();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not delete plan.", "error");
    }
  }

  return (
    <div>
      <PageHeader
        title="Membership Plans"
        description="Manage pricing tiers and included benefits"
        action={<Button icon={<Plus size={16} />} onClick={openCreate}>Add Plan</Button>}
      />

      {loading ? (
        <LoadingState rows={4} />
      ) : error ? (
        <ErrorState message={error} onRetry={load} />
      ) : plans.length === 0 ? (
        <EmptyState title="No membership plans yet" />
      ) : (
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
          {plans.map((plan) => (
            <div
              key={plan.id}
              className={cn(
                "admin-card relative flex flex-col overflow-hidden rounded-2xl p-6 shadow-sm transition-transform hover:-translate-y-1",
                plan.popular && "ring-2 ring-a-accent",
              )}
            >
              {plan.popular && (
                <span className="absolute right-4 top-4 rounded-full bg-a-accent px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-black">
                  Popular
                </span>
              )}
              <div className="h-1.5 w-10 rounded-full" style={{ background: plan.color }} />
              <h3 className="mt-4 text-lg font-bold text-a-text dark:text-a-dark-text">{plan.name}</h3>
              <p className="text-xs text-a-muted dark:text-a-dark-muted">{plan.tagline}</p>
              <div className="mt-4 flex items-baseline gap-1">
                <span className="text-3xl font-bold text-a-text dark:text-a-dark-text">{formatCurrency(plan.price)}</span>
                <span className="text-xs text-a-muted dark:text-a-dark-muted">/ {plan.duration.toLowerCase()}</span>
              </div>

              <ul className="mt-5 flex-1 space-y-2.5">
                {plan.features.map((f) => (
                  <li key={f} className="flex items-start gap-2 text-sm text-a-text dark:text-a-dark-text">
                    <Check size={15} className="mt-0.5 shrink-0 text-emerald-500" />
                    {f}
                  </li>
                ))}
              </ul>

              <div className="mt-5 flex items-center justify-between border-t border-a-border pt-4 text-xs dark:border-a-dark-border">
                <span className="text-a-muted dark:text-a-dark-muted">{plan.memberCount} members</span>
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
        description={`${deleteTarget?.name} will be removed. Existing members won't be affected.`}
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
