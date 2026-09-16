import { useEffect, useState } from "react";
import { Plus, MapPin, Pencil, Trash2 } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { Button } from "../components/ui/Button";
import { SearchBar } from "../components/ui/SearchBar";
import { FilterDropdown } from "../components/ui/FilterDropdown";
import { Pagination } from "../components/ui/Pagination";
import { StatusBadge } from "../components/ui/Badge";
import { ConfirmDialog } from "../components/ui/ConfirmDialog";
import { LoadingState } from "../components/ui/LoadingState";
import { ErrorState } from "../components/ui/ErrorState";
import { EmptyState } from "../components/ui/EmptyState";
import { EquipmentFormModal } from "../components/equipment/EquipmentFormModal";
import { useApiList } from "../hooks/useApiList";
import { useDebouncedValue } from "../hooks/useDebouncedValue";
import { equipmentService, type EquipmentInput } from "../services/equipmentService";
import type { EquipmentItem } from "../types";
import { formatDate } from "../utils/format";
import { useToast } from "../context/ToastContext";
import { ApiError } from "../services/apiClient";

const PER_PAGE = 12;

export default function Equipment() {
  const [query, setQuery] = useState("");
  const [category, setCategory] = useState("all");
  const [page, setPage] = useState(1);
  const [deleteTarget, setDeleteTarget] = useState<EquipmentItem | null>(null);
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<EquipmentItem | null>(null);
  const [saving, setSaving] = useState(false);
  const [formErrors, setFormErrors] = useState<Record<string, string[]> | undefined>(undefined);
  const { showToast } = useToast();
  const debouncedQuery = useDebouncedValue(query);

  useEffect(() => setPage(1), [debouncedQuery, category]);

  const { data: equipment, meta, loading, error, refetch } = useApiList(
    (signal) =>
      equipmentService.list({ search: debouncedQuery || undefined, category: category === "all" ? undefined : category, page, perPage: PER_PAGE }, signal),
    [debouncedQuery, category, page],
  );

  function openCreate() {
    setEditing(null);
    setFormErrors(undefined);
    setFormOpen(true);
  }

  function openEdit(e: EquipmentItem) {
    setEditing(e);
    setFormErrors(undefined);
    setFormOpen(true);
  }

  async function handleSave(input: EquipmentInput, imageFile?: File) {
    setSaving(true);
    setFormErrors(undefined);
    try {
      const saved = editing ? await equipmentService.update(editing.id, input) : await equipmentService.create(input);
      if (imageFile) await equipmentService.uploadImage(saved.id, imageFile);
      showToast(editing ? "Equipment updated" : "Equipment added");
      setFormOpen(false);
      setEditing(null);
      refetch();
    } catch (err) {
      if (err instanceof ApiError && err.errors) setFormErrors(err.errors);
      else showToast(err instanceof ApiError ? err.message : "Could not save equipment.", "error");
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete() {
    if (!deleteTarget) return;
    try {
      await equipmentService.remove(deleteTarget.id);
      showToast("Equipment removed", "error");
      setDeleteTarget(null);
      refetch();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not delete equipment.", "error");
    }
  }

  return (
    <div>
      <PageHeader
        title="Equipment"
        description={meta ? `${meta.total} equipment items tracked` : "Loading equipment…"}
        action={<Button icon={<Plus size={16} />} onClick={openCreate}>Add Equipment</Button>}
      />

      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <SearchBar value={query} onChange={setQuery} placeholder="Search equipment..." className="sm:max-w-xs" />
        <FilterDropdown
          label="Categories"
          value={category}
          onChange={setCategory}
          options={["Cardio", "Strength", "Free Weights", "Functional"].map((c) => ({ label: c, value: c }))}
        />
      </div>

      {loading ? (
        <LoadingState rows={4} />
      ) : error ? (
        <ErrorState message={error} onRetry={refetch} />
      ) : equipment.length === 0 ? (
        <EmptyState title="No equipment found" />
      ) : (
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
          {equipment.map((e) => (
            <div key={e.id} className="admin-card overflow-hidden rounded-2xl shadow-sm">
              <div className="relative h-32 overflow-hidden">
                <img src={e.image} alt={e.name} className="h-full w-full object-cover" />
                <div className="absolute right-2 top-2"><StatusBadge status={e.condition} /></div>
              </div>
              <div className="p-4">
                <h3 className="truncate font-semibold text-a-text dark:text-a-dark-text">{e.name}</h3>
                <p className="text-xs text-a-muted dark:text-a-dark-muted">{e.brand} · {e.model}</p>
                <p className="mt-2 flex items-center gap-1 text-xs text-a-muted dark:text-a-dark-muted"><MapPin size={12} /> {e.location}</p>
                <div className="mt-2 flex justify-between text-xs text-a-muted dark:text-a-dark-muted">
                  <span>Next: {formatDate(e.nextMaintenance)}</span>
                </div>
                <div className="mt-3 flex gap-2">
                  <Button variant="secondary" size="sm" className="flex-1" icon={<Pencil size={13} />} onClick={() => openEdit(e)}>Edit</Button>
                  <Button variant="danger" size="icon" onClick={() => setDeleteTarget(e)}><Trash2 size={14} /></Button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {meta && meta.totalPages > 1 && (
        <Pagination page={meta.page} totalPages={meta.totalPages} onChange={setPage} totalItems={meta.total} pageSize={PER_PAGE} />
      )}

      <EquipmentFormModal
        open={formOpen}
        onClose={() => { setFormOpen(false); setEditing(null); setFormErrors(undefined); }}
        onSave={handleSave}
        initial={editing}
        saving={saving}
        errors={formErrors}
        uploadImage={editing ? (file) => equipmentService.uploadImage(editing.id, file) : undefined}
      />

      <ConfirmDialog
        open={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        onConfirm={handleDelete}
        title="Delete equipment?"
        description={`${deleteTarget?.name} will be removed from inventory.`}
        confirmLabel="Delete"
        danger
      />
    </div>
  );
}
