import type { ReactNode } from "react";
import { cn } from "../../../utils/cn";

type Tone = "neutral" | "success" | "warning" | "danger" | "info" | "accent";

const toneStyles: Record<Tone, string> = {
  neutral: "bg-a-surface-2 text-a-muted border-a-border dark:bg-a-dark-surface-2 dark:text-a-dark-muted dark:border-a-dark-border",
  success: "bg-emerald-500/10 text-emerald-600 border-emerald-500/25 dark:text-emerald-400",
  warning: "bg-amber-500/10 text-amber-600 border-amber-500/25 dark:text-amber-400",
  danger: "bg-rose-500/10 text-rose-600 border-rose-500/25 dark:text-rose-400",
  info: "bg-sky-500/10 text-sky-600 border-sky-500/25 dark:text-sky-400",
  accent: "bg-a-accent/10 text-a-accent-2 border-a-accent/25 dark:text-a-accent",
};

const statusToneMap: Record<string, Tone> = {
  Active: "success",
  Paid: "success",
  Excellent: "success",
  Completed: "success",
  Published: "success",
  "In Use": "success",
  Present: "success",

  Pending: "warning",
  "Expiring Soon": "warning",
  "Needs Maintenance": "warning",
  Upcoming: "warning",
  Draft: "neutral",
  Scheduled: "info",
  "In Progress": "info",
  "On Leave": "warning",

  Expired: "danger",
  Failed: "danger",
  Overdue: "danger",
  Suspended: "danger",
  Cancelled: "danger",
  "Out of Service": "danger",
  Inactive: "neutral",
  Unpaid: "danger",
  Retired: "neutral",
  Refunded: "info",
  Full: "danger",
};

export function StatusBadge({ status, className }: { status: string; className?: string }) {
  const tone = statusToneMap[status] ?? "neutral";
  return (
    <span
      className={cn(
        "inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium whitespace-nowrap",
        toneStyles[tone],
        className,
      )}
    >
      <span className="h-1.5 w-1.5 rounded-full bg-current" />
      {status}
    </span>
  );
}

export function Badge({ children, tone = "neutral", className }: { children: ReactNode; tone?: Tone; className?: string }) {
  return (
    <span className={cn("inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium", toneStyles[tone], className)}>
      {children}
    </span>
  );
}
