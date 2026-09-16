import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { Plus, Pencil, Ban, CheckCircle2, RotateCcw, MoreVertical } from "lucide-react";
import { PageHeader } from "../../admin/components/layout/PageHeader";
import { Button } from "../../admin/components/ui/Button";
import { SearchBar } from "../../admin/components/ui/SearchBar";
import { FilterDropdown } from "../../admin/components/ui/FilterDropdown";
import { DataTable, type Column } from "../../admin/components/ui/DataTable";
import { Pagination } from "../../admin/components/ui/Pagination";
import { StatusBadge } from "../../admin/components/ui/Badge";
import { Dropdown, DropdownItem } from "../../admin/components/ui/Dropdown";
import { ConfirmDialog } from "../../admin/components/ui/ConfirmDialog";
import { useApiList } from "../../admin/hooks/useApiList";
import { useDebouncedValue } from "../../admin/hooks/useDebouncedValue";
import { useToast } from "../../admin/context/ToastContext";
import { ApiError } from "../../admin/services/apiClient";
import { formatDate } from "../../admin/utils/format";
import { GymFormModal } from "../components/gyms/GymFormModal";
import { platformGymService, type GymInput } from "../services/platformGymService";
import type { Gym, GymStatus } from "../types";

const statuses: GymStatus[] = ["Trial", "Active", "Suspended", "Inactive"];
const PER_PAGE = 10;

