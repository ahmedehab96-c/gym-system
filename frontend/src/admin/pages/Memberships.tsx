import { useEffect, useState } from "react";
import { ArrowLeftRight, Ban, RefreshCcw, XCircle, MoreVertical } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { SearchBar } from "../components/ui/SearchBar";
import { Tabs } from "../components/ui/Tabs";
import { DataTable, type Column } from "../components/ui/DataTable";
import { Pagination } from "../components/ui/Pagination";
import { Avatar } from "../components/ui/Avatar";
import { StatusBadge } from "../components/ui/Badge";
import { Dropdown, DropdownItem } from "../components/ui/Dropdown";
import { Modal } from "../components/ui/Modal";
import { Select } from "../components/ui/Select";
import { Button } from "../components/ui/Button";
import { useApiList } from "../hooks/useApiList";
import { useDebouncedValue } from "../hooks/useDebouncedValue";
import { membershipService } from "../services/membershipService";
import { membershipPlanService } from "../services/membershipPlanService";
import type { Membership, MembershipPlan } from "../types";
import { formatCurrency, formatDate } from "../utils/format";
import { useToast } from "../context/ToastContext";
import { ApiError } from "../services/apiClient";

const tabs = ["All", "Active", "Expiring Soon", "Expired", "Suspended"];
const PER_PAGE = 8;

export default function Memberships() {
  const [tab, setTab] = useState("All");
  const [query, setQuery] = useState("");
  const [page, setPage] = useState(1);
  const { showToast } = useToast();
  const debouncedQuery = useDebouncedValue(query);
  const [plans, setPlans] = useState<MembershipPlan[]>([]);
  const [changePlanTarget, setChangePlanTarget] = useState<Membership | null>(null);
  const [newPlanId, setNewPlanId] = useState("");
  const [changingPlan, setChangingPlan] = useState(false);

  useEffect(() => setPage(1), [tab, debouncedQuery]);

  useEffect(() => {
    membershipPlanService.list().then(setPlans).catch(() => undefined);
  }, []);

  const { data: memberships, meta, loading, error, refetch } = useApiList(
    (signal) =>
      membershipService.list(
        { search: debouncedQuery || undefined, status: tab === "All" ? undefined : tab, page, perPage: PER_PAGE },
        signal,
      ),
    [tab, debouncedQuery, page],
  );

  async function renew(m: Membership) {
    try {
      await membershipService.renew(m.id);
      showToast(`${m.memberName}'s membership renewed`);
      refetch();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not renew membership.", "error");
    }
  }

  async function suspend(m: Membership) {
    try {
      await membershipService.suspend(m.id);
      showToast(`${m.memberName}'s membership suspended`);
      refetch();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not suspend membership.", "error");
    }
  }

  async function cancel(m: Membership) {
    try {
      await membershipService.cancel(m.id);
      showToast(`${m.memberName}'s membership cancelled`);
      refetch();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not cancel membership.", "error");
    }
  }

  function openChangePlan(m: Membership) {
    setChangePlanTarget(m);
    setNewPlanId(m.planId);
  }

  async function handleChangePlan() {
    if (!changePlanTarget || !newPlanId) return;
    setChangingPlan(true);
    try {
      await membershipService.changePlan(changePlanTarget.id, newPlanId);
      showToast(`${changePlanTarget.memberName}'s plan changed`);
      setChangePlanTarget(null);
      refetch();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not change plan.", "error");
    } finally {
      setChangingPlan(false);
    }
  }

  const columns: Column<Membership>[] = [
    {
      key: "memberName",
      header: "Member",
      render: (m) => (
        <div className="flex items-center gap-3">
          <Avatar src={m.memberAvatar} name={m.memberName} size="sm" />
          <span className="font-medium">{m.memberName}</span>
        </div>
      ),
    },
    { key: "planName", header: "Plan", render: (m) => m.planName },
    { key: "startDate", header: "Start Date", render: (m) => formatDate(m.startDate) },
    { key: "expiryDate", header: "Expiry Date", render: (m) => formatDate(m.expiryDate) },
    { key: "price", header: "Price", render: (m) => formatCurrency(m.price) },
    { key: "status", header: "Status", render: (m) => <StatusBadge status={m.status} /> },
    {
      key: "actions",
      header: "",
      className: "text-right",
      render: (m) => (
        <div className="flex justify-end" onClick={(e) => e.stopPropagation()}>
          <Dropdown trigger={<button className="rounded-lg p-1.5 text-a-muted hover:bg-a-surface-2 dark:hover:bg-a-dark-surface-2"><MoreVertical size={16} /></button>}>
            <DropdownItem onClick={() => renew(m)}><RefreshCcw size={14} /> Renew</DropdownItem>
            <DropdownItem onClick={() => openChangePlan(m)}><ArrowLeftRight size={14} /> Change Plan</DropdownItem>
            <DropdownItem onClick={() => suspend(m)}><Ban size={14} /> Suspend</DropdownItem>
            <DropdownItem onClick={() => cancel(m)} className="text-rose-500"><XCircle size={14} /> Cancel</DropdownItem>
          </Dropdown>
        </div>
      ),
    },
  ];

  return (
    <div>
      <PageHeader title="Memberships" description="Track and manage all active membership records" />

      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <Tabs tabs={tabs} active={tab} onChange={setTab} />
        <SearchBar value={query} onChange={setQuery} placeholder="Search member..." className="sm:max-w-xs" />
      </div>

      <div className="admin-card rounded-2xl p-4 shadow-sm">
        <DataTable columns={columns} rows={memberships} rowKey={(m) => m.id} loading={loading} error={error} onRetry={refetch} />
        {meta && meta.totalPages > 1 && (
          <Pagination page={meta.page} totalPages={meta.totalPages} onChange={setPage} totalItems={meta.total} pageSize={PER_PAGE} />
        )}
      </div>

      <Modal
        open={!!changePlanTarget}
        onClose={() => setChangePlanTarget(null)}
        title="Change Membership Plan"
        description={changePlanTarget ? `${changePlanTarget.memberName} is currently on ${changePlanTarget.planName}` : undefined}
        size="sm"
        footer={
          <>
            <Button variant="secondary" onClick={() => setChangePlanTarget(null)}>Cancel</Button>
            <Button disabled={changingPlan} onClick={handleChangePlan}>{changingPlan ? "Saving..." : "Change Plan"}</Button>
          </>
        }
      >
        <Select
          label="New Plan"
          value={newPlanId}
          onChange={(e) => setNewPlanId(e.target.value)}
          options={plans.map((p) => ({ label: `${p.name} — ${formatCurrency(p.price)}`, value: p.id }))}
        />
      </Modal>
    </div>
  );
}
