import { useEffect, useState } from "react";
import { Plus, Users2, Clock, Pencil, Trash2 } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { Button } from "../components/ui/Button";
import { SearchBar } from "../components/ui/SearchBar";
import { FilterDropdown } from "../components/ui/FilterDropdown";
import { Pagination } from "../components/ui/Pagination";
import { StatusBadge, Badge } from "../components/ui/Badge";
import { ConfirmDialog } from "../components/ui/ConfirmDialog";
import { Modal } from "../components/ui/Modal";
import { LoadingState } from "../components/ui/LoadingState";
import { ErrorState } from "../components/ui/ErrorState";
import { EmptyState } from "../components/ui/EmptyState";
import { ProgramFormModal } from "../components/programs/ProgramFormModal";
import { useApiList } from "../hooks/useApiList";
import { useDebouncedValue } from "../hooks/useDebouncedValue";
import { trainingProgramService, type ProgramInput } from "../services/trainingProgramService";
import { trainerService } from "../services/trainerService";
import type { TrainingProgram, Trainer } from "../types";
import { useToast } from "../context/ToastContext";
import { ApiError } from "../services/apiClient";

const PER_PAGE = 12;

export default function TrainingPrograms() {
  const [query, setQuery] = useState("");
  const [difficulty, setDifficulty] = useState("all");
  const [page, setPage] = useState(1);
  const [deleteTarget, setDeleteTarget] = useState<TrainingProgram | null>(null);
  const [viewing, setViewing] = useState<TrainingProgram | null>(null);
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<TrainingProgram | null>(null);
  const [saving, setSaving] = useState(false);
  const [formErrors, setFormErrors] = useState<Record<string, string[]> | undefined>(undefined);
  const [trainers, setTrainers] = useState<Trainer[]>([]);
  const { showToast } = useToast();
  const debouncedQuery = useDebouncedValue(query);

  useEffect(() => setPage(1), [debouncedQuery, difficulty]);

  const { data: programs, meta, loading, error, refetch } = useApiList(
    (signal) =>
      trainingProgramService.list(
        { search: debouncedQuery || undefined, difficulty: difficulty === "all" ? undefined : difficulty, page, perPage: PER_PAGE },
        signal,
      ),
    [debouncedQuery, difficulty, page],
  );

  useEffect(() => {
    trainerService.list({ perPage: 50 }).then((res) => setTrainers(res.data)).catch(() => undefined);
  }, []);

  function openCreate() {
    setEditing(null);
    setFormErrors(undefined);
    setFormOpen(true);
  }

  function openEdit(p: TrainingProgram) {
    setEditing(p);
    setFormErrors(undefined);
    setFormOpen(true);
  }

  async function handleSave(input: ProgramInput, imageFile?: File) {
    setSaving(true);
    setFormErrors(undefined);
    try {
      const saved = editing ? await trainingProgramService.update(editing.id, input) : await trainingProgramService.create(input);
      if (imageFile) await trainingProgramService.uploadImage(saved.id, imageFile);
      showToast(editing ? "Program updated" : "Program added");
      setFormOpen(false);
      setEditing(null);
      refetch();
    } catch (err) {
      if (err instanceof ApiError && err.errors) setFormErrors(err.errors);
      else showToast(err instanceof ApiError ? err.message : "Could not save program.", "error");
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete() {
    if (!deleteTarget) return;
    try {
      await trainingProgramService.remove(deleteTarget.id);
      showToast("Program deleted", "error");
      refetch();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not delete program.", "error");
    }
  }

  return (
    <div>
      <PageHeader
        title="Training Programs"
        description="Structured programs members can enroll in"
        action={<Button icon={<Plus size={16} />} onClick={openCreate}>Add Program</Button>}
      />

      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <SearchBar value={query} onChange={setQuery} placeholder="Search programs..." className="sm:max-w-xs" />
        <FilterDropdown
          label="Difficulty"
          value={difficulty}
          onChange={setDifficulty}
          options={["Beginner", "Intermediate", "Advanced", "All Levels"].map((d) => ({ label: d, value: d }))}
        />
      </div>

      {loading ? (
        <LoadingState rows={4} />
      ) : error ? (
        <ErrorState message={error} onRetry={refetch} />
      ) : programs.length === 0 ? (
        <EmptyState title="No training programs found" />
      ) : (
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
          {programs.map((p) => (
            <div key={p.id} className="admin-card overflow-hidden rounded-2xl shadow-sm">
              <div className="relative h-32 cursor-pointer overflow-hidden" onClick={() => setViewing(p)}>
                <img src={p.image} alt={p.name} className="h-full w-full object-cover" />
                <div className="absolute right-3 top-3"><StatusBadge status={p.status} /></div>
              </div>
              <div className="p-4">
                <h3 className="cursor-pointer font-semibold text-a-text hover:text-a-accent-2 dark:text-a-dark-text dark:hover:text-a-accent" onClick={() => setViewing(p)}>{p.name}</h3>
                <p className="mt-1 line-clamp-2 text-xs text-a-muted dark:text-a-dark-muted">{p.description}</p>
                <div className="mt-3 flex flex-wrap gap-1.5">
                  <Badge tone="accent">{p.difficulty}</Badge>
                  <Badge><Clock size={11} className="mr-1 inline" />{p.duration}</Badge>
                  <Badge><Users2 size={11} className="mr-1 inline" />{p.membersEnrolled}</Badge>
                </div>
                <p className="mt-3 text-xs text-a-muted dark:text-a-dark-muted">Trainer: <span className="text-a-text dark:text-a-dark-text">{p.trainerName}</span></p>
                <div className="mt-3 flex gap-2">
                  <Button variant="secondary" size="sm" className="flex-1" icon={<Pencil size={13} />} onClick={() => openEdit(p)}>Edit</Button>
                  <Button variant="danger" size="icon" onClick={() => setDeleteTarget(p)}><Trash2 size={14} /></Button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {meta && meta.totalPages > 1 && (
        <Pagination page={meta.page} totalPages={meta.totalPages} onChange={setPage} totalItems={meta.total} pageSize={PER_PAGE} />
      )}

      <Modal open={!!viewing} onClose={() => setViewing(null)} title={viewing?.name} size="md">
        {viewing && (
          <div>
            <img src={viewing.image} alt={viewing.name} className="mb-4 h-48 w-full rounded-xl object-cover" />
            <p className="text-sm text-a-muted dark:text-a-dark-muted">{viewing.description}</p>
            <div className="mt-4 grid grid-cols-2 gap-3 text-sm">
              <div><p className="text-xs text-a-muted dark:text-a-dark-muted">Duration</p><p className="font-medium text-a-text dark:text-a-dark-text">{viewing.duration}</p></div>
              <div><p className="text-xs text-a-muted dark:text-a-dark-muted">Difficulty</p><p className="font-medium text-a-text dark:text-a-dark-text">{viewing.difficulty}</p></div>
              <div><p className="text-xs text-a-muted dark:text-a-dark-muted">Trainer</p><p className="font-medium text-a-text dark:text-a-dark-text">{viewing.trainerName}</p></div>
              <div><p className="text-xs text-a-muted dark:text-a-dark-muted">Enrolled</p><p className="font-medium text-a-text dark:text-a-dark-text">{viewing.membersEnrolled} members</p></div>
            </div>
          </div>
        )}
      </Modal>

      <ConfirmDialog
        open={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        onConfirm={handleDelete}
        title="Delete program?"
        description={`${deleteTarget?.name} will be permanently removed.`}
        confirmLabel="Delete"
        danger
      />

      <ProgramFormModal
        open={formOpen}
        onClose={() => { setFormOpen(false); setEditing(null); setFormErrors(undefined); }}
        onSave={handleSave}
        initial={editing}
        trainers={trainers}
        saving={saving}
        errors={formErrors}
        uploadImage={editing ? (file) => trainingProgramService.uploadImage(editing.id, file) : undefined}
      />
    </div>
  );
}
