import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { Plus, Eye, Pencil, Trash2, Ban, RefreshCcw, MoreVertical } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { Button } from "../components/ui/Button";
import { SearchBar } from "../components/ui/SearchBar";
import { FilterDropdown } from "../components/ui/FilterDropdown";
import { DataTable, type Column } from "../components/ui/DataTable";
import { Pagination } from "../components/ui/Pagination";
import { Avatar } from "../components/ui/Avatar";
import { StatusBadge } from "../components/ui/Badge";
import { Dropdown, DropdownItem } from "../components/ui/Dropdown";
import { ConfirmDialog } from "../components/ui/ConfirmDialog";
import { MemberFormModal } from "../components/members/MemberFormModal";
import { useApiList } from "../hooks/useApiList";
import { useDebouncedValue } from "../hooks/useDebouncedValue";
import { memberService, type MemberInput } from "../services/memberService";
import { membershipPlanService } from "../services/membershipPlanService";
import type { Member, MembershipPlan } from "../types";
import { formatDate } from "../utils/format";
import { useToast } from "../context/ToastContext";
import { ApiError } from "../services/apiClient";

const PER_PAGE = 8;

export default function Members() {
  const navigate = useNavigate();
  const { showToast } = useToast();
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState("all");
  const [planId, setPlanId] = useState("all");
  const [sortKey, setSortKey] = useState("name");
  const [sortDir, setSortDir] = useState<"asc" | "desc">("asc");
  const [page, setPage] = useState(1);
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<Member | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<Member | null>(null);
  const [saving, setSaving] = useState(false);
  const [formErrors, setFormErrors] = useState<Record<string, string[]> | undefined>(undefined);
  const [plans, setPlans] = useState<MembershipPlan[]>([]);

  const debouncedQuery = useDebouncedValue(query);

  useEffect(() => {
    membershipPlanService.list().then(setPlans).catch(() => undefined);
  }, []);

  useEffect(() => {
    setPage(1);
  }, [debouncedQuery, status, planId]);

  const { data: members, meta, loading, error, refetch } = useApiList(
    (signal) =>
      memberService.list(
        {
          search: debouncedQuery || undefined,
          status: status === "all" ? undefined : status,
          planId: planId === "all" ? undefined : planId,
          sortBy: sortKey,
          sortDir,
          page,
          perPage: PER_PAGE,
        },
        signal,
      ),
    [debouncedQuery, status, planId, sortKey, sortDir, page],
  );

  function handleSort(key: string) {
    if (sortKey === key) setSortDir((d) => (d === "asc" ? "desc" : "asc"));
    else {
      setSortKey(key);
      setSortDir("asc");
    }
  }

  async function handleSave(member: Member) {
    setSaving(true);
    setFormErrors(undefined);
    const input: MemberInput = {
      name: member.name,
      gender: member.gender,
      phone: member.phone,
      email: member.email,
      address: member.address,
      dob: member.dob,
      planId: member.planId,
      startDate: member.startDate,
      expiryDate: member.expiryDate,
      status: member.status,
      emergencyContact: member.emergencyContact,
    };
    try {
      if (editing) {
        await memberService.update(editing.id, input);
        showToast("Member updated successfully");
      } else {
        await memberService.create(input);
        showToast("Member added successfully");
      }
      setFormOpen(false);
      setEditing(null);
      refetch();
    } catch (err) {
      if (err instanceof ApiError && err.errors) {
        setFormErrors(err.errors);
      } else {
        showToast(err instanceof ApiError ? err.message : "Could not save member.", "error");
      }
    } finally {
      setSaving(false);
    }
  }

  async function updateStatus(member: Member, newStatus: Member["status"]) {
    try {
      await memberService.updateStatus(member.id, newStatus);
      showToast(`${member.name} marked as ${newStatus}`);
      refetch();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not update status.", "error");
    }
  }

  async function handleDelete() {
    if (!deleteTarget) return;
    try {
      await memberService.remove(deleteTarget.id);
      showToast("Member deleted", "error");
      refetch();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not delete member.", "error");
    }
  }

  const columns: Column<Member>[] = [
    {
      key: "name",
      header: "Member",
      sortable: true,
      render: (m) => (
        <div className="flex items-center gap-3">
          <Avatar src={m.avatar} name={m.name} size="sm" />
          <div>
            <p className="font-medium">{m.name}</p>
            <p className="text-xs text-a-muted dark:text-a-dark-muted">{m.memberId}</p>
          </div>
        </div>
      ),
    },
    { key: "phone", header: "Phone", render: (m) => m.phone },
    { key: "email", header: "Email", render: (m) => <span className="text-a-muted dark:text-a-dark-muted">{m.email}</span> },
    { key: "planName", header: "Membership", render: (m) => m.planName },
    { key: "startDate", header: "Start Date", render: (m) => formatDate(m.startDate) },
    { key: "expiryDate", header: "Expiry Date", sortable: true, render: (m) => formatDate(m.expiryDate) },
    { key: "status", header: "Status", render: (m) => <StatusBadge status={m.status} /> },
    { key: "attendanceRate", header: "Attendance", sortable: true, render: (m) => `${m.attendanceRate}%` },
    {
      key: "actions",
      header: "",
      className: "text-right",
      render: (m) => (
        <div onClick={(e) => e.stopPropagation()} className="flex justify-end">
          <Dropdown align="right" trigger={<button className="rounded-lg p-1.5 text-a-muted hover:bg-a-surface-2 dark:hover:bg-a-dark-surface-2"><MoreVertical size={16} /></button>}>
            <DropdownItem onClick={() => navigate(`/admin/members/${m.id}`)}><Eye size={14} /> View</DropdownItem>
            <DropdownItem onClick={() => { setEditing(m); setFormErrors(undefined); setFormOpen(true); }}><Pencil size={14} /> Edit</DropdownItem>
            <DropdownItem onClick={() => updateStatus(m, "Active")}><RefreshCcw size={14} /> Renew</DropdownItem>
            <DropdownItem onClick={() => updateStatus(m, "Suspended")}><Ban size={14} /> Suspend</DropdownItem>
            <DropdownItem onClick={() => setDeleteTarget(m)} className="text-rose-500"><Trash2 size={14} /> Delete</DropdownItem>
          </Dropdown>
        </div>
      ),
    },
  ];

  return (
    <div>
      <PageHeader
        title="Members"
        description={meta ? `${meta.total} total members` : "Loading members…"}
        action={
          <Button icon={<Plus size={16} />} onClick={() => { setEditing(null); setFormErrors(undefined); setFormOpen(true); }}>
            Add Member
          </Button>
        }
      />

      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <SearchBar value={query} onChange={setQuery} placeholder="Search by name, email, or ID..." className="sm:max-w-xs" />
        <FilterDropdown
          label="Status"
          value={status}
          onChange={setStatus}
          options={["Active", "Inactive", "Suspended", "Expired"].map((s) => ({ label: s, value: s }))}
        />
        <FilterDropdown
          label="Plans"
          value={planId}
          onChange={setPlanId}
          options={plans.map((p) => ({ label: p.name, value: p.id }))}
        />
      </div>

      <div className="admin-card rounded-2xl p-4 shadow-sm">
        <DataTable
          columns={columns}
          rows={members}
          rowKey={(m) => m.id}
          sortKey={sortKey}
          sortDir={sortDir}
          onSort={handleSort}
          onRowClick={(m) => navigate(`/admin/members/${m.id}`)}
          loading={loading}
          error={error}
          onRetry={refetch}
        />
        {meta && meta.totalPages > 1 && (
          <Pagination page={meta.page} totalPages={meta.totalPages} onChange={setPage} totalItems={meta.total} pageSize={PER_PAGE} />
        )}
      </div>

      <MemberFormModal
        open={formOpen}
        onClose={() => { setFormOpen(false); setEditing(null); setFormErrors(undefined); }}
        onSave={handleSave}
        initial={editing}
        plans={plans}
        saving={saving}
        errors={formErrors}
      />

      <ConfirmDialog
        open={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        onConfirm={handleDelete}
        title="Delete member?"
        description={`This will permanently remove ${deleteTarget?.name} from your member list.`}
        confirmLabel="Delete"
        danger
      />
    </div>
  );
}
