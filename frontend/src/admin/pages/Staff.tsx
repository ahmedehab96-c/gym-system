import { useEffect, useState } from "react";
import { Plus, Pencil, Trash2, MoreVertical } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { Button } from "../components/ui/Button";
import { SearchBar } from "../components/ui/SearchBar";
import { FilterDropdown } from "../components/ui/FilterDropdown";
import { DataTable, type Column } from "../components/ui/DataTable";
import { Pagination } from "../components/ui/Pagination";
import { Avatar } from "../components/ui/Avatar";
import { StatusBadge, Badge } from "../components/ui/Badge";
import { Dropdown, DropdownItem } from "../components/ui/Dropdown";
import { ConfirmDialog } from "../components/ui/ConfirmDialog";
import { StaffFormModal } from "../components/staff/StaffFormModal";
import { useApiList } from "../hooks/useApiList";
import { useDebouncedValue } from "../hooks/useDebouncedValue";
import { staffService, type StaffInput } from "../services/staffService";
import type { StaffMember, StaffRole } from "../types";
import { timeAgo } from "../utils/format";
import { useToast } from "../context/ToastContext";
import { ApiError } from "../services/apiClient";

const roles: StaffRole[] = ["Super Admin", "Admin", "Manager", "Receptionist", "Trainer", "Accountant"];
const PER_PAGE = 10;

export default function Staff() {
  const [query, setQuery] = useState("");
  const [role, setRole] = useState("all");
  const [page, setPage] = useState(1);
  const [deleteTarget, setDeleteTarget] = useState<StaffMember | null>(null);
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<StaffMember | null>(null);
  const [saving, setSaving] = useState(false);
  const [formErrors, setFormErrors] = useState<Record<string, string[]> | undefined>(undefined);
  const { showToast } = useToast();
  const debouncedQuery = useDebouncedValue(query);

  useEffect(() => setPage(1), [debouncedQuery, role]);

  const { data: staff, meta, loading, error, refetch } = useApiList(
    (signal) => staffService.list({ search: debouncedQuery || undefined, role: role === "all" ? undefined : role, page, perPage: PER_PAGE }, signal),
    [debouncedQuery, role, page],
  );

  function openCreate() {
    setEditing(null);
    setFormErrors(undefined);
    setFormOpen(true);
  }

  function openEdit(s: StaffMember) {
    setEditing(s);
    setFormErrors(undefined);
    setFormOpen(true);
  }

  async function handleSave(input: StaffInput, photoFile?: File) {
    setSaving(true);
    setFormErrors(undefined);
    try {
      const saved = editing
        ? await staffService.update(editing.id, input.password ? input : { ...input, password: undefined })
        : await staffService.create(input);
      if (photoFile) await staffService.uploadPhoto(saved.id, photoFile);
      showToast(editing ? "Staff member updated" : "Staff member added");
      setFormOpen(false);
      setEditing(null);
      refetch();
    } catch (err) {
      if (err instanceof ApiError && err.errors) setFormErrors(err.errors);
      else showToast(err instanceof ApiError ? err.message : "Could not save staff member.", "error");
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete() {
    if (!deleteTarget) return;
    try {
      await staffService.remove(deleteTarget.id);
      showToast("Staff member removed", "error");
      setDeleteTarget(null);
      refetch();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not remove staff member.", "error");
    }
  }

  const columns: Column<StaffMember>[] = [
    { key: "name", header: "Name", render: (s) => (
      <div className="flex items-center gap-3">
        <Avatar src={s.photo} name={s.name} size="sm" />
        <span className="font-medium">{s.name}</span>
      </div>
    ) },
    { key: "role", header: "Role", render: (s) => <Badge tone="accent">{s.role}</Badge> },
    { key: "email", header: "Email", render: (s) => <span className="text-a-muted dark:text-a-dark-muted">{s.email}</span> },
    { key: "phone", header: "Phone", render: (s) => s.phone },
    { key: "status", header: "Status", render: (s) => <StatusBadge status={s.status} /> },
    { key: "lastLogin", header: "Last Login", render: (s) => (s.lastLogin ? timeAgo(s.lastLogin) : "—") },
    { key: "actions", header: "", className: "text-right", render: (s) => (
      <div className="flex justify-end" onClick={(e) => e.stopPropagation()}>
        <Dropdown trigger={<button className="rounded-lg p-1.5 text-a-muted hover:bg-a-surface-2 dark:hover:bg-a-dark-surface-2"><MoreVertical size={16} /></button>}>
          <DropdownItem onClick={() => openEdit(s)}><Pencil size={14} /> Edit</DropdownItem>
          <DropdownItem onClick={() => setDeleteTarget(s)} className="text-rose-500"><Trash2 size={14} /> Remove</DropdownItem>
        </Dropdown>
      </div>
    ) },
  ];

  return (
    <div>
      <PageHeader
        title="Staff"
        description={meta ? `${meta.total} staff members` : "Loading staff…"}
        action={<Button icon={<Plus size={16} />} onClick={openCreate}>Add Staff</Button>}
      />

      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <SearchBar value={query} onChange={setQuery} placeholder="Search staff..." className="sm:max-w-xs" />
        <FilterDropdown label="Roles" value={role} onChange={setRole} options={roles.map((r) => ({ label: r, value: r }))} />
      </div>

      <div className="admin-card rounded-2xl p-4 shadow-sm">
        <DataTable columns={columns} rows={staff} rowKey={(s) => s.id} loading={loading} error={error} onRetry={refetch} />
        {meta && meta.totalPages > 1 && (
          <Pagination page={meta.page} totalPages={meta.totalPages} onChange={setPage} totalItems={meta.total} pageSize={PER_PAGE} />
        )}
      </div>

      <StaffFormModal
        open={formOpen}
        onClose={() => { setFormOpen(false); setEditing(null); setFormErrors(undefined); }}
        onSave={handleSave}
        initial={editing}
        saving={saving}
        errors={formErrors}
        uploadPhoto={editing ? (file) => staffService.uploadPhoto(editing.id, file) : undefined}
      />

      <ConfirmDialog
        open={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        onConfirm={handleDelete}
        title="Remove staff member?"
        description={`${deleteTarget?.name} will lose access to the admin console.`}
        confirmLabel="Remove"
        danger
      />
    </div>
  );
}
