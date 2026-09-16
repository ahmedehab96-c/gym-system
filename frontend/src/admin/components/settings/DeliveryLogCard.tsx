import { useEffect, useState } from "react";
import { ChartCard } from "../ui/ChartCard";
import { DataTable, type Column } from "../ui/DataTable";
import { FilterDropdown } from "../ui/FilterDropdown";
import { Pagination } from "../ui/Pagination";
import { StatusBadge } from "../ui/Badge";
import { useApiList } from "../../hooks/useApiList";
import { communicationService, type DeliveryStatus, type NotificationDelivery } from "../../services/communicationService";
import { formatDateTime } from "../../utils/format";

const PER_PAGE = 10;
const statuses: DeliveryStatus[] = ["Sent", "Failed", "Pending"];

/** Read-only external-channel (email/WhatsApp/push) delivery history and failures (Phase 24 §8). */
export function DeliveryLogCard() {
  const [status, setStatus] = useState("all");
  const [page, setPage] = useState(1);

  useEffect(() => setPage(1), [status]);

  const { data: deliveries, meta, loading, error, refetch } = useApiList<NotificationDelivery>(
    (signal) =>
      communicationService.listDeliveries(
        { status: status === "all" ? undefined : (status as DeliveryStatus), page, perPage: PER_PAGE },
        signal,
      ),
    [status, page],
  );

  const columns: Column<NotificationDelivery>[] = [
    { key: "type", header: "Event", render: (d) => d.type },
    { key: "channel", header: "Channel", render: (d) => <span className="capitalize">{d.channel}</span> },
    { key: "recipient", header: "Recipient", render: (d) => <span className="font-mono text-xs">{d.recipient}</span> },
    { key: "status", header: "Status", render: (d) => <StatusBadge status={d.status} /> },
    { key: "error", header: "Error", render: (d) => (d.error ? <span className="text-xs text-rose-500">{d.error}</span> : "—") },
    { key: "createdAt", header: "When", render: (d) => formatDateTime(d.createdAt) },
  ];

  return (
    <ChartCard title="Delivery Log" subtitle="Email, WhatsApp, and push delivery history">
      <div className="mb-3 flex justify-end">
        <FilterDropdown label="Status" value={status} onChange={setStatus} options={statuses.map((s) => ({ label: s, value: s }))} />
      </div>
      <DataTable columns={columns} rows={deliveries} rowKey={(d) => d.id} loading={loading} error={error} onRetry={refetch} />
      {meta && meta.totalPages > 1 && (
        <Pagination page={meta.page} totalPages={meta.totalPages} onChange={setPage} totalItems={meta.total} pageSize={PER_PAGE} />
      )}
    </ChartCard>
  );
}
