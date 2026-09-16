import { useEffect, useState, type FormEvent } from "react";
import { Modal } from "../ui/Modal";
import { Input } from "../ui/Input";
import { Select } from "../ui/Select";
import { Button } from "../ui/Button";
import { ImageUploader } from "../ui/ImageUploader";
import type { Trainer } from "../../types";
import type { TrainerInput } from "../../services/trainerService";

interface TrainerFormModalProps {
  open: boolean;
  onClose: () => void;
  onSave: (input: TrainerInput, photoFile?: File) => void;
  initial: Trainer | null;
  saving?: boolean;
  errors?: Record<string, string[]>;
  uploadPhoto?: (file: File) => Promise<string>;
}

function emptyInput(): TrainerInput {
  return { name: "", specialty: "", email: "", phone: "", experience: "", bio: "", status: "Active" };
}

export function TrainerFormModal({ open, onClose, onSave, initial, saving, errors, uploadPhoto }: TrainerFormModalProps) {
  const [form, setForm] = useState<TrainerInput>(initial ?? emptyInput());
  const [photo, setPhoto] = useState<string | undefined>(initial?.photo || undefined);
  const [pendingFile, setPendingFile] = useState<File | undefined>(undefined);

  useEffect(() => {
    setForm(initial ? { ...initial } : emptyInput());
    setPhoto(initial?.photo || undefined);
    setPendingFile(undefined);
  }, [initial, open]);

  function update<K extends keyof TrainerInput>(key: K, value: TrainerInput[K]) {
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
      title={initial ? "Edit Trainer" : "Add Trainer"}
      description={initial ? "Update this trainer's profile" : "Add a new trainer to your staff"}
      size="lg"
      footer={
        <>
          <Button variant="secondary" onClick={onClose}>Cancel</Button>
          <Button type="submit" form="trainer-form" disabled={saving}>
            {saving ? "Saving..." : initial ? "Save Changes" : "Add Trainer"}
          </Button>
        </>
      }
    >
      <form id="trainer-form" onSubmit={handleSubmit} className="space-y-4">
        <ImageUploader value={photo} onChange={setPhoto} uploadFn={uploadPhoto} onFileSelected={setPendingFile} />
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <Input label="Full Name" value={form.name} onChange={(e) => update("name", e.target.value)} error={fieldError("name")} required />
          <Input label="Primary Specialty" value={form.specialty} onChange={(e) => update("specialty", e.target.value)} error={fieldError("specialty")} required />
          <Input label="Email" type="email" value={form.email} onChange={(e) => update("email", e.target.value)} error={fieldError("email")} required />
          <Input label="Phone" value={form.phone ?? ""} onChange={(e) => update("phone", e.target.value)} error={fieldError("phone")} />
          <Input label="Experience" placeholder="e.g. 5 years" value={form.experience ?? ""} onChange={(e) => update("experience", e.target.value)} error={fieldError("experience")} />
          <Select
            label="Status"
            value={form.status ?? "Active"}
            onChange={(e) => update("status", e.target.value as Trainer["status"])}
            options={["Active", "On Leave", "Inactive"].map((s) => ({ label: s, value: s }))}
            error={fieldError("status")}
          />
          <Input label="Bio" value={form.bio ?? ""} onChange={(e) => update("bio", e.target.value)} error={fieldError("bio")} className="sm:col-span-2" />
        </div>
      </form>
    </Modal>
  );
}
