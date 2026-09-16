import { useEffect, useState, type FormEvent } from "react";
import { Modal } from "../ui/Modal";
import { Input } from "../ui/Input";
import { Select } from "../ui/Select";
import { Button } from "../ui/Button";
import { ImageUploader } from "../ui/ImageUploader";
import type { StaffMember, StaffRole } from "../../types";
import type { StaffInput } from "../../services/staffService";

const roles: StaffRole[] = ["Super Admin", "Admin", "Manager", "Receptionist", "Trainer", "Accountant"];

interface StaffFormModalProps {
  open: boolean;
  onClose: () => void;
  onSave: (input: StaffInput, photoFile?: File) => void;
  initial: StaffMember | null;
  saving?: boolean;
  errors?: Record<string, string[]>;
  uploadPhoto?: (file: File) => Promise<string>;
}

function emptyInput(): StaffInput {
  return { name: "", email: "", phone: "", password: "", role: "Receptionist", status: "Active" };
}

export function StaffFormModal({ open, onClose, onSave, initial, saving, errors, uploadPhoto }: StaffFormModalProps) {
  const [form, setForm] = useState<StaffInput>(initial ? { ...initial, password: "" } : emptyInput());
  const [photo, setPhoto] = useState<string | undefined>(initial?.photo || undefined);
  const [pendingFile, setPendingFile] = useState<File | undefined>(undefined);

  useEffect(() => {
    setForm(initial ? { ...initial, password: "" } : emptyInput());
    setPhoto(initial?.photo || undefined);
    setPendingFile(undefined);
  }, [initial, open]);

  function update<K extends keyof StaffInput>(key: K, value: StaffInput[K]) {
    setForm((f) => ({ ...f, [key]: value }));
  }

  function fieldError(backendField: string): string | undefined {
    return errors?.[backendField]?.[0];
  }

  function handleSubmit(e: FormEvent) {
    e.preventDefault();
    onSave(form, pendingFile);
  }

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={initial ? "Edit Staff Member" : "Add Staff Member"}
      description={initial ? "Update this staff member's account" : "Create a new admin console account"}
      size="lg"
      footer={
        <>
          <Button variant="secondary" onClick={onClose}>Cancel</Button>
          <Button type="submit" form="staff-form" disabled={saving}>
            {saving ? "Saving..." : initial ? "Save Changes" : "Add Staff Member"}
          </Button>
        </>
      }
    >
      <form id="staff-form" onSubmit={handleSubmit} className="space-y-4">
        <ImageUploader value={photo} onChange={setPhoto} uploadFn={uploadPhoto} onFileSelected={setPendingFile} />
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <Input label="Full Name" value={form.name} onChange={(e) => update("name", e.target.value)} error={fieldError("name")} required />
          <Input label="Email" type="email" value={form.email} onChange={(e) => update("email", e.target.value)} error={fieldError("email")} required />
          <Input label="Phone" value={form.phone ?? ""} onChange={(e) => update("phone", e.target.value)} error={fieldError("phone")} />
          <Input
            label={initial ? "New Password (optional)" : "Password"}
            type="password"
            value={form.password ?? ""}
            onChange={(e) => update("password", e.target.value)}
            error={fieldError("password")}
            required={!initial}
          />
          <Select
            label="Role"
            value={form.role}
            onChange={(e) => update("role", e.target.value as StaffRole)}
            options={roles.map((r) => ({ label: r, value: r }))}
            error={fieldError("role")}
          />
          <Select
            label="Status"
            value={form.status ?? "Active"}
            onChange={(e) => update("status", e.target.value as StaffMember["status"])}
            options={["Active", "Inactive"].map((s) => ({ label: s, value: s }))}
            error={fieldError("status")}
          />
        </div>
      </form>
    </Modal>
  );
}
