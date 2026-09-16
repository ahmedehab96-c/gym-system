import { useEffect, useState, type FormEvent } from "react";
import { Plus, X } from "lucide-react";
import { Modal } from "../../../admin/components/ui/Modal";
import { Input } from "../../../admin/components/ui/Input";
import { Select } from "../../../admin/components/ui/Select";
import { Button } from "../../../admin/components/ui/Button";
import type { PlanStatus, PlatformPlan } from "../../types";
import type { PlanInput } from "../../services/platformPlanService";

interface PlanFormModalProps {
  open: boolean;
  onClose: () => void;
  onSave: (input: PlanInput) => void;
  initial: PlatformPlan | null;
  saving?: boolean;
  errors?: Record<string, string[]>;
}

const statuses: PlanStatus[] = ["Active", "Inactive"];

function emptyInput(): PlanInput {
  return {
    name: "",
    slug: "",
    description: "",
    monthlyPrice: 0,
    yearlyPrice: 0,
    trialDays: 14,
    features: [],
    limits: { max_members: null, max_staff: null, max_trainers: null, max_classes: null, storage_mb: null, ai_requests: null },
    status: "Active",
    sortOrder: 0,
  };
}

function toInput(plan: PlatformPlan): PlanInput {
  return {
    name: plan.name,
    slug: plan.slug,
    description: plan.description ?? "",
    monthlyPrice: plan.monthlyPrice,
    yearlyPrice: plan.yearlyPrice,
    trialDays: plan.trialDays,
    features: plan.features,
    limits: plan.limits,
    status: plan.status,
    sortOrder: plan.sortOrder,
  };
}

export function PlanFormModal({ open, onClose, onSave, initial, saving, errors }: PlanFormModalProps) {
  const [form, setForm] = useState<PlanInput>(initial ? toInput(initial) : emptyInput());
  const [featureDraft, setFeatureDraft] = useState("");

  useEffect(() => {
    setForm(initial ? toInput(initial) : emptyInput());
    setFeatureDraft("");
  }, [initial, open]);

  function update<K extends keyof PlanInput>(key: K, value: PlanInput[K]) {
    setForm((f) => ({ ...f, [key]: value }));
  }

  function updateLimit(key: keyof NonNullable<PlanInput["limits"]>, value: string) {
    const parsed = value.trim() === "" ? null : Number(value);
    setForm((f) => ({ ...f, limits: { ...f.limits, [key]: parsed } }));
  }

  function addFeature() {
    const value = featureDraft.trim();
    if (!value) return;
    update("features", [...(form.features ?? []), value]);
    setFeatureDraft("");
  }

  function removeFeature(index: number) {
    update("features", (form.features ?? []).filter((_, i) => i !== index));
  }

  function fieldError(backendField: string): string | undefined {
    return errors?.[backendField]?.[0];
  }

  function handleSubmit(e: FormEvent) {
    e.preventDefault();
    onSave(form);
  }

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={initial ? "Edit Plan" : "Create Plan"}
      description={initial ? "Update this SaaS pricing plan" : "Add a new plan to the platform's pricing catalog"}
      size="lg"
      footer={
        <>
          <Button variant="secondary" onClick={onClose}>Cancel</Button>
          <Button type="submit" form="plan-form" disabled={saving}>
            {saving ? "Saving..." : initial ? "Save Changes" : "Create Plan"}
          </Button>
        </>
      }
    >
      <form id="plan-form" onSubmit={handleSubmit} className="space-y-4">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <Input label="Plan Name" value={form.name} onChange={(e) => update("name", e.target.value)} error={fieldError("name")} required />
          <Input label="Slug" value={form.slug} onChange={(e) => update("slug", e.target.value)} error={fieldError("slug")} required />
        </div>
        <Input label="Description" value={form.description ?? ""} onChange={(e) => update("description", e.target.value)} error={fieldError("description")} />

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <Input label="Monthly Price" type="number" min={0} value={form.monthlyPrice} onChange={(e) => update("monthlyPrice", Number(e.target.value))} error={fieldError("monthly_price")} required />
          <Input label="Yearly Price" type="number" min={0} value={form.yearlyPrice} onChange={(e) => update("yearlyPrice", Number(e.target.value))} error={fieldError("yearly_price")} required />
          <Input label="Trial Days" type="number" min={0} value={form.trialDays ?? 0} onChange={(e) => update("trialDays", Number(e.target.value))} error={fieldError("trial_days")} />
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <Select label="Status" value={form.status ?? "Active"} onChange={(e) => update("status", e.target.value as PlanStatus)} options={statuses.map((s) => ({ label: s, value: s }))} error={fieldError("status")} />
          <Input label="Sort Order" type="number" min={0} value={form.sortOrder ?? 0} onChange={(e) => update("sortOrder", Number(e.target.value))} error={fieldError("sort_order")} />
        </div>

        <div>
          <p className="mb-1.5 text-xs font-medium text-a-muted dark:text-a-dark-muted">Usage Limits (blank = unlimited)</p>
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <Input label="Max Members" type="number" min={0} value={form.limits.max_members ?? ""} onChange={(e) => updateLimit("max_members", e.target.value)} />
            <Input label="Max Staff" type="number" min={0} value={form.limits.max_staff ?? ""} onChange={(e) => updateLimit("max_staff", e.target.value)} />
            <Input label="Max Trainers" type="number" min={0} value={form.limits.max_trainers ?? ""} onChange={(e) => updateLimit("max_trainers", e.target.value)} />
            <Input label="Max Classes" type="number" min={0} value={form.limits.max_classes ?? ""} onChange={(e) => updateLimit("max_classes", e.target.value)} />
            <Input label="Storage (MB)" type="number" min={0} value={form.limits.storage_mb ?? ""} onChange={(e) => updateLimit("storage_mb", e.target.value)} />
            <Input label="AI Requests" type="number" min={0} value={form.limits.ai_requests ?? ""} onChange={(e) => updateLimit("ai_requests", e.target.value)} />
          </div>
        </div>

        <div>
          <p className="mb-1.5 text-xs font-medium text-a-muted dark:text-a-dark-muted">Features</p>
          <div className="flex gap-2">
            <Input
              value={featureDraft}
              onChange={(e) => setFeatureDraft(e.target.value)}
              placeholder="e.g. Unlimited class scheduling"
              onKeyDown={(e) => { if (e.key === "Enter") { e.preventDefault(); addFeature(); } }}
              className="flex-1"
            />
            <Button type="button" variant="secondary" icon={<Plus size={15} />} onClick={addFeature}>Add</Button>
          </div>
          {(form.features ?? []).length > 0 && (
            <ul className="mt-2 space-y-1.5">
              {(form.features ?? []).map((feature, i) => (
                <li key={i} className="flex items-center justify-between rounded-lg bg-a-surface-2 px-3 py-1.5 text-sm dark:bg-a-dark-surface-2">
                  <span className="text-a-text dark:text-a-dark-text">{feature}</span>
                  <button type="button" onClick={() => removeFeature(i)} className="text-a-muted hover:text-rose-500">
                    <X size={14} />
                  </button>
                </li>
              ))}
            </ul>
          )}
        </div>
      </form>
    </Modal>
  );
}
