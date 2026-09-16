import { useEffect, useState } from "react";
import { Download, Printer, Eye } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { SearchBar } from "../components/ui/SearchBar";
import { FilterDropdown } from "../components/ui/FilterDropdown";
import { DataTable, type Column } from "../components/ui/DataTable";
import { Pagination } from "../components/ui/Pagination";
import { Avatar } from "../components/ui/Avatar";
import { StatusBadge } from "../components/ui/Badge";
import { Modal } from "../components/ui/Modal";
import { Button } from "../components/ui/Button";
import { useApiList } from "../hooks/useApiList";
import { useDebouncedValue } from "../hooks/useDebouncedValue";
import { invoiceService } from "../services/invoiceService";
import type { Invoice } from "../types";
import { formatCurrency, formatDate } from "../utils/format";
import { useToast } from "../context/ToastContext";

const PER_PAGE = 10;

export default function Invoices() {
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState("all");
  const [page, setPage] = useState(1);
  const [viewing, setViewing] = useState<Invoice | null>(null);
  const { showToast } = useToast();
  const debouncedQuery = useDebouncedValue(query);

  useEffect(() => setPage(1), [debouncedQuery, status]);

  const { data: invoices, meta, loading, error, refetch } = useApiList(
    (signal) =>
      invoiceService.list({ search: debouncedQuery || undefined, status: status === "all" ? undefined : status, page, perPage: PER_PAGE }, signal),
    [debouncedQuery, status, page],
  );

  const columns: Column<Invoice>[] = [
    { key: "id", header: "Invoice ID", render: (i) => <span className="font-medium">{i.invoiceNumber}</span> },
    { key: "member", header: "Member", render: (i) => (
      <div className="flex items-center gap-3">
        <Avatar src={i.memberAvatar} name={i.memberName} size="sm" />
        <span>{i.memberName}</span>
      </div>
    ) },
    { key: "issue", header: "Issue Date", render: (i) => formatDate(i.issueDate) },
    { key: "due", header: "Due Date", render: (i) => formatDate(i.dueDate) },
    { key: "total", header: "Total", render: (i) => formatCurrency(i.total) },
    { key: "status", header: "Status", render: (i) => <StatusBadge status={i.status} /> },
    { key: "actions", header: "", className: "text-right", render: (i) => (
      <button onClick={(e) => { e.stopPropagation(); setViewing(i); }} className="rounded-lg p-1.5 text-a-muted hover:bg-a-surface-2 dark:hover:bg-a-dark-surface-2">
        <Eye size={15} />
      </button>
    ) },
  ];

  return (
    <div>
      <PageHeader title="Invoices" description="Generate and manage member invoices" />

      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <SearchBar value={query} onChange={setQuery} placeholder="Search member..." className="sm:max-w-xs" />
        <FilterDropdown label="Status" value={status} onChange={setStatus} options={["Paid", "Unpaid", "Overdue", "Draft"].map((s) => ({ label: s, value: s }))} />
      </div>

      <div className="admin-card rounded-2xl p-4 shadow-sm">
        <DataTable columns={columns} rows={invoices} rowKey={(i) => i.id} onRowClick={setViewing} loading={loading} error={error} onRetry={refetch} />
        {meta && meta.totalPages > 1 && (
          <Pagination page={meta.page} totalPages={meta.totalPages} onChange={setPage} totalItems={meta.total} pageSize={PER_PAGE} />
        )}
      </div>

      <Modal
        open={!!viewing}
        onClose={() => setViewing(null)}
        title={viewing?.invoiceNumber}
        size="md"
        footer={
          <>
            <Button variant="secondary" icon={<Printer size={15} />} onClick={() => window.print()}>Print</Button>
            <Button icon={<Download size={15} />} onClick={() => showToast("Use your browser's print dialog and choose \"Save as PDF\" to download this invoice.")}>Download PDF</Button>
          </>
        }
      >
        {viewing && (
          <div>
            <div className="flex items-center justify-between border-b border-a-border pb-4 dark:border-a-dark-border">
              <div className="flex items-center gap-3">
                <Avatar src={viewing.memberAvatar} name={viewing.memberName} size="md" />
                <div>
                  <p className="font-medium text-a-text dark:text-a-dark-text">{viewing.memberName}</p>
                  <p className="text-xs text-a-muted dark:text-a-dark-muted">Issued {formatDate(viewing.issueDate)}</p>
                </div>
              </div>
              <StatusBadge status={viewing.status} />
            </div>
            <div className="mt-4 space-y-2">
              {viewing.items.map((item, i) => (
                <div key={i} className="flex justify-between text-sm">
                  <span className="text-a-muted dark:text-a-dark-muted">{item.description}</span>
                  <span className="text-a-text dark:text-a-dark-text">{formatCurrency(item.amount)}</span>
                </div>
              ))}
            </div>
            <div className="mt-4 space-y-1.5 border-t border-a-border pt-4 text-sm dark:border-a-dark-border">
              <div className="flex justify-between text-a-muted dark:text-a-dark-muted">
                <span>Subtotal</span>
                <span>{formatCurrency(viewing.subtotal)}</span>
              </div>
              {viewing.discount > 0 && (
                <div className="flex justify-between text-a-muted dark:text-a-dark-muted">
                  <span>Discount</span>
                  <span>-{formatCurrency(viewing.discount)}</span>
                </div>
              )}
              <div className="flex justify-between font-semibold text-a-text dark:text-a-dark-text">
                <span>Total</span>
                <span>{formatCurrency(viewing.total)}</span>
              </div>
              {viewing.amountPaid !== null && (
                <>
                  <div className="flex justify-between text-a-muted dark:text-a-dark-muted">
                    <span>Amount Paid</span>
                    <span>{formatCurrency(viewing.amountPaid)}</span>
                  </div>
                  <div className="flex justify-between font-semibold text-a-text dark:text-a-dark-text">
                    <span>Balance Due</span>
                    <span>{formatCurrency(viewing.balanceDue ?? 0)}</span>
                  </div>
                </>
              )}
            </div>
            <p className="mt-2 text-xs text-a-muted dark:text-a-dark-muted">Due {formatDate(viewing.dueDate)}</p>
          </div>
        )}
      </Modal>
    </div>
  );
}
