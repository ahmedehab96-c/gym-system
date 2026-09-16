import { useState } from "react";
import { Plus, MoreVertical, Pencil, Trash2 } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { Badge } from "../components/ui/Badge";
import { Button } from "../components/ui/Button";
import { SearchBar } from "../components/ui/SearchBar";
import { Dropdown, DropdownItem } from "../components/ui/Dropdown";
import { ConfirmDialog } from "../components/ui/ConfirmDialog";
import { LoadingState } from "../components/ui/LoadingState";
import { ErrorState } from "../components/ui/ErrorState";
import { EmptyState } from "../components/ui/EmptyState";
import { FacilityFormModal } from "../components/facilities/FacilityFormModal";
import { useApiList } from "../hooks/useApiList";
import { useDebouncedValue } from "../hooks/useDebouncedValue";
import { facilityService, type Facility, type FacilityInput } from "../services/facilityService";
import { useToast } from "../context/ToastContext";
import { ApiError } from "../services/apiClient";

export default function Facilities() {
  const { showToast } = useToast();
  const [query, setQuery] = useState("");
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<Facility | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<Facility | null>(null);
  const [saving, setSaving] = useState(false);
  const [formErrors, setFormErrors] = useState<Record<string, string[]> | undefined>(undefined);
  const debouncedQuery = useDebouncedValue(query);

  const {
    data: facilities,
    loading,
    error,
    refetch,
  } = useApiList((signal) => facilityService.list({ search: debouncedQuery || undefined }, signal).then((data) => ({ data, meta: { page: 1, perPage: data.length, total: data.length, totalPages: 1 } })), [debouncedQuery]);

  function openCreate() {
    setEditing(null);
    setFormErrors(undefined);
    setFormOpen(true);
  }

  function openEdit(f: Facility) {
    setEditing(f);
    setFormErrors(undefined);
    setFormOpen(true);
  }

  async function handleSave(input: FacilityInput, imageFile?: File) {
    setSaving(true);
    setFormErrors(undefined);
    try {
      const saved = editing ? await facilityService.update(editing.id, input) : await facilityService.create(input);
      if (imageFile) await facilityService.uploadImage(saved.id, imageFile);
      showToast(editing ? "Facility updated" : "Facility added");
      setFormOpen(false);
      setEditing(null);
      refetch();
    } catch (err) {
      if (err instanceof ApiError && err.errors) {
        setFormErrors(err.errors);
      } else {
        showToast(err instanceof ApiError ? err.message : "Could not save facility.", "error");
      }
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete() {
    if (!deleteTarget) return;
    try {
      await facilityService.remove(deleteTarget.id);
      showToast("Facility deleted", "error");
      setDeleteTarget(null);
      refetch();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not delete facility.", "error");
    }
  }

  return (
    <div>
      <PageHeader
        title="Facilities"
        description="Physical spaces and areas within the gym"
        action={<Button icon={<Plus size={16} />} onClick={openCreate}>Add Facility</Button>}
      />

      <div className="mb-4"><SearchBar value={query} onChange={setQuery} placeholder="Search facilities..." className="sm:max-w-xs" /></div>

      {loading ? (
        <LoadingState rows={4} />
      ) : error ? (
        <ErrorState message={error} onRetry={refetch} />
      ) : facilities.length === 0 ? (
        <EmptyState title="No facilities found" />
      ) : (
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
          {facilities.map((f) => (
            <div key={f.id} className="admin-card overflow-hidden rounded-2xl shadow-sm">
              {f.image ? (
                <img src={f.image} alt={f.name} className="h-32 w-full object-cover" />
              ) : (
                <div className="h-32 w-full bg-a-surface-2 dark:bg-a-dark-surface-2" />
              )}
              <div className="p-4">
                <div className="flex items-center justify-between">
                  <h3 className="font-semibold text-a-text dark:text-a-dark-text">{f.name}</h3>
                  <div className="flex items-center gap-1">
                    <Badge tone={f.status === "Open" ? "success" : "warning"}>{f.status}</Badge>
                    <Dropdown align="right" trigger={<button className="rounded-lg p-1 text-a-muted hover:bg-a-surface-2 dark:hover:bg-a-dark-surface-2"><MoreVertical size={15} /></button>}>
                      <DropdownItem onClick={() => openEdit(f)}><Pencil size={14} /> Edit</DropdownItem>
                      <DropdownItem onClick={() => setDeleteTarget(f)} className="text-rose-500"><Trash2 size={14} /> Delete</DropdownItem>
                    </Dropdown>
                  </div>
                </div>
                <p className="mt-1 text-xs text-a-muted dark:text-a-dark-muted">{f.area} · Capacity {f.capacity ?? "—"}</p>
              </div>
            </div>
          ))}
        </div>
      )}

      <FacilityFormModal
        open={formOpen}
        onClose={() => { setFormOpen(false); setEditing(null); setFormErrors(undefined); }}
        onSave={handleSave}
        initial={editing}
        saving={saving}
        errors={formErrors}
        uploadImage={editing ? (file) => facilityService.uploadImage(editing.id, file) : undefined}
      />

      <ConfirmDialog
        open={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        onConfirm={handleDelete}
        title="Delete facility?"
        description={`This will permanently remove ${deleteTarget?.name} from your facilities list.`}
        confirmLabel="Delete"
        danger
      />
    </div>
  );
}
