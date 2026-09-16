import { useEffect, useState, type FormEvent } from "react";
import { Modal } from "../../../admin/components/ui/Modal";
import { Input } from "../../../admin/components/ui/Input";
import { Select } from "../../../admin/components/ui/Select";
import { Button } from "../../../admin/components/ui/Button";
import type { Gym, GymStatus } from "../../types";
import type { GymInput } from "../../services/platformGymService";

interface GymFormModalProps {
  open: boolean;
  onClose: () => void;
  onSave: (input: GymInput) => void;
  initial: Gym | null;
  saving?: boolean;
  errors?: Record<string, string[]>;
}

const statuses: GymStatus[] = ["Trial", "Active", "Suspended", "Inactive"];

function emptyInput(): GymInput {
  return { name: "", slug: "", email: "", phone: "", address: "", timezone: "UTC", currency: "USD", status: "Trial" };
}

function toInput(gym: Gym): GymInput {
  return {
    name: gym.name,
    slug: gym.slug,
    email: gym.email ?? "",
    phone: gym.phone ?? "",
    address: gym.address ?? "",
    timezone: gym.timezone,
    currency: gym.currency,
    status: gym.status,
  };
}

function slugify(value: string): string {
  return value
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

export function GymFormModal({ open, onClose, onSave, initial, saving, errors }: GymFormModalProps) {
  const [form, setForm] = useState<GymInput>(initial ? toInput(initial) : emptyInput());
  const [slugTouched, setSlugTouched] = useState(Boolean(initial));

  useEffect(() => {
    setForm(initial ? toInput(initial) : emptyInput());
    setSlugTouched(Boolean(initial));
  }, [initial, open]);

  function update<K extends keyof GymInput>(key: K, value: GymInput[K]) {
    setForm((f) => ({ ...f, [key]: value }));
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
      title={initial ? "Edit Gym" : "Add Gym"}
      description={initial ? "Update this gym's platform details" : "Onboard a new gym onto the platform"}
      size="lg"
      footer={
        <>
          <Button variant="secondary" onClick={onClose}>Cancel</Button>
          <Button type="submit" form="gym-form" disabled={saving}>
            {saving ? "Saving..." : initial ? "Save Changes" : "Add Gym"}
          </Button>
        </>
      }
    >
      <form id="gym-form" onSubmit={handleSubmit} className="space-y-4">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <Input
            label="Gym Name"
            value={form.name}
            onChange={(e) => {
              const name = e.target.value;
              update("name", name);
              if (!slugTouched) update("slug", slugify(name));
            }}
            error={fieldError("name")}
            required
          />
          <Input
            label="Slug"
            value={form.slug}
            onChange={(e) => {
              setSlugTouched(true);
              update("slug", slugify(e.target.value));
            }}
            error={fieldError("slug")}
            required
          />
          <Input label="Email" type="email" value={form.email ?? ""} onChange={(e) => update("email", e.target.value)} error={fieldError("email")} />
          <Input label="Phone" value={form.phone ?? ""} onChange={(e) => update("phone", e.target.value)} error={fieldError("phone")} />
          <div className="sm:col-span-2">
            <Input label="Address" value={form.address ?? ""} onChange={(e) => update("address", e.target.value)} error={fieldError("address")} />
          </div>
          <Input label="Timezone" value={form.timezone ?? ""} onChange={(e) => update("timezone", e.target.value)} error={fieldError("timezone")} />
          <Input label="Currency" value={form.currency ?? ""} onChange={(e) => update("currency", e.target.value)} error={fieldError("currency")} />
          <Select
            label="Status"
            value={form.status ?? "Trial"}
            onChange={(e) => update("status", e.target.value as GymStatus)}
            options={statuses.map((s) => ({ label: s, value: s }))}
            error={fieldError("status")}
          />
        </div>

        {!initial && (
          <div className="rounded-xl border border-dashed border-a-border p-4 dark:border-a-dark-border">
            <p className="mb-3 text-xs font-semibold uppercase tracking-wide text-a-muted dark:text-a-dark-muted">
              Owner account (optional)
            </p>
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <Input label="Owner Name" value={form.ownerName ?? ""} onChange={(e) => update("ownerName", e.target.value)} error={fieldError("owner_name")} />
              <Input label="Owner Email" type="email" value={form.ownerEmail ?? ""} onChange={(e) => update("ownerEmail", e.target.value)} error={fieldError("owner_email")} />
              <div className="sm:col-span-2">
                <Input
                  label="Owner Password"
                  type="password"
                  value={form.ownerPassword ?? ""}
                  onChange={(e) => update("ownerPassword", e.target.value)}
                  error={fieldError("owner_password")}
                />
              </div>
            </div>
          </div>
        )}
      </form>
    </Modal>
  );
}
