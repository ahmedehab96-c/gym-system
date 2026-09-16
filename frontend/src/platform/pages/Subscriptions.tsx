import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { PageHeader } from "../../admin/components/layout/PageHeader";
import { SearchBar } from "../../admin/components/ui/SearchBar";
import { FilterDropdown } from "../../admin/components/ui/FilterDropdown";
import { DataTable, type Column } from "../../admin/components/ui/DataTable";
import { Pagination } from "../../admin/components/ui/Pagination";
import { StatusBadge } from "../../admin/components/ui/Badge";
import { useApiList } from "../../admin/hooks/useApiList";
import { useDebouncedValue } from "../../admin/hooks/useDebouncedValue";
import { formatCurrency, formatDate } from "../../admin/utils/format";
import { platformSubscriptionService } from "../services/platformSubscriptionService";
import type { PlatformSubscription, SubscriptionStatus } from "../types";

const statuses: SubscriptionStatus[] = ["Trial", "Active", "Past Due", "Cancelled", "Expired"];
const billingCycles = ["Monthly", "Yearly"];
const PER_PAGE = 15;

export default function Subscriptions() {
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState("all");
  const [billingCycle, setBillingCycle] = useState("all");
  const [page, setPage] = useState(1);
  const debouncedQuery = useDebouncedValue(query);

  useEffect(() => setPage(1), [debouncedQuery, status, billingCycle]);

  const { data: subscriptions, meta, loading, error, refetch } = useApiList(
    (signal) => platformSubscriptionService.list(
      { search: debouncedQuery || undefined, status: status === "all" ? undefined : status, billingCycle: billingCycle === "all" ? undefined : billingCycle, page, perPage: PER_PAGE },
      signal,
    ),
    [debouncedQuery, status, billingCycle, page],
  );

  const columns: Column<PlatformSubscription>[] = [
    { key: "tenant", header: "Gym", render: (s) => s.tenant ? (
      <Link to={`/platform/gyms/${s.tenant.id}`} className="font-medium text-a-text hover:text-sky-600 dark:text-a-dark-text dark:hover:text-sky-400">{s.tenant.name}</Link>
    ) : <span className="text-a-muted dark:text-a-dark-muted">—</span> },
    { key: "plan", header: "Plan", render: (s) => s.plan?.name ?? "—" },
    { key: "status", header: "Status", render: (s) => <StatusBadge status={s.status} /> },
    { key: "billingCycle", header: "Billing Cycle", render: (s) => s.billingCycle },
    { key: "price", header: "Amount", render: (s) => formatCurrency(s.price) },
    { key: "nextBillingAt", header: "Next Billing", render: (s) => (s.nextBillingAt ? formatDate(s.nextBillingAt) : "—") },
    { key: "trialEndsAt", header: "Trial Ends", render: (s) => (s.trialEndsAt ? formatDate(s.trialEndsAt) : "—") },
  ];

  return (
    <div>
      <PageHeader title="Subscriptions" description={meta ? `${meta.total} subscriptions across every gym` : "Loading subscriptions…"} />

      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <SearchBar value={query} onChange={setQuery} placeholder="Search by gym name..." className="sm:max-w-xs" />
        <FilterDropdown label="Statuses" value={status} onChange={setStatus} options={statuses.map((s) => ({ label: s, value: s }))} />
        <FilterDropdown label="Billing Cycles" value={billingCycle} onChange={setBillingCycle} options={billingCycles.map((c) => ({ label: c, value: c }))} />
      </div>

      <div className="admin-card rounded-2xl p-4 shadow-sm">
        <DataTable columns={columns} rows={subscriptions} rowKey={(s) => s.id} loading={loading} error={error} onRetry={refetch} />
        {meta && meta.totalPages > 1 && (
          <Pagination page={meta.page} totalPages={meta.totalPages} onChange={setPage} totalItems={meta.total} pageSize={PER_PAGE} />
        )}
      </div>
    </div>
  );
}
