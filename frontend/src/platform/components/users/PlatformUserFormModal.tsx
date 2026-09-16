import { useEffect, useState, type FormEvent } from "react";
import { Modal } from "../../../admin/components/ui/Modal";
import { Input } from "../../../admin/components/ui/Input";
import { Button } from "../../../admin/components/ui/Button";
import type { PlatformUserInput } from "../../services/platformUserService";

interface PlatformUserFormModalProps {
  open: boolean;
  onClose: () => void;
  onSave: (input: PlatformUserInput) => void;
  saving?: boolean;
  errors?: Record<string, string[]>;
}

function emptyInput(): PlatformUserInput {
  return { name: "", email: "", phone: "", password: "", status: "Active" };
}

export function PlatformUserFormModal({ open, onClose, onSave, saving, errors }: PlatformUserFormModalProps) {
  const [form, setForm] = useState<PlatformUserInput>(emptyInput());

  useEffect(() => {
    if (open) setForm(emptyInput());
  }, [open]);

  function update<K extends keyof PlatformUserInput>(key: K, value: PlatformUserInput[K]) {
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
      title="Add Platform Admin"
      description="Grant another account full Super Admin access to the platform"
      footer={
        <>
          <Button variant="secondary" onClick={onClose}>Cancel</Button>
          <Button type="submit" form="platform-user-form" disabled={saving}>{saving ? "Saving..." : "Add Platform Admin"}</Button>
        </>
      }
    >
      <form id="platform-user-form" onSubmit={handleSubmit} className="space-y-4">
        <Input label="Full Name" value={form.name} onChange={(e) => update("name", e.target.value)} error={fieldError("name")} required />
        <Input label="Email" type="email" value={form.email} onChange={(e) => update("email", e.target.value)} error={fieldError("email")} required />
        <Input label="Phone" value={form.phone ?? ""} onChange={(e) => update("phone", e.target.value)} error={fieldError("phone")} />
        <Input label="Password" type="password" value={form.password} onChange={(e) => update("password", e.target.value)} error={fieldError("password")} required />
      </form>
    </Modal>
  );
}
