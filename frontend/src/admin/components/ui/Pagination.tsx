import { ChevronLeft, ChevronRight } from "lucide-react";
import { cn } from "../../../utils/cn";

interface PaginationProps {
  page: number;
  totalPages: number;
  onChange: (page: number) => void;
  totalItems?: number;
  pageSize?: number;
}

export function Pagination({ page, totalPages, onChange, totalItems, pageSize }: PaginationProps) {
  const pages = Array.from({ length: totalPages }, (_, i) => i + 1).filter(
    (p) => p === 1 || p === totalPages || Math.abs(p - page) <= 1,
  );

  return (
    <div className="flex flex-wrap items-center justify-between gap-3 pt-4">
      {totalItems !== undefined && pageSize !== undefined && (
        <p className="text-xs text-a-muted dark:text-a-dark-muted">
          Showing {Math.min((page - 1) * pageSize + 1, totalItems)}-{Math.min(page * pageSize, totalItems)} of {totalItems}
        </p>
      )}
      <div className="flex items-center gap-1">
        <button
          disabled={page === 1}
          onClick={() => onChange(page - 1)}
          className="flex h-8 w-8 items-center justify-center rounded-lg text-a-muted transition-colors hover:bg-a-surface-2 disabled:opacity-30 dark:text-a-dark-muted dark:hover:bg-a-dark-surface-2"
        >
          <ChevronLeft size={16} />
        </button>
        {pages.map((p, i) => (
          <span key={p} className="flex items-center">
            {i > 0 && pages[i - 1] !== p - 1 && <span className="px-1 text-a-muted dark:text-a-dark-muted">…</span>}
            <button
              onClick={() => onChange(p)}
              className={cn(
                "flex h-8 w-8 items-center justify-center rounded-lg text-xs font-medium transition-colors",
                p === page
                  ? "bg-a-accent text-black"
                  : "text-a-muted hover:bg-a-surface-2 dark:text-a-dark-muted dark:hover:bg-a-dark-surface-2",
              )}
            >
              {p}
            </button>
          </span>
        ))}
        <button
          disabled={page === totalPages}
          onClick={() => onChange(page + 1)}
          className="flex h-8 w-8 items-center justify-center rounded-lg text-a-muted transition-colors hover:bg-a-surface-2 disabled:opacity-30 dark:text-a-dark-muted dark:hover:bg-a-dark-surface-2"
        >
          <ChevronRight size={16} />
        </button>
      </div>
    </div>
  );
}
