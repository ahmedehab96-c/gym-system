import { useEffect, useState } from "react";
import { Search } from "lucide-react";
import { Modal } from "../ui/Modal";
import { Input } from "../ui/Input";
import { Select } from "../ui/Select";
import { Button } from "../ui/Button";
import { Avatar } from "../ui/Avatar";
import { memberService } from "../../services/memberService";
import { useDebouncedValue } from "../../hooks/useDebouncedValue";
import type { Member, Trainer } from "../../types";
import type { PersonalTrainingInput } from "../../services/personalTrainingService";

const goals = ["Fat Loss", "Muscle Gain", "Strength", "Rehab", "General Fitness"];

interface AssignSessionModalProps {
  open: boolean;
  onClose: () => void;
  onSave: (input: PersonalTrainingInput) => void;
  trainers: Trainer[];
  saving?: boolean;
  errors?: Record<string, string[]>;
}

export function AssignSessionModal({ open, onClose, onSave, trainers, saving, errors }: AssignSessionModalProps) {
  const [selectedMember, setSelectedMember] = useState<Member | null>(null);
  const [query, setQuery] = useState("");
  const [results, setResults] = useState<Member[]>([]);
  const [trainerId, setTrainerId] = useState(trainers[0]?.id ?? "");
  const [goal, setGoal] = useState(goals[0]);
  const [sessionsPerWeek, setSessionsPerWeek] = useState(2);
  const debouncedQuery = useDebouncedValue(query, 250);

  useEffect(() => {
    setSelectedMember(null);
    setQuery("");
    setResults([]);
    setTrainerId(trainers[0]?.id ?? "");
    setGoal(goals[0]);
    setSessionsPerWeek(2);
  }, [open, trainers]);

  useEffect(() => {
    if (debouncedQuery.length < 2) {
      setResults([]);
      return;
    }
    const controller = new AbortController();
    memberService.list({ search: debouncedQuery, status: "Active", perPage: 5 }, controller.signal).then(({ data }) => setResults(data)).catch(() => undefined);
    return () => controller.abort();
  }, [debouncedQuery]);

  function fieldError(backendField: string): string | undefined {
    return errors?.[backendField]?.[0];
  }

  function handleSave() {
    if (!selectedMember || !trainerId) return;
    onSave({ memberId: selectedMember.id, trainerId, goal, sessionsPerWeek });
  }

  return (
    <Modal
      open={open}
      onClose={onClose}
      title="Assign Personal Training"
      description="Pair a member with a trainer"
      footer={
        <>
          <Button variant="secondary" onClick={onClose}>Cancel</Button>
          <Button disabled={saving || !selectedMember} onClick={handleSave}>{saving ? "Saving..." : "Assign"}</Button>
        </>
      }
    >
      <div className="space-y-4">
        {selectedMember ? (
          <div className="flex items-center gap-3 rounded-xl bg-a-surface-2 p-3 dark:bg-a-dark-surface-2">
            <Avatar src={selectedMember.avatar} name={selectedMember.name} size="sm" />
            <span className="flex-1 text-sm font-medium text-a-text dark:text-a-dark-text">{selectedMember.name}</span>
            <button className="text-xs font-medium text-a-accent-2 dark:text-a-accent" onClick={() => setSelectedMember(null)}>Change</button>
          </div>
        ) : (
          <div className="relative">
            <Search size={15} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-a-muted dark:text-a-dark-muted" />
            <input
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              placeholder="Search active members by name..."
              className="w-full rounded-xl border border-a-border bg-a-surface py-2.5 pl-9 pr-3 text-sm text-a-text outline-none transition-colors focus:border-a-accent dark:border-a-dark-border dark:bg-a-dark-surface-2 dark:text-a-dark-text"
            />
            {results.length > 0 && (
              <div className="mt-1.5 max-h-48 overflow-y-auto rounded-xl border border-a-border dark:border-a-dark-border">
                {results.map((m) => (
                  <button
                    key={m.id}
                    onClick={() => { setSelectedMember(m); setQuery(""); setResults([]); }}
                    className="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm hover:bg-a-surface-2 dark:hover:bg-a-dark-surface-2"
                  >
                    <Avatar src={m.avatar} name={m.name} size="sm" />
                    {m.name}
                  </button>
                ))}
              </div>
            )}
            {fieldError("member_id") && <span className="mt-1 block text-xs text-rose-500">{fieldError("member_id")}</span>}
          </div>
        )}

        <Select label="Trainer" value={trainerId} onChange={(e) => setTrainerId(e.target.value)} options={trainers.map((t) => ({ label: t.name, value: t.id }))} error={fieldError("trainer_id")} />
        <Select label="Goal" value={goal} onChange={(e) => setGoal(e.target.value)} options={goals.map((g) => ({ label: g, value: g }))} error={fieldError("goal")} />
        <Input label="Sessions per Week" type="number" min={1} max={14} value={sessionsPerWeek} onChange={(e) => setSessionsPerWeek(Number(e.target.value))} error={fieldError("sessions_per_week")} />
      </div>
    </Modal>
  );
}
