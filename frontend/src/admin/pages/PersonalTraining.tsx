import { useEffect, useState } from "react";
import { Plus, Trash2 } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { DataTable, type Column } from "../components/ui/DataTable";
import { Pagination } from "../components/ui/Pagination";
import { SearchBar } from "../components/ui/SearchBar";
import { Avatar } from "../components/ui/Avatar";
import { Badge } from "../components/ui/Badge";
import { Button } from "../components/ui/Button";
import { ConfirmDialog } from "../components/ui/ConfirmDialog";
import { AssignSessionModal } from "../components/personal-training/AssignSessionModal";
import { useApiList } from "../hooks/useApiList";
import { useDebouncedValue } from "../hooks/useDebouncedValue";
import { personalTrainingService, type PersonalTrainingInput, type PersonalTrainingSession } from "../services/personalTrainingService";
import { trainerService } from "../services/trainerService";
import type { Trainer } from "../types";
import { useToast } from "../context/ToastContext";
import { ApiError } from "../services/apiClient";

const PER_PAGE = 10;

export default function PersonalTraining() {
  const [query, setQuery] = useState("");
  const [page, setPage] = useState(1);
  const [trainers, setTrainers] = useState<Trainer[]>([]);
  const [formOpen, setFormOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [formErrors, setFormErrors] = useState<Record<string, string[]> | undefined>(undefined);
  const [deleteTarget, setDeleteTarget] = useState<PersonalTrainingSession | null>(null);
  const { showToast } = useToast();
  const debouncedQuery = useDebouncedValue(query);

  useEffect(() => setPage(1), [debouncedQuery]);

  const { data: sessions, meta, loading, error, refetch } = useApiList(
    (signal) => personalTrainingService.list({ search: debouncedQuery || undefined, page, perPage: PER_PAGE }, signal),
    [debouncedQuery, page],
  );

  useEffect(() => {
    trainerService.list({ perPage: 50 }).then((res) => setTrainers(res.data)).catch(() => undefined);
  }, []);

  async function handleSave(input: PersonalTrainingInput) {
    setSaving(true);
    setFormErrors(undefined);
    try {
      await personalTrainingService.create(input);
      showToast("Personal training assigned");
      setFormOpen(false);
      refetch();
    } catch (err) {
      if (err instanceof ApiError && err.errors) setFormErrors(err.errors);
      else showToast(err instanceof ApiError ? err.message : "Could not assign personal training.", "error");
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete() {
    if (!deleteTarget) return;
    try {
      await personalTrainingService.remove(deleteTarget.id);
      showToast("Assignment removed", "error");
      setDeleteTarget(null);
      refetch();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not remove assignment.", "error");
    }
  }

  const columns: Column<PersonalTrainingSession>[] = [
    { key: "member", header: "Member", render: (s) => (
      <div className="flex items-center gap-3">
        <Avatar src={s.memberAvatar} name={s.memberName} size="sm" />
        <span className="font-medium">{s.memberName}</span>
      </div>
    ) },
    { key: "trainer", header: "Trainer", render: (s) => s.trainerName || "—" },
    { key: "goal", header: "Goal", render: (s) => <Badge tone="accent">{s.goal}</Badge> },
    { key: "freq", header: "Sessions / Week", render: (s) => s.sessionsPerWeek },
    { key: "actions", header: "", className: "text-right", render: (s) => (
      <div className="flex justify-end" onClick={(e) => e.stopPropagation()}>
        <button className="rounded-lg p-1.5 text-rose-500 hover:bg-rose-500/10" onClick={() => setDeleteTarget(s)}><Trash2 size={14} /></button>
      </div>
    ) },
  ];

  return (
    <div>
      <PageHeader
        title="Personal Training"
        description="One-on-one coaching assignments and goals"
        action={<Button icon={<Plus size={16} />} onClick={() => { setFormErrors(undefined); setFormOpen(true); }}>Assign Training</Button>}
      />
      <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div className="admin-card rounded-2xl p-4 shadow-sm lg:col-span-2">
          <div className="mb-3"><SearchBar value={query} onChange={setQuery} placeholder="Search member..." className="sm:max-w-xs" /></div>
          <DataTable columns={columns} rows={sessions} rowKey={(s) => s.id} loading={loading} error={error} onRetry={refetch} />
          {meta && meta.totalPages > 1 && (
            <Pagination page={meta.page} totalPages={meta.totalPages} onChange={setPage} totalItems={meta.total} pageSize={PER_PAGE} />
          )}
        </div>
        <div className="admin-card rounded-2xl p-5 shadow-sm">
          <h3 className="mb-3 text-sm font-semibold text-a-text dark:text-a-dark-text">Trainer Load</h3>
          <div className="space-y-3">
            {trainers.map((t) => (
              <div key={t.id} className="flex items-center gap-3">
                <Avatar src={t.photo} name={t.name} size="sm" />
                <div className="min-w-0 flex-1">
                  <p className="truncate text-sm font-medium text-a-text dark:text-a-dark-text">{t.name}</p>
                  <div className="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-a-surface-2 dark:bg-a-dark-surface-2">
                    <div className="h-full rounded-full bg-a-accent" style={{ width: `${Math.min(100, t.assignedMembers * 4)}%` }} />
                  </div>
                </div>
                <span className="text-xs font-medium text-a-muted dark:text-a-dark-muted">{t.assignedMembers}</span>
              </div>
            ))}
          </div>
        </div>
      </div>

      <AssignSessionModal
        open={formOpen}
        onClose={() => { setFormOpen(false); setFormErrors(undefined); }}
        onSave={handleSave}
        trainers={trainers}
        saving={saving}
        errors={formErrors}
      />

      <ConfirmDialog
        open={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        onConfirm={handleDelete}
        title="Remove assignment?"
        description={`${deleteTarget?.memberName} will no longer be paired with ${deleteTarget?.trainerName}.`}
        confirmLabel="Remove"
        danger
      />
    </div>
  );
}
