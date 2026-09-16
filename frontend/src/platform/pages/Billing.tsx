import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { DollarSign, CheckCircle2, XCircle, Undo2, RotateCcw } from "lucide-react";
import { PageHeader } from "../../admin/components/layout/PageHeader";
import { SearchBar } from "../../admin/components/ui/SearchBar";
import { FilterDropdown } from "../../admin/components/ui/FilterDropdown";
import { DataTable, type Column } from "../../admin/components/ui/DataTable";
import { Pagination } from "../../admin/components/ui/Pagination";
import { StatusBadge } from "../../admin/components/ui/Badge";
import { StatCard } from "../../admin/components/ui/StatCard";
import { Button } from "../../admin/components/ui/Button";
import { ConfirmDialog } from "../../admin/components/ui/ConfirmDialog";
import { Tabs } from "../../admin/components/ui/Tabs";
import { useApiList } from "../../admin/hooks/useApiList";
import { useApiResource } from "../../admin/hooks/useApiResource";
import { useDebouncedValue } from "../../admin/hooks/useDebouncedValue";
import { useToast } from "../../admin/context/ToastContext";
import { formatCurrency, formatDate, formatDateTime } from "../../admin/utils/format";
import { ApiError } from "../../admin/services/apiClient";
import { platformBillingService } from "../services/platformBillingService";
import { platformPaymentService, type PaymentTransaction } from "../services/platformPaymentService";
import type { InvoiceStatus, PlatformInvoice } from "../types";

const invoiceStatuses: InvoiceStatus[] = ["Pending", "Paid", "Failed", "Refunded", "Cancelled"];
const transactionStatuses = ["Pending", "Paid", "Failed", "Refunded"];
const transactionTypes = ["Charge", "Refund"];
const PER_PAGE = 15;

export default function Billing() {
  const [tab, setTab] = useState<"Invoices" | "Transactions">("Invoices");

  return (
    <div>
      <PageHeader title="Billing" description="SaaS billing history, real payment transactions, and refunds across every gym." />
      <div className="mb-4">
        <Tabs tabs={["Invoices", "Transactions"]} active={tab} onChange={(t) => setTab(t as typeof tab)} />
      </div>
      {tab === "Invoices" ? <InvoicesTab /> : <TransactionsTab />}
    </div>
  );
}

function InvoicesTab() {
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState("all");
  const [page, setPage] = useState(1);
  const debouncedQuery = useDebouncedValue(query);

  useEffect(() => setPage(1), [debouncedQuery, status]);

  const { data: invoices, meta, loading, error, refetch } = useApiList(
    (signal) => platformBillingService.list({ search: debouncedQuery || undefined, status: status === "all" ? undefined : status, page, perPage: PER_PAGE }, signal),
    [debouncedQuery, status, page],
  );

  const columns: Column<PlatformInvoice>[] = [
    { key: "invoiceNumber", header: "Invoice #", render: (i) => <span className="font-medium text-a-text dark:text-a-dark-text">{i.invoiceNumber}</span> },
    { key: "tenant", header: "Gym", render: (i) => i.tenant ? (
      <Link to={`/platform/gyms/${i.tenant.id}`} className="text-a-text hover:text-sky-600 dark:text-a-dark-text dark:hover:text-sky-400">{i.tenant.name}</Link>
    ) : <span className="text-a-muted dark:text-a-dark-muted">—</span> },
    { key: "planName", header: "Plan", render: (i) => i.planName ?? "—" },
    { key: "amount", header: "Amount", render: (i) => formatCurrency(i.amount) },
    { key: "status", header: "Status", render: (i) => <StatusBadge status={i.status} /> },
    { key: "issueDate", header: "Issued", render: (i) => formatDate(i.issueDate) },
    { key: "dueDate", header: "Due", render: (i) => formatDate(i.dueDate) },
    { key: "paidDate", header: "Paid", render: (i) => (i.paidDate ? formatDate(i.paidDate) : "—") },
  ];

  return (
    <>
      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <SearchBar value={query} onChange={setQuery} placeholder="Search invoice # or gym..." className="sm:max-w-xs" />
        <FilterDropdown label="Statuses" value={status} onChange={setStatus} options={invoiceStatuses.map((s) => ({ label: s, value: s }))} />
      </div>

      <div className="admin-card rounded-2xl p-4 shadow-sm">
        <DataTable columns={columns} rows={invoices} rowKey={(i) => i.id} loading={loading} error={error} onRetry={refetch} />
        {meta && meta.totalPages > 1 && (
          <Pagination page={meta.page} totalPages={meta.totalPages} onChange={setPage} totalItems={meta.total} pageSize={PER_PAGE} />
        )}
      </div>
    </>
  );
}

