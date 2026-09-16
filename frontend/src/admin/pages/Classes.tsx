import { useEffect, useState } from "react";
import { Plus, LayoutGrid, List, Pencil, Trash2 } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { Button } from "../components/ui/Button";
import { SearchBar } from "../components/ui/SearchBar";
import { DataTable, type Column } from "../components/ui/DataTable";
import { StatusBadge } from "../components/ui/Badge";
import { ConfirmDialog } from "../components/ui/ConfirmDialog";
import { WeekCalendar } from "../components/ui/WeekCalendar";
import { Modal } from "../components/ui/Modal";
import { ClassFormModal } from "../components/classes/ClassFormModal";
import { useApiList } from "../hooks/useApiList";
import { useDebouncedValue } from "../hooks/useDebouncedValue";
import { classService, type ClassInput } from "../services/classService";
import { trainerService } from "../services/trainerService";
import type { GymClass, Trainer } from "../types";
import { useToast } from "../context/ToastContext";
import { ApiError } from "../services/apiClient";
import { cn } from "../../utils/cn";

export default function Classes() {
  const [query, setQuery] = useState("");
  const [view, setView] = useState<"list" | "calendar">("list");
  const [deleteTarget, setDeleteTarget] = useState<GymClass | null>(null);
  const [viewing, setViewing] = useState<GymClass | null>(null);
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<GymClass | null>(null);
  const [saving, setSaving] = useState(false);
  const [formErrors, setFormErrors] = useState<Record<string, string[]> | undefined>(undefined);
  const [trainers, setTrainers] = useState<Trainer[]>([]);
  const { showToast } = useToast();
  const debouncedQuery = useDebouncedValue(query);

  const { data: classes, meta, loading, error, refetch } = useApiList(
    (signal) => classService.list({ search: debouncedQuery || undefined, perPage: 100 }, signal),
    [debouncedQuery],
  );

  useEffect(() => {
    trainerService.list({ perPage: 50 }).then((res) => setTrainers(res.data)).catch(() => undefined);
  }, []);

  function openCreate() {
    setEditing(null);
    setFormErrors(undefined);
    setFormOpen(true);
  }

  function openEdit(c: GymClass) {
    setEditing(c);
    setFormErrors(undefined);
    setFormOpen(true);
  }

  async function handleSave(input: ClassInput) {
    setSaving(true);
    setFormErrors(undefined);
    try {
      if (editing) {
        await classService.update(editing.id, input);
        showToast("Class updated");
      } else {
        await classService.create(input);
        showToast("Class added");
      }
      setFormOpen(false);
      setEditing(null);
      refetch();
    } catch (err) {
      if (err instanceof ApiError && err.errors) setFormErrors(err.errors);
      else showToast(err instanceof ApiError ? err.message : "Could not save class.", "error");
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete() {
    if (!deleteTarget) return;
    try {
      await classService.remove(deleteTarget.id);
      showToast("Class deleted", "error");
      refetch();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not delete class.", "error");
    }
  }

  const columns: Column<GymClass>[] = [
    { key: "name", header: "Class", render: (c) => (
      <div className="flex items-center gap-2">
        <span className="h-2.5 w-2.5 rounded-full" style={{ background: c.color }} />
        <span className="font-medium">{c.name}</span>
      </div>
    ) },
    { key: "trainer", header: "Trainer", render: (c) => c.trainerName },
    { key: "day", header: "Day", render: (c) => c.day },
    { key: "time", header: "Time", render: (c) => `${c.startTime} - ${c.endTime}` },
    { key: "capacity", header: "Capacity", render: (c) => `${c.booked}/${c.capacity}` },
    { key: "status", header: "Status", render: (c) => <StatusBadge status={c.status} /> },
    { key: "actions", header: "", className: "text-right", render: (c) => (
      <div className="flex justify-end gap-1" onClick={(e) => e.stopPropagation()}>
        <button className="rounded-lg p-1.5 text-a-muted hover:bg-a-surface-2 dark:hover:bg-a-dark-surface-2" onClick={() => openEdit(c)}><Pencil size={14} /></button>
        <button className="rounded-lg p-1.5 text-rose-500 hover:bg-rose-500/10" onClick={() => setDeleteTarget(c)}><Trash2 size={14} /></button>
      </div>
    ) },
  ];

  return (
    <div>
      <PageHeader
        title="Classes"
        description={meta ? `${meta.total} classes scheduled` : "Loading classes…"}
        action={<Button icon={<Plus size={16} />} onClick={openCreate}>Add Class</Button>}
      />

      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <SearchBar value={query} onChange={setQuery} placeholder="Search classes..." className="sm:max-w-xs" />
        <div className="flex items-center gap-1 rounded-xl bg-a-surface-2 p-1 dark:bg-a-dark-surface-2">
          <button onClick={() => setView("list")} className={cn("flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium", view === "list" ? "bg-a-surface shadow-sm dark:bg-a-dark-surface" : "text-a-muted dark:text-a-dark-muted")}>
            <List size={14} /> List
          </button>
          <button onClick={() => setView("calendar")} className={cn("flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium", view === "calendar" ? "bg-a-surface shadow-sm dark:bg-a-dark-surface" : "text-a-muted dark:text-a-dark-muted")}>
            <LayoutGrid size={14} /> Calendar
          </button>
        </div>
      </div>

      <div className="admin-card rounded-2xl p-4 shadow-sm">
        {view === "list" ? (
          <DataTable columns={columns} rows={classes} rowKey={(c) => c.id} loading={loading} error={error} onRetry={refetch} />
        ) : (
          <WeekCalendar classes={classes} onSelect={setViewing} />
        )}
      </div>

      <Modal open={!!viewing} onClose={() => setViewing(null)} title={viewing?.name} size="sm">
        {viewing && (
          <div className="space-y-3 text-sm">
            <div className="flex justify-between"><span className="text-a-muted dark:text-a-dark-muted">Trainer</span><span className="font-medium text-a-text dark:text-a-dark-text">{viewing.trainerName}</span></div>
            <div className="flex justify-between"><span className="text-a-muted dark:text-a-dark-muted">Day</span><span className="font-medium text-a-text dark:text-a-dark-text">{viewing.day}</span></div>
            <div className="flex justify-between"><span className="text-a-muted dark:text-a-dark-muted">Time</span><span className="font-medium text-a-text dark:text-a-dark-text">{viewing.startTime} - {viewing.endTime}</span></div>
            <div className="flex justify-between"><span className="text-a-muted dark:text-a-dark-muted">Capacity</span><span className="font-medium text-a-text dark:text-a-dark-text">{viewing.booked}/{viewing.capacity}</span></div>
            <div className="flex justify-between"><span className="text-a-muted dark:text-a-dark-muted">Status</span><StatusBadge status={viewing.status} /></div>
          </div>
        )}
      </Modal>

      <ConfirmDialog
        open={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        onConfirm={handleDelete}
        title="Delete class?"
        description={`${deleteTarget?.name} on ${deleteTarget?.day} will be removed.`}
        confirmLabel="Delete"
        danger
      />

      <ClassFormModal
        open={formOpen}
        onClose={() => { setFormOpen(false); setEditing(null); setFormErrors(undefined); }}
        onSave={handleSave}
        initial={editing}
        trainers={trainers}
        saving={saving}
        errors={formErrors}
      />
    </div>
  );
}
