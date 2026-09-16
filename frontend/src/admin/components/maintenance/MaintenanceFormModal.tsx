import { useEffect, useState, type FormEvent } from "react";
import { Modal } from "../ui/Modal";
import { Input } from "../ui/Input";
import { Select } from "../ui/Select";
import { Button } from "../ui/Button";
import type { MaintenanceRecord, EquipmentItem } from "../../types";
import type { MaintenanceInput } from "../../services/maintenanceService";

const statuses: MaintenanceRecord["status"][] = ["Upcoming", "Overdue", "In Progress", "Completed"];

interface MaintenanceFormModalProps {
  open: boolean;
  onClose: () => void;
  onSave: (input: MaintenanceInput) => void;
  initial: MaintenanceRecord | null;
  equipment: EquipmentItem[];
  saving?: boolean;
  errors?: Record<string, string[]>;
}

function emptyInput(equipment: EquipmentItem[]): MaintenanceInput {
  return { equipmentId: equipment[0]?.id ?? "", type: "", technician: "", cost: 0 };
}

export function MaintenanceFormModal({ open, onClose, onSave, initial, equipment, saving, errors }: MaintenanceFormModalProps) {
  const [form, setForm] = useState<MaintenanceInput>(initial ?? emptyInput(equipment));

  useEffect(() => {
    setForm(initial ? { ...initial } : emptyInput(equipment));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [initial, open]);

  function update<K extends keyof MaintenanceInput>(key: K, value: MaintenanceInput[K]) {
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
      title={initial ? "Edit Maintenance Record" : "Schedule Maintenance"}
      description={initial ? "Update this maintenance record" : "Schedule a service for a piece of equipment"}
      footer={
        <>
          <Button variant="secondary" onClick={onClose}>Cancel</Button>
          <Button type="submit" form="maintenance-form" disabled={saving}>{saving ? "Saving..." : initial ? "Save Changes" : "Schedule"}</Button>
        </>
      }
    >
      <form id="maintenance-form" onSubmit={handleSubmit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Select
          label="Equipment"
          value={form.equipmentId}
          onChange={(e) => update("equipmentId", e.target.value)}
          options={equipment.map((e) => ({ label: e.name, value: e.id }))}
          error={fieldError("equipment_id")}
          className="sm:col-span-2"
        />
        <Input label="Type" placeholder="e.g. Belt replacement" value={form.type} onChange={(e) => update("type", e.target.value)} error={fieldError("type")} required />
        <Input label="Technician" value={form.technician ?? ""} onChange={(e) => update("technician", e.target.value)} error={fieldError("technician")} />
        <Input label="Date" type="date" value={form.date ?? ""} onChange={(e) => update("date", e.target.value)} error={fieldError("date")} required />
        <Input label="Cost (EGP)" type="number" value={form.cost ?? 0} onChange={(e) => update("cost", Number(e.target.value))} error={fieldError("cost")} />
        <Select
          label="Status"
          value={form.status ?? "Upcoming"}
          onChange={(e) => update("status", e.target.value as MaintenanceRecord["status"])}
          options={statuses.map((s) => ({ label: s, value: s }))}
          error={fieldError("status")}
        />
        <Input label="Notes" value={form.notes ?? ""} onChange={(e) => update("notes", e.target.value)} error={fieldError("notes")} className="sm:col-span-2" />
      </form>
    </Modal>
  );
}
