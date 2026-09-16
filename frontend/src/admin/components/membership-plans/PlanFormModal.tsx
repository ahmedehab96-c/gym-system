import { useEffect, useState, type FormEvent } from "react";
import { Modal } from "../ui/Modal";
import { Input } from "../ui/Input";
import { Select } from "../ui/Select";
import { Button } from "../ui/Button";
import type { MembershipPlan } from "../../types";
import type { PlanInput } from "../../services/membershipPlanService";

interface PlanFormModalProps {
  open: boolean;
  onClose: () => void;
  onSave: (input: PlanInput) => void;
  initial: MembershipPlan | null;
  saving?: boolean;
  errors?: Record<string, string[]>;
}

function emptyInput(): PlanInput {
  return { name: "", tagline: "", price: 0, duration: "Monthly", durationDays: 30, features: [], color: "#d4a72f", status: "Active", popular: false };
}

export function PlanFormModal({ open, onClose, onSave, initial, saving, errors }: PlanFormModalProps) {
  const [form, setForm] = useState<PlanInput>(initial ?? emptyInput());
  const [featuresText, setFeaturesText] = useState((initial?.features ?? []).join("\n"));

  useEffect(() => {
    setForm(initial ? { ...initial } : emptyInput());
    setFeaturesText((initial?.features ?? []).join("\n"));
  }, [initial, open]);

  function update<K extends keyof PlanInput>(key: K, value: PlanInput[K]) {
    setForm((f) => ({ ...f, [key]: value }));
  }

  function fieldError(backendField: string): string | undefined {
    return errors?.[backendField]?.[0];
  }

  function handleSave() {
    onSave({ ...form, features: featuresText.split("\n").map((f) => f.trim()).filter(Boolean) });
  }

  function handleSubmit(e: FormEvent) {
    e.preventDefault();
    handleSave();
  }

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={initial ? "Edit Plan" : "Add Plan"}
      description={initial ? "Update this membership plan" : "Create a new pricing tier"}
      size="lg"
      footer={
        <>
          <Button variant="secondary" onClick={onClose}>Cancel</Button>
          <Button type="submit" form="plan-form" disabled={saving}>{saving ? "Saving..." : initial ? "Save Changes" : "Add Plan"}</Button>
        </>
      }
    >
      <form id="plan-form" onSubmit={handleSubmit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Input label="Name" value={form.name} onChange={(e) => update("name", e.target.value)} error={fieldError("name")} required />
        <Input label="Tagline" value={form.tagline ?? ""} onChange={(e) => update("tagline", e.target.value)} error={fieldError("tagline")} />
        <Input label="Price (EGP)" type="number" value={form.price} onChange={(e) => update("price", Number(e.target.value))} error={fieldError("price")} required />
        <Select
          label="Duration"
          value={form.duration}
          onChange={(e) => {
            const label = e.target.value;
            update("duration", label);
            update("durationDays", label === "Monthly" ? 30 : label === "Quarterly" ? 90 : label === "Yearly" ? 365 : form.durationDays);
          }}
          options={["Monthly", "Quarterly", "Yearly"].map((d) => ({ label: d, value: d }))}
          error={fieldError("duration_label")}
        />
        <Input label="Duration (days)" type="number" value={form.durationDays} onChange={(e) => update("durationDays", Number(e.target.value))} error={fieldError("duration_days")} required />
        <Input label="Color" type="color" value={form.color ?? "#d4a72f"} onChange={(e) => update("color", e.target.value)} error={fieldError("color")} />
        <Select
          label="Status"
          value={form.status ?? "Active"}
          onChange={(e) => update("status", e.target.value as MembershipPlan["status"])}
          options={["Active", "Inactive"].map((s) => ({ label: s, value: s }))}
          error={fieldError("status")}
        />
        <Select
          label="Popular"
          value={form.popular ? "yes" : "no"}
          onChange={(e) => update("popular", e.target.value === "yes")}
          options={[{ label: "No", value: "no" }, { label: "Yes", value: "yes" }]}
        />
        <label className="flex flex-col gap-1.5 sm:col-span-2">
          <span className="text-xs font-medium text-a-muted dark:text-a-dark-muted">Features (one per line)</span>
          <textarea
            value={featuresText}
            onChange={(e) => setFeaturesText(e.target.value)}
            rows={5}
            className="w-full rounded-xl border border-a-border bg-a-surface px-3.5 py-2.5 text-sm text-a-text outline-none transition-colors focus:border-a-accent dark:border-a-dark-border dark:bg-a-dark-surface-2 dark:text-a-dark-text"
          />
          {fieldError("features") && <span className="text-xs text-rose-500">{fieldError("features")}</span>}
        </label>
      </form>
    </Modal>
  );
}