function TransactionsTab() {
  const { showToast } = useToast();
  const [status, setStatus] = useState("all");
  const [type, setType] = useState("all");
  const [page, setPage] = useState(1);
  const [refundTarget, setRefundTarget] = useState<PaymentTransaction | null>(null);

  useEffect(() => setPage(1), [status, type]);

  const { data: stats, loading: statsLoading, error: statsError, refetch: refetchStats } = useApiResource(
    (signal) => platformPaymentService.stats(signal),
    [],
  );

  const { data: transactions, meta, loading, error, refetch } = useApiList(
    (signal) => platformPaymentService.list({ status: status === "all" ? undefined : status, type: type === "all" ? undefined : type, page, perPage: PER_PAGE }, signal),
    [status, type, page],
  );

  function confirmRefund() {
    if (!refundTarget) return;
    const target = refundTarget;
    platformPaymentService
      .refund(target.id)
      .then(() => {
        showToast(`Refunded ${formatCurrency(target.amount)} for ${target.reference}.`, "success");
        refetch();
        refetchStats();
      })
      .catch((err) => {
        showToast(err instanceof ApiError ? err.message : "Could not process the refund.", "error");
      });
  }

  const columns: Column<PaymentTransaction>[] = [
    { key: "reference", header: "Reference", render: (t) => <span className="font-mono text-xs text-a-text dark:text-a-dark-text">{t.reference}</span> },
    { key: "tenant", header: "Gym", render: (t) => t.tenant ? (
      <Link to={`/platform/gyms/${t.tenant.id}`} className="text-a-text hover:text-sky-600 dark:text-a-dark-text dark:hover:text-sky-400">{t.tenant.name}</Link>
    ) : <span className="text-a-muted dark:text-a-dark-muted">—</span> },
    { key: "type", header: "Type", render: (t) => <span className="text-xs">{t.type === "Refund" ? <Undo2 size={13} className="mr-1 inline text-rose-500" /> : null}{t.type}</span> },
    { key: "amount", header: "Amount", render: (t) => formatCurrency(t.amount) },
    { key: "status", header: "Status", render: (t) => <StatusBadge status={t.status} /> },
    { key: "createdAt", header: "Date", render: (t) => formatDateTime(t.createdAt) },
    {
      key: "actions",
      header: "",
      render: (t) =>
        t.type === "Charge" && t.status === "Paid" ? (
          <Button size="sm" variant="danger" onClick={() => setRefundTarget(t)}>
            Refund
          </Button>
        ) : null,
    },
  ];

  return (
    <>
      {statsError ? (
        <div className="admin-card mb-6 rounded-2xl p-4 text-sm text-rose-500 shadow-sm">
          Could not load revenue stats. <button onClick={refetchStats} className="underline">Retry</button>
        </div>
      ) : (
        <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <StatCard label="Net Revenue" value={statsLoading || !stats ? "—" : formatCurrency(stats.netRevenue)} icon={<DollarSign size={18} />} />
          <StatCard label="Successful Payments" value={statsLoading || !stats ? "—" : String(stats.successfulPayments)} icon={<CheckCircle2 size={18} />} />
          <StatCard label="Failed Payments" value={statsLoading || !stats ? "—" : String(stats.failedPayments)} icon={<XCircle size={18} />} />
          <StatCard label="Refunds Issued" value={statsLoading || !stats ? "—" : `${stats.refundCount} (${formatCurrency(stats.totalRefunded)})`} icon={<RotateCcw size={18} />} />
        </div>
      )}

      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <FilterDropdown label="Statuses" value={status} onChange={setStatus} options={transactionStatuses.map((s) => ({ label: s, value: s }))} />
        <FilterDropdown label="Types" value={type} onChange={setType} options={transactionTypes.map((t) => ({ label: t, value: t }))} />
      </div>

      <div className="admin-card rounded-2xl p-4 shadow-sm">
        <DataTable columns={columns} rows={transactions} rowKey={(t) => t.id} loading={loading} error={error} onRetry={refetch} />
        {meta && meta.totalPages > 1 && (
          <Pagination page={meta.page} totalPages={meta.totalPages} onChange={setPage} totalItems={meta.total} pageSize={PER_PAGE} />
        )}
      </div>

      <ConfirmDialog
        open={refundTarget !== null}
        onClose={() => setRefundTarget(null)}
        onConfirm={confirmRefund}
        title="Issue refund"
        description={refundTarget ? `Refund ${formatCurrency(refundTarget.amount)} for transaction ${refundTarget.reference}? This cannot be undone.` : ""}
        confirmLabel="Refund"
        danger
      />
    </>
  );
}
