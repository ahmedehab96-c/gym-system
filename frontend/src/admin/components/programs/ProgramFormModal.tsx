import { useEffect, useState, type FormEvent } from "react";
import { Modal } from "../ui/Modal";
import { Input } from "../ui/Input";
import { Select } from "../ui/Select";
import { Button } from "../ui/Button";
import { ImageUploader } from "../ui/ImageUploader";
import type { TrainingProgram, Trainer } from "../../types";
import type { ProgramInput } from "../../services/trainingProgramService";

const difficulties: TrainingProgram["difficulty"][] = ["Beginner", "Intermediate", "Advanced", "All Levels"];

interface ProgramFormModalProps {
  open: boolean;
  onClose: () => void;
  onSave: (input: ProgramInput, imageFile?: File) => void;
  initial: TrainingProgram | null;
  trainers: Trainer[];
  saving?: boolean;
  errors?: Record<string, string[]>;
  uploadImage?: (file: File) => Promise<string>;
}

function emptyInput(): ProgramInput {
  return { name: "", description: "", duration: "", difficulty: "All Levels", status: "Draft" };
}

export function ProgramFormModal({ open, onClose, onSave, initial, trainers, saving, errors, uploadImage }: ProgramFormModalProps) {
  const [form, setForm] = useState<ProgramInput>(initial ?? emptyInput());
  const [image, setImage] = useState<string | undefined>(initial?.image || undefined);
  const [pendingFile, setPendingFile] = useState<File | undefined>(undefined);

  useEffect(() => {
    setForm(initial ? { ...initial } : emptyInput());
    setImage(initial?.image || undefined);
    setPendingFile(undefined);
  }, [initial, open]);

  function update<K extends keyof ProgramInput>(key: K, value: ProgramInput[K]) {
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
      title={initial ? "Edit Program" : "Add Program"}
      description={initial ? "Update this training program" : "Create a new structured program"}
      size="lg"
      footer={
        <>
          <Button variant="secondary" onClick={onClose}>Cancel</Button>
          <Button type="submit" form="program-form" disabled={saving}>
            {saving ? "Saving..." : initial ? "Save Changes" : "Add Program"}
          </Button>
        </>
      }
    >
      <form id="program-form" onSubmit={handleSubmit} className="space-y-4">
        <ImageUploader value={image} onChange={setImage} uploadFn={uploadImage} onFileSelected={setPendingFile} />
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <Input label="Name" value={form.name} onChange={(e) => update("name", e.target.value)} error={fieldError("name")} className="sm:col-span-2" required />
          <Input label="Description" value={form.description ?? ""} onChange={(e) => update("description", e.target.value)} error={fieldError("description")} className="sm:col-span-2" />
          <Input label="Duration" placeholder="e.g. 8 weeks" value={form.duration ?? ""} onChange={(e) => update("duration", e.target.value)} error={fieldError("duration")} />
          <Select label="Difficulty" value={form.difficulty ?? "All Levels"} onChange={(e) => update("difficulty", e.target.value as TrainingProgram["difficulty"])} options={difficulties.map((d) => ({ label: d, value: d }))} error={fieldError("difficulty")} />
          <Select
            label="Trainer"
            value={form.trainerId ?? ""}
            onChange={(e) => update("trainerId", e.target.value || undefined)}
            options={[{ label: "Unassigned", value: "" }, ...trainers.map((t) => ({ label: t.name, value: t.id }))]}
            error={fieldError("trainer_id")}
          />
          <Select
            label="Status"
            value={form.status ?? "Draft"}
            onChange={(e) => update("status", e.target.value as TrainingProgram["status"])}
            options={["Active", "Draft", "Archived"].map((s) => ({ label: s, value: s }))}
            error={fieldError("status")}
          />
        </div>
      </form>
    </Modal>
  );
}
