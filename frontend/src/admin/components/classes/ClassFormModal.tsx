import { useEffect, useState, type FormEvent } from "react";
import { Modal } from "../ui/Modal";
import { Input } from "../ui/Input";
import { Select } from "../ui/Select";
import { Button } from "../ui/Button";
import type { GymClass, Trainer } from "../../types";
import type { ClassInput } from "../../services/classService";

const days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
const statuses: GymClass["status"][] = ["Scheduled", "Full", "Cancelled", "Completed"];

interface ClassFormModalProps {
  open: boolean;
  onClose: () => void;
  onSave: (input: ClassInput) => void;
  initial: GymClass | null;
  trainers: Trainer[];
  saving?: boolean;
  errors?: Record<string, string[]>;
}

function emptyInput(trainers: Trainer[]): ClassInput {
  return { name: "", trainerId: trainers[0]?.id ?? "", day: "Monday", startTime: "09:00", endTime: "10:00", capacity: 15 };
}

export function ClassFormModal({ open, onClose, onSave, initial, trainers, saving, errors }: ClassFormModalProps) {
  const [form, setForm] = useState<ClassInput>(initial ?? emptyInput(trainers));

  useEffect(() => {
    setForm(initial ? { ...initial } : emptyInput(trainers));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [initial, open]);

  function update<K extends keyof ClassInput>(key: K, value: ClassInput[K]) {
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
      title={initial ? "Edit Class" : "Add Class"}
      description={initial ? "Update this class's schedule" : "Schedule a new class"}
      size="lg"
      footer={
        <>
          <Button variant="secondary" onClick={onClose}>Cancel</Button>
          <Button type="submit" form="class-form" disabled={saving}>{saving ? "Saving..." : initial ? "Save Changes" : "Add Class"}</Button>
        </>
      }
    >
      <form id="class-form" onSubmit={handleSubmit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Input label="Name" value={form.name} onChange={(e) => update("name", e.target.value)} error={fieldError("name")} className="sm:col-span-2" required />
        <Input label="Category" value={form.category ?? ""} onChange={(e) => update("category", e.target.value)} error={fieldError("category")} />
        <Select
          label="Trainer"
          value={form.trainerId}
          onChange={(e) => update("trainerId", e.target.value)}
          options={trainers.map((t) => ({ label: t.name, value: t.id }))}
          error={fieldError("trainer_id")}
        />
        <Select label="Day" value={form.day} onChange={(e) => update("day", e.target.value)} options={days.map((d) => ({ label: d, value: d }))} error={fieldError("day")} />
        <Input label="Capacity" type="number" value={form.capacity} onChange={(e) => update("capacity", Number(e.target.value))} error={fieldError("capacity")} required />
        <Input label="Start Time" type="time" value={form.startTime} onChange={(e) => update("startTime", e.target.value)} error={fieldError("start_time")} required />
        <Input label="End Time" type="time" value={form.endTime} onChange={(e) => update("endTime", e.target.value)} error={fieldError("end_time")} required />
        <Select
          label="Status"
          value={form.status ?? "Scheduled"}
          onChange={(e) => update("status", e.target.value as GymClass["status"])}
          options={statuses.map((s) => ({ label: s, value: s }))}
          error={fieldError("status")}
        />
      </form>
    </Modal>
  );
}
