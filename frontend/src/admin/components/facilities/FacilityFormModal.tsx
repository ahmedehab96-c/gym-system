import { useEffect, useState, type FormEvent } from "react";
import { Modal } from "../ui/Modal";
import { Input } from "../ui/Input";
import { Select } from "../ui/Select";
import { Button } from "../ui/Button";
import { ImageUploader } from "../ui/ImageUploader";
import type { Facility, FacilityInput } from "../../services/facilityService";

interface FacilityFormModalProps {
  open: boolean;
  onClose: () => void;
  onSave: (input: FacilityInput, imageFile?: File) => void;
  initial: Facility | null;
  saving?: boolean;
  errors?: Record<string, string[]>;
  uploadImage?: (file: File) => Promise<string>;
}

function emptyInput(): FacilityInput {
  return { name: "", description: "", area: "", status: "Open" };
}

export function FacilityFormModal({ open, onClose, onSave, initial, saving, errors, uploadImage }: FacilityFormModalProps) {
  const [form, setForm] = useState<FacilityInput>(initial ? { ...initial, capacity: initial.capacity ?? undefined } : emptyInput());
  const [image, setImage] = useState<string | undefined>(initial?.image || undefined);
  const [pendingFile, setPendingFile] = useState<File | undefined>(undefined);

  useEffect(() => {
    setForm(initial ? { ...initial, capacity: initial.capacity ?? undefined } : emptyInput());
    setImage(initial?.image || undefined);
    setPendingFile(undefined);
  }, [initial, open]);

  function update<K extends keyof FacilityInput>(key: K, value: FacilityInput[K]) {
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
      title={initial ? "Edit Facility" : "Add Facility"}
      description={initial ? "Update this facility's details" : "Add a new physical space"}
      footer={
        <>
          <Button variant="secondary" onClick={onClose}>Cancel</Button>
          <Button type="submit" form="facility-form" disabled={saving}>
            {saving ? "Saving..." : initial ? "Save Changes" : "Add Facility"}
          </Button>
        </>
      }
    >
      <form id="facility-form" onSubmit={handleSubmit} className="space-y-4">
        <ImageUploader
          value={image}
          onChange={setImage}
          uploadFn={uploadImage}
          onFileSelected={setPendingFile}
        />
        <Input label="Name" value={form.name} onChange={(e) => update("name", e.target.value)} error={fieldError("name")} required />
        <Input label="Description" value={form.description ?? ""} onChange={(e) => update("description", e.target.value)} error={fieldError("description")} />
        <div className="grid grid-cols-2 gap-4">
          <Input label="Capacity" type="number" value={form.capacity ?? ""} onChange={(e) => update("capacity", e.target.value ? Number(e.target.value) : undefined)} error={fieldError("capacity")} />
          <Input label="Area" placeholder="e.g. 200 m²" value={form.area ?? ""} onChange={(e) => update("area", e.target.value)} error={fieldError("area")} />
        </div>
        <Select
          label="Status"
          value={form.status ?? "Open"}
          onChange={(e) => update("status", e.target.value as Facility["status"])}
          options={["Open", "Maintenance"].map((s) => ({ label: s, value: s }))}
          error={fieldError("status")}
        />
      </form>
    </Modal>
  );
}
