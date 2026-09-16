import { useEffect, useState } from "react";
import { Wallet, TrendingUp, Clock, RotateCcw, Plus } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { StatCard } from "../components/ui/StatCard";
import { SearchBar } from "../components/ui/SearchBar";
import { FilterDropdown } from "../components/ui/FilterDropdown";
import { DataTable, type Column } from "../components/ui/DataTable";
import { Pagination } from "../components/ui/Pagination";
import { Avatar } from "../components/ui/Avatar";
import { StatusBadge, Badge } from "../components/ui/Badge";
import { Button } from "../components/ui/Button";
import { ConfirmDialog } from "../components/ui/ConfirmDialog";
import { PaymentFormModal } from "../components/payments/PaymentFormModal";
import { useApiList } from "../hooks/useApiList";
import { useDebouncedValue } from "../hooks/useDebouncedValue";
import { paymentService, type PaymentInput, type PaymentStats } from "../services/paymentService";
import type { Payment } from "../types";
import { formatCurrency, formatDate } from "../utils/format";
import { useToast } from "../context/ToastContext";
import { ApiError } from "../services/apiClient";

const PER_PAGE = 10;

export default function Payments() {
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState("all");
  const [method, setMethod] = useState("all");
  const [page, setPage] = useState(1);
  const [stats, setStats] = useState<PaymentStats | null>(null);
  const [formOpen, setFormOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [formErrors, setFormErrors] = useState<Record<string, string[]> | undefined>(undefined);
  const [refundTarget, setRefundTarget] = useState<Payment | null>(null);
  const { showToast } = useToast();
  const debouncedQuery = useDebouncedValue(query);

  useEffect(() => setPage(1), [debouncedQuery, status, method]);

  function loadStats() {
    paymentService.stats().then(setStats).catch(() => undefined);
  }

  useEffect(loadStats, []);

  const { data: payments, meta, loading, error, refetch } = useApiList(
    (signal) =>
      paymentService.list(
        {
          search: debouncedQuery || undefined,
          status: status === "all" ? undefined : status,
          method: method === "all" ? undefined : method,
          page,
          perPage: PER_PAGE,
        },
        signal,
      ),
    [debouncedQuery, status, method, page],
  );

  async function handleCreate(input: PaymentInput) {
    setSaving(true);
    setFormErrors(undefined);
    try {
      await paymentService.create(input);
      showToast("Payment recorded");
      setFormOpen(false);
      refetch();
      loadStats();
    } catch (err) {
      if (err instanceof ApiError && err.errors) setFormErrors(err.errors);
      else showToast(err instanceof ApiError ? err.message : "Could not record payment.", "error");
    } finally {
      setSaving(false);
    }
  }

  async function handleRefund() {
    if (!refundTarget) return;
    try {
      await paymentService.refund(refundTarget.id);
      showToast("Payment refunded");
      setRefundTarget(null);
      refetch();
      loadStats();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not refund payment.", "error");
    }
  }

  const columns: Column<Payment>[] = [
    { key: "invoice", header: "Invoice ID", render: (p) => <span className="font-medium">{p.invoiceId || "—"}</span> },
    { key: "member", header: "Member", render: (p) => (
      <div className="flex items-center gap-3">
        <Avatar src={p.memberAvatar} name={p.memberName} size="sm" />
        <span>{p.memberName}</span>
      </div>
    ) },
    { key: "amount", header: "Amount", render: (p) => formatCurrency(p.amount) },
    { key: "method", header: "Method", render: (p) => <Badge>{p.method}</Badge> },
    { key: "date", header: "Date", render: (p) => formatDate(p.date) },
    { key: "status", header: "Status", render: (p) => <StatusBadge status={p.status} /> },
    { key: "actions", header: "", className: "text-right", render: (p) => (
      p.status === "Paid" ? (
        <button
          onClick={(e) => { e.stopPropagation(); setRefundTarget(p); }}
          className="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-medium text-rose-500 transition-colors hover:bg-rose-500/10"
        >
          <RotateCcw size={12} /> Refund
        </button>
      ) : null
    ) },
  ];

  return (
    <div>
      <PageHeader
        title="Payments"
        description="Track and reconcile all member payments"
        action={<Button icon={<Plus size={16} />} onClick={() => { setFormErrors(undefined); setFormOpen(true); }}>Record Payment</Button>}
      />

      <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
        <StatCard label="Today's Revenue" value={formatCurrency(stats?.todayRevenue ?? 0)} icon={<Wallet size={18} />} />
        <StatCard label="Monthly Revenue" value={formatCurrency(stats?.monthlyRevenue ?? 0)} icon={<TrendingUp size={18} />} accent="from-emerald-400/20 to-emerald-500/10" />
        <StatCard label="Pending Payments" value={String(stats?.pending ?? "—")} icon={<Clock size={18} />} accent="from-amber-400/20 to-amber-500/10" />
        <StatCard label="Refunds" value={String(stats?.refunds ?? "—")} icon={<RotateCcw size={18} />} accent="from-rose-400/20 to-rose-500/10" />
      </div>

      <div className="my-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <SearchBar value={query} onChange={setQuery} placeholder="Search member..." className="sm:max-w-xs" />
        <FilterDropdown label="Status" value={status} onChange={setStatus} options={["Paid", "Pending", "Failed", "Refunded"].map((s) => ({ label: s, value: s }))} />
        <FilterDropdown label="Methods" value={method} onChange={setMethod} options={["Cash", "Card", "Bank Transfer", "Online"].map((m) => ({ label: m, value: m }))} />
      </div>

      <div className="admin-card rounded-2xl p-4 shadow-sm">
        <DataTable columns={columns} rows={payments} rowKey={(p) => p.id} loading={loading} error={error} onRetry={refetch} />
        {meta && meta.totalPages > 1 && (
          <Pagination page={meta.page} totalPages={meta.totalPages} onChange={setPage} totalItems={meta.total} pageSize={PER_PAGE} />
        )}
      </div>

      <PaymentFormModal
        open={formOpen}
        onClose={() => { setFormOpen(false); setFormErrors(undefined); }}
        onSave={handleCreate}
        saving={saving}
        errors={formErrors}
      />

      <ConfirmDialog
        open={!!refundTarget}
        onClose={() => setRefundTarget(null)}
        onConfirm={handleRefund}
        title="Refund payment?"
        description={`${formatCurrency(refundTarget?.amount ?? 0)} paid by ${refundTarget?.memberName} will be marked as refunded.`}
        confirmLabel="Refund"
        danger
      />
    </div>
  );
}
