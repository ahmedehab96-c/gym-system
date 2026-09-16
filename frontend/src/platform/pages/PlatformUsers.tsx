import { useEffect, useState } from "react";
import { Plus, Power } from "lucide-react";
import { PageHeader } from "../../admin/components/layout/PageHeader";
import { Button } from "../../admin/components/ui/Button";
import { SearchBar } from "../../admin/components/ui/SearchBar";
import { DataTable, type Column } from "../../admin/components/ui/DataTable";
import { Pagination } from "../../admin/components/ui/Pagination";
import { Avatar } from "../../admin/components/ui/Avatar";
import { StatusBadge, Badge } from "../../admin/components/ui/Badge";
import { ConfirmDialog } from "../../admin/components/ui/ConfirmDialog";
import { useApiList } from "../../admin/hooks/useApiList";
import { useDebouncedValue } from "../../admin/hooks/useDebouncedValue";
import { useToast } from "../../admin/context/ToastContext";
import { useAuth } from "../../admin/context/AuthContext";
import { ApiError } from "../../admin/services/apiClient";
import { timeAgo } from "../../admin/utils/format";
import { PlatformUserFormModal } from "../components/users/PlatformUserFormModal";
import { platformUserService, type PlatformUserInput } from "../services/platformUserService";
import type { PlatformUser } from "../types";

const PER_PAGE = 10;

export default function PlatformUsers() {
  const { user: currentUser } = useAuth();
  const { showToast } = useToast();
  const [query, setQuery] = useState("");
  const [page, setPage] = useState(1);
  const [formOpen, setFormOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [formErrors, setFormErrors] = useState<Record<string, string[]> | undefined>(undefined);
  const [confirmTarget, setConfirmTarget] = useState<PlatformUser | null>(null);
  const debouncedQuery = useDebouncedValue(query);

  useEffect(() => setPage(1), [debouncedQuery]);

  const { data: users, meta, loading, error, refetch } = useApiList(
    (signal) => platformUserService.list({ search: debouncedQuery || undefined, page, perPage: PER_PAGE }, signal),
    [debouncedQuery, page],
  );

  async function handleSave(input: PlatformUserInput) {
    setSaving(true);
    setFormErrors(undefined);
    try {
      await platformUserService.create(input);
      showToast("Platform admin added");
      setFormOpen(false);
      refetch();
    } catch (err) {
      if (err instanceof ApiError && err.errors) setFormErrors(err.errors);
      else showToast(err instanceof ApiError ? err.message : "Could not add platform admin.", "error");
    } finally {
      setSaving(false);
    }
  }

  async function handleToggleStatus() {
    if (!confirmTarget) return;
    const nextStatus = confirmTarget.status === "Active" ? "Inactive" : "Active";
    try {
      await platformUserService.updateStatus(confirmTarget.id, nextStatus);
      showToast(`${confirmTarget.name} ${nextStatus === "Active" ? "activated" : "deactivated"}`);
      refetch();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not update account status.", "error");
    }
  }

  const columns: Column<PlatformUser>[] = [
    { key: "name", header: "Name", render: (u) => (
      <div className="flex items-center gap-3">
        <Avatar src={u.photo ?? undefined} name={u.name} size="sm" />
        <span className="font-medium">{u.name}</span>
      </div>
    ) },
    { key: "role", header: "Role", render: () => <Badge tone="accent">Platform Admin</Badge> },
    { key: "email", header: "Email", render: (u) => <span className="text-a-muted dark:text-a-dark-muted">{u.email}</span> },
    { key: "status", header: "Status", render: (u) => <StatusBadge status={u.status} /> },
    { key: "lastLogin", header: "Last Login", render: (u) => (u.lastLoginAt ? timeAgo(u.lastLoginAt) : "—") },
    { key: "actions", header: "", className: "text-right", render: (u) => (
      <div className="flex justify-end" onClick={(e) => e.stopPropagation()}>
        <Button
          variant="secondary"
          size="sm"
          icon={<Power size={13} />}
          disabled={u.id === currentUser?.id}
          onClick={() => setConfirmTarget(u)}
        >
          {u.status === "Active" ? "Deactivate" : "Activate"}
        </Button>
      </div>
    ) },
  ];

  return (
    <div>
      <PageHeader
        title="Platform Users"
        description={meta ? `${meta.total} Super Admin accounts` : "Loading platform users…"}
        action={<Button icon={<Plus size={16} />} onClick={() => setFormOpen(true)}>Add Platform Admin</Button>}
      />

      <div className="mb-4">
        <SearchBar value={query} onChange={setQuery} placeholder="Search platform admins..." className="sm:max-w-xs" />
      </div>

      <div className="admin-card rounded-2xl p-4 shadow-sm">
        <DataTable columns={columns} rows={users} rowKey={(u) => u.id} loading={loading} error={error} onRetry={refetch} />
        {meta && meta.totalPages > 1 && (
          <Pagination page={meta.page} totalPages={meta.totalPages} onChange={setPage} totalItems={meta.total} pageSize={PER_PAGE} />
        )}
      </div>

      <PlatformUserFormModal
        open={formOpen}
        onClose={() => { setFormOpen(false); setFormErrors(undefined); }}
        onSave={handleSave}
        saving={saving}
        errors={formErrors}
      />

      <ConfirmDialog
        open={!!confirmTarget}
        onClose={() => setConfirmTarget(null)}
        onConfirm={handleToggleStatus}
        title={confirmTarget?.status === "Active" ? "Deactivate this account?" : "Activate this account?"}
        description={`${confirmTarget?.name} will ${confirmTarget?.status === "Active" ? "lose" : "regain"} access to the platform console.`}
        confirmLabel={confirmTarget?.status === "Active" ? "Deactivate" : "Activate"}
        danger={confirmTarget?.status === "Active"}
      />
    </div>
  );
}