export default function Gyms() {
  const navigate = useNavigate();
  const { showToast } = useToast();
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState("all");
  const [page, setPage] = useState(1);
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<Gym | null>(null);
  const [saving, setSaving] = useState(false);
  const [formErrors, setFormErrors] = useState<Record<string, string[]> | undefined>(undefined);
  const [confirmTarget, setConfirmTarget] = useState<{ gym: Gym; action: "suspend" | "activate" | "reactivate" } | null>(null);
  const debouncedQuery = useDebouncedValue(query);

  useEffect(() => setPage(1), [debouncedQuery, status]);

  const { data: gyms, meta, loading, error, refetch } = useApiList(
    (signal) => platformGymService.list({ search: debouncedQuery || undefined, status: status === "all" ? undefined : status, page, perPage: PER_PAGE }, signal),
    [debouncedQuery, status, page],
  );

  function openCreate() {
    setEditing(null);
    setFormErrors(undefined);
    setFormOpen(true);
  }

  function openEdit(gym: Gym) {
    setEditing(gym);
    setFormErrors(undefined);
    setFormOpen(true);
  }

  async function handleSave(input: GymInput) {
    setSaving(true);
    setFormErrors(undefined);
    try {
      if (editing) await platformGymService.update(editing.id, input);
      else await platformGymService.create(input);
      showToast(editing ? "Gym updated" : "Gym created");
      setFormOpen(false);
      setEditing(null);
      refetch();
    } catch (err) {
      if (err instanceof ApiError && err.errors) setFormErrors(err.errors);
      else showToast(err instanceof ApiError ? err.message : "Could not save gym.", "error");
    } finally {
      setSaving(false);
    }
  }

  async function handleConfirmedAction() {
    if (!confirmTarget) return;
    const { gym, action } = confirmTarget;
    try {
      if (action === "suspend") await platformGymService.suspend(gym.id);
      else if (action === "activate") await platformGymService.activate(gym.id);
      else await platformGymService.reactivate(gym.id);
      showToast(`Gym ${action === "suspend" ? "suspended" : action === "activate" ? "activated" : "reactivated"}`, action === "suspend" ? "warning" : "success");
      refetch();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not update gym status.", "error");
    }
  }

  const columns: Column<Gym>[] = [
    { key: "name", header: "Gym", render: (g) => (
      <div>
        <p className="font-medium text-a-text dark:text-a-dark-text">{g.name}</p>
        <p className="text-xs text-a-muted dark:text-a-dark-muted">{g.slug}</p>
      </div>
    ) },
    { key: "owner", header: "Owner", render: (g) => g.owner ? (
      <div>
        <p className="text-sm">{g.owner.name}</p>
        <p className="text-xs text-a-muted dark:text-a-dark-muted">{g.owner.email}</p>
      </div>
    ) : <span className="text-a-muted dark:text-a-dark-muted">—</span> },
    { key: "plan", header: "Plan", render: (g) => g.subscription?.planName ?? <span className="text-a-muted dark:text-a-dark-muted">No subscription</span> },
    { key: "staff", header: "Staff", render: (g) => g.staffCount ?? 0 },
    { key: "status", header: "Status", render: (g) => <StatusBadge status={g.status} /> },
    { key: "createdAt", header: "Joined", render: (g) => formatDate(g.createdAt) },
    { key: "actions", header: "", className: "text-right", render: (g) => (
      <div className="flex justify-end" onClick={(e) => e.stopPropagation()}>
        <Dropdown trigger={<button className="rounded-lg p-1.5 text-a-muted hover:bg-a-surface-2 dark:hover:bg-a-dark-surface-2"><MoreVertical size={16} /></button>}>
          <DropdownItem onClick={() => openEdit(g)}><Pencil size={14} /> Edit</DropdownItem>
          {g.status === "Suspended" ? (
            <DropdownItem onClick={() => setConfirmTarget({ gym: g, action: "reactivate" })}><RotateCcw size={14} /> Reactivate</DropdownItem>
          ) : g.status === "Active" ? (
            <DropdownItem onClick={() => setConfirmTarget({ gym: g, action: "suspend" })} className="text-rose-500"><Ban size={14} /> Suspend</DropdownItem>
          ) : (
            <DropdownItem onClick={() => setConfirmTarget({ gym: g, action: "activate" })}><CheckCircle2 size={14} /> Activate</DropdownItem>
          )}
        </Dropdown>
      </div>
    ) },
  ];

  return (
    <div>
      <PageHeader
        title="Gyms"
        description={meta ? `${meta.total} gym${meta.total === 1 ? "" : "s"} on the platform` : "Loading gyms…"}
        action={<Button icon={<Plus size={16} />} onClick={openCreate}>Add Gym</Button>}
      />

      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <SearchBar value={query} onChange={setQuery} placeholder="Search gyms..." className="sm:max-w-xs" />
        <FilterDropdown label="Statuses" value={status} onChange={setStatus} options={statuses.map((s) => ({ label: s, value: s }))} />
      </div>

      <div className="admin-card rounded-2xl p-4 shadow-sm">
        <DataTable columns={columns} rows={gyms} rowKey={(g) => g.id} loading={loading} error={error} onRetry={refetch} onRowClick={(g) => navigate(`/platform/gyms/${g.id}`)} />
        {meta && meta.totalPages > 1 && (
          <Pagination page={meta.page} totalPages={meta.totalPages} onChange={setPage} totalItems={meta.total} pageSize={PER_PAGE} />
        )}
      </div>

      <GymFormModal
        open={formOpen}
        onClose={() => { setFormOpen(false); setEditing(null); setFormErrors(undefined); }}
        onSave={handleSave}
        initial={editing}
        saving={saving}
        errors={formErrors}
      />

      <ConfirmDialog
        open={!!confirmTarget}
        onClose={() => setConfirmTarget(null)}
        onConfirm={handleConfirmedAction}
        title={confirmTarget?.action === "suspend" ? "Suspend this gym?" : confirmTarget?.action === "activate" ? "Activate this gym?" : "Reactivate this gym?"}
        description={`${confirmTarget?.gym.name} ${confirmTarget?.action === "suspend" ? "will lose access to the admin console immediately." : "will regain full access to the admin console."}`}
        confirmLabel={confirmTarget?.action === "suspend" ? "Suspend" : confirmTarget?.action === "activate" ? "Activate" : "Reactivate"}
        danger={confirmTarget?.action === "suspend"}
      />
    </div>
  );
}
