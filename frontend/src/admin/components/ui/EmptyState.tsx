import type { ReactNode } from "react";
import { Inbox } from "lucide-react";

interface EmptyStateProps {
  title: string;
  description?: string;
  icon?: ReactNode;
  action?: ReactNode;
}

export function EmptyState({ title, description, icon, action }: EmptyStateProps) {
  return (
    <div className="flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-a-border px-6 py-14 text-center dark:border-a-dark-border">
      <div className="flex h-12 w-12 items-center justify-center rounded-full bg-a-surface-2 text-a-muted dark:bg-a-dark-surface-2 dark:text-a-dark-muted">
        {icon ?? <Inbox size={20} />}
      </div>
      <div>
        <p className="text-sm font-medium text-a-text dark:text-a-dark-text">{title}</p>
        {description && <p className="mt-1 text-xs text-a-muted dark:text-a-dark-muted">{description}</p>}
      </div>
      {action}
    </div>
  );
}
