import type { ReactNode } from "react";
import { ArrowDown, ArrowUp, ArrowUpDown } from "lucide-react";
import { cn } from "../../../utils/cn";
import { EmptyState } from "./EmptyState";
import { ErrorState } from "./ErrorState";
import { LoadingState } from "./LoadingState";

export interface Column<T> {
  key: string;
  header: string;
  render: (row: T) => ReactNode;
  sortable?: boolean;
  className?: string;
}

interface DataTableProps<T> {
  columns: Column<T>[];
  rows: T[];
  rowKey: (row: T) => string;
  sortKey?: string;
  sortDir?: "asc" | "desc";
  onSort?: (key: string) => void;
  onRowClick?: (row: T) => void;
  emptyLabel?: string;
  loading?: boolean;
  error?: string | null;
  onRetry?: () => void;
}

export function DataTable<T>({
  columns,
  rows,
  rowKey,
  sortKey,
  sortDir,
  onSort,
  onRowClick,
  emptyLabel,
  loading,
  error,
  onRetry,
}: DataTableProps<T>) {
  if (loading) {
    return <LoadingState />;
  }

  if (error) {
    return <ErrorState message={error} onRetry={onRetry} />;
  }

  if (rows.length === 0) {
    return <EmptyState title="No results" description={emptyLabel ?? "Try adjusting your filters or search."} />;
  }

  return (
    <div className="overflow-x-auto rounded-2xl">
      <table className="w-full min-w-[720px] border-collapse text-sm">
        <thead>
          <tr className="border-b border-a-border dark:border-a-dark-border">
            {columns.map((col) => (
              <th
                key={col.key}
                onClick={() => col.sortable && onSort?.(col.key)}
                className={cn(
                  "whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-a-muted dark:text-a-dark-muted",
                  col.sortable && "cursor-pointer select-none hover:text-a-text dark:hover:text-a-dark-text",
                  col.className,
                )}
              >
                <span className="inline-flex items-center gap-1">
                  {col.header}
                  {col.sortable &&
                    (sortKey === col.key ? (
                      sortDir === "asc" ? <ArrowUp size={12} /> : <ArrowDown size={12} />
                    ) : (
                      <ArrowUpDown size={12} className="opacity-40" />
                    ))}
                </span>
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {rows.map((row) => (
            <tr
              key={rowKey(row)}
              onClick={() => onRowClick?.(row)}
              className={cn(
                "border-b border-a-border/60 transition-colors last:border-0 dark:border-a-dark-border/60",
                onRowClick && "cursor-pointer hover:bg-a-surface-2 dark:hover:bg-a-dark-surface-2",
              )}
            >
              {columns.map((col) => (
                <td key={col.key} className={cn("px-4 py-3 align-middle text-a-text dark:text-a-dark-text", col.className)}>
                  {col.render(row)}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
