import { useEffect, useState, type FormEvent } from "react";
import { Modal } from "../ui/Modal";
import { Input } from "../ui/Input";
import { Select } from "../ui/Select";
import { Button } from "../ui/Button";
import { ImageUploader } from "../ui/ImageUploader";
import type { EquipmentItem } from "../../types";
import type { EquipmentInput } from "../../services/equipmentService";

const categories: EquipmentItem["category"][] = ["Cardio", "Strength", "Free Weights", "Functional"];
const conditions: EquipmentItem["condition"][] = ["Excellent", "Good", "Needs Maintenance", "Out of Service"];
const statuses: EquipmentItem["status"][] = ["In Use", "Under Maintenance", "Retired"];

interface EquipmentFormModalProps {
  open: boolean;
  onClose: () => void;
  onSave: (input: EquipmentInput, imageFile?: File) => void;
  initial: EquipmentItem | null;
  saving?: boolean;
  errors?: Record<string, string[]>;
  uploadImage?: (file: File) => Promise<string>;
}

function emptyInput(): EquipmentInput {
  return { name: "", category: "Cardio", brand: "", model: "", location: "" };
}

export function EquipmentFormModal({ open, onClose, onSave, initial, saving, errors, uploadImage }: EquipmentFormModalProps) {
  const [form, setForm] = useState<EquipmentInput>(initial ?? emptyInput());
  const [image, setImage] = useState<string | undefined>(initial?.image || undefined);
  const [pendingFile, setPendingFile] = useState<File | undefined>(undefined);

  useEffect(() => {
    setForm(initial ? { ...initial } : emptyInput());
    setImage(initial?.image || undefined);
    setPendingFile(undefined);
  }, [initial, open]);

  function update<K extends keyof EquipmentInput>(key: K, value: EquipmentInput[K]) {
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
      title={initial ? "Edit Equipment" : "Add Equipment"}
      description={initial ? "Update this equipment's details" : "Add a new item to inventory"}
      size="lg"
      footer={
        <>
          <Button variant="secondary" onClick={onClose}>Cancel</Button>
          <Button type="submit" form="equipment-form" disabled={saving}>
            {saving ? "Saving..." : initial ? "Save Changes" : "Add Equipment"}
          </Button>
        </>
      }
    >
      <form id="equipment-form" onSubmit={handleSubmit} className="space-y-4">
        <ImageUploader value={image} onChange={setImage} uploadFn={uploadImage} onFileSelected={setPendingFile} />
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <Input label="Name" value={form.name} onChange={(e) => update("name", e.target.value)} error={fieldError("name")} required />
          <Select label="Category" value={form.category} onChange={(e) => update("category", e.target.value as EquipmentItem["category"])} options={categories.map((c) => ({ label: c, value: c }))} error={fieldError("category")} />
          <Input label="Brand" value={form.brand ?? ""} onChange={(e) => update("brand", e.target.value)} error={fieldError("brand")} />
          <Input label="Model" value={form.model ?? ""} onChange={(e) => update("model", e.target.value)} error={fieldError("model")} />
          <Input label="Location" value={form.location ?? ""} onChange={(e) => update("location", e.target.value)} error={fieldError("location")} />
          <Select label="Condition" value={form.condition ?? "Excellent"} onChange={(e) => update("condition", e.target.value as EquipmentItem["condition"])} options={conditions.map((c) => ({ label: c, value: c }))} error={fieldError("condition")} />
          <Select label="Status" value={form.status ?? "In Use"} onChange={(e) => update("status", e.target.value as EquipmentItem["status"])} options={statuses.map((s) => ({ label: s, value: s }))} error={fieldError("status")} />
          <Input label="Purchase Date" type="date" value={form.purchaseDate ?? ""} onChange={(e) => update("purchaseDate", e.target.value)} error={fieldError("purchase_date")} />
        </div>
      </form>
    </Modal>
  );
}
