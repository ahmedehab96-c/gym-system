import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { PageHeader } from "../../admin/components/layout/PageHeader";
import { SearchBar } from "../../admin/components/ui/SearchBar";
import { DataTable, type Column } from "../../admin/components/ui/DataTable";
import { Pagination } from "../../admin/components/ui/Pagination";
import { Badge } from "../../admin/components/ui/Badge";
import { useApiList } from "../../admin/hooks/useApiList";
import { useDebouncedValue } from "../../admin/hooks/useDebouncedValue";
import { formatDateTime } from "../../admin/utils/format";
import { platformAuditLogService } from "../services/platformAuditLogService";
import type { AuditLogEntry } from "../types";

const PER_PAGE = 20;

export default function AuditLogs() {
  const [query, setQuery] = useState("");
  const [page, setPage] = useState(1);
  const debouncedQuery = useDebouncedValue(query);

  useEffect(() => setPage(1), [debouncedQuery]);

  const { data: logs, meta, loading, error, refetch } = useApiList(
    (signal) => platformAuditLogService.list({ search: debouncedQuery || undefined, page, perPage: PER_PAGE }, signal),
    [debouncedQuery, page],
  );

  const columns: Column<AuditLogEntry>[] = [
    { key: "actorName", header: "Actor", render: (l) => <span className="font-medium text-a-text dark:text-a-dark-text">{l.actorName}</span> },
    { key: "action", header: "Action", render: (l) => <Badge tone="accent">{l.action}</Badge> },
    { key: "description", header: "Description", render: (l) => <span className="text-a-muted dark:text-a-dark-muted">{l.description}</span> },
    { key: "tenant", header: "Gym", render: (l) => l.tenant ? (
      <Link to={`/platform/gyms/${l.tenant.id}`} className="text-a-text hover:text-sky-600 dark:text-a-dark-text dark:hover:text-sky-400">{l.tenant.name}</Link>
    ) : <span className="text-a-muted dark:text-a-dark-muted">—</span> },
    { key: "createdAt", header: "Date/Time", render: (l) => formatDateTime(l.createdAt) },
  ];

  return (
    <div>
      <PageHeader title="Audit Logs" description={meta ? `${meta.total} recorded actions` : "Loading audit log…"} />

      <div className="mb-4">
        <SearchBar value={query} onChange={setQuery} placeholder="Search by actor, action, or description..." className="sm:max-w-sm" />
      </div>

      <div className="admin-card rounded-2xl p-4 shadow-sm">
        <DataTable columns={columns} rows={logs} rowKey={(l) => l.id} loading={loading} error={error} onRetry={refetch} />
        {meta && meta.totalPages > 1 && (
          <Pagination page={meta.page} totalPages={meta.totalPages} onChange={setPage} totalItems={meta.total} pageSize={PER_PAGE} />
        )}
      </div>
    </div>
  );
}
