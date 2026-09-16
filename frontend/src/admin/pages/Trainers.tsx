import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { Plus, Star, Users2, CalendarDays } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { Button } from "../components/ui/Button";
import { SearchBar } from "../components/ui/SearchBar";
import { FilterDropdown } from "../components/ui/FilterDropdown";
import { Pagination } from "../components/ui/Pagination";
import { StatusBadge, Badge } from "../components/ui/Badge";
import { LoadingState } from "../components/ui/LoadingState";
import { ErrorState } from "../components/ui/ErrorState";
import { EmptyState } from "../components/ui/EmptyState";
import { TrainerFormModal } from "../components/trainers/TrainerFormModal";
import { useApiList } from "../hooks/useApiList";
import { useDebouncedValue } from "../hooks/useDebouncedValue";
import { trainerService, type TrainerInput } from "../services/trainerService";
import { useToast } from "../context/ToastContext";
import { ApiError } from "../services/apiClient";

const PER_PAGE = 12;

export default function Trainers() {
  const navigate = useNavigate();
  const { showToast } = useToast();
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState("all");
  const [page, setPage] = useState(1);
  const [formOpen, setFormOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [formErrors, setFormErrors] = useState<Record<string, string[]> | undefined>(undefined);
  const debouncedQuery = useDebouncedValue(query);

  useEffect(() => setPage(1), [debouncedQuery, status]);

  const { data: trainers, meta, loading, error, refetch } = useApiList(
    (signal) =>
      trainerService.list({ search: debouncedQuery || undefined, status: status === "all" ? undefined : status, page, perPage: PER_PAGE }, signal),
    [debouncedQuery, status, page],
  );

  async function handleSave(input: TrainerInput, photoFile?: File) {
    setSaving(true);
    setFormErrors(undefined);
    try {
      const created = await trainerService.create(input);
      if (photoFile) await trainerService.uploadPhoto(created.id, photoFile);
      showToast("Trainer added");
      setFormOpen(false);
      refetch();
    } catch (err) {
      if (err instanceof ApiError && err.errors) setFormErrors(err.errors);
      else showToast(err instanceof ApiError ? err.message : "Could not add trainer.", "error");
    } finally {
      setSaving(false);
    }
  }

  return (
    <div>
      <PageHeader
        title="Trainers"
        description={meta ? `${meta.total} trainers on staff` : "Loading trainers…"}
        action={<Button icon={<Plus size={16} />} onClick={() => { setFormErrors(undefined); setFormOpen(true); }}>Add Trainer</Button>}
      />

      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <SearchBar value={query} onChange={setQuery} placeholder="Search trainers..." className="sm:max-w-xs" />
        <FilterDropdown label="Status" value={status} onChange={setStatus} options={["Active", "On Leave", "Inactive"].map((s) => ({ label: s, value: s }))} />
      </div>

      {loading ? (
        <LoadingState rows={6} />
      ) : error ? (
        <ErrorState message={error} onRetry={refetch} />
      ) : trainers.length === 0 ? (
        <EmptyState title="No trainers found" />
      ) : (
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
          {trainers.map((t) => (
            <div
              key={t.id}
              onClick={() => navigate(`/admin/trainers/${t.id}`)}
              className="admin-card group cursor-pointer overflow-hidden rounded-2xl shadow-sm transition-transform hover:-translate-y-1"
            >
              <div className="relative h-36 overflow-hidden">
                <img src={t.photo} alt={t.name} className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
                <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" />
                <div className="absolute right-3 top-3"><StatusBadge status={t.status} /></div>
                <div className="absolute bottom-3 left-4 text-white">
                  <p className="font-semibold">{t.name}</p>
                  <p className="text-xs opacity-90">{t.specialty}</p>
                </div>
              </div>
              <div className="p-4">
                <div className="flex flex-wrap gap-1.5">
                  {t.specialties.map((s) => (
                    <Badge key={s} tone="accent">{s}</Badge>
                  ))}
                </div>
                <div className="mt-4 grid grid-cols-3 gap-2 text-center text-xs">
                  <div>
                    <p className="flex items-center justify-center gap-1 font-semibold text-a-text dark:text-a-dark-text"><Star size={12} className="text-a-accent" /> {t.rating}</p>
                    <p className="text-a-muted dark:text-a-dark-muted">Rating</p>
                  </div>
                  <div>
                    <p className="flex items-center justify-center gap-1 font-semibold text-a-text dark:text-a-dark-text"><Users2 size={12} /> {t.assignedMembers}</p>
                    <p className="text-a-muted dark:text-a-dark-muted">Members</p>
                  </div>
                  <div>
                    <p className="flex items-center justify-center gap-1 font-semibold text-a-text dark:text-a-dark-text"><CalendarDays size={12} /> {t.classesCount}</p>
                    <p className="text-a-muted dark:text-a-dark-muted">Classes</p>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {meta && meta.totalPages > 1 && (
        <Pagination page={meta.page} totalPages={meta.totalPages} onChange={setPage} totalItems={meta.total} pageSize={PER_PAGE} />
      )}

      <TrainerFormModal
        open={formOpen}
        onClose={() => { setFormOpen(false); setFormErrors(undefined); }}
        onSave={handleSave}
        initial={null}
        saving={saving}
        errors={formErrors}
      />
    </div>
  );
}
