import type { ReactNode } from "react";
import { cn } from "../../../utils/cn";

interface ChartCardProps {
  title: string;
  subtitle?: string;
  action?: ReactNode;
  children: ReactNode;
  className?: string;
}

export function ChartCard({ title, subtitle, action, children, className }: ChartCardProps) {
  return (
    <div className={cn("admin-card rounded-2xl p-5 shadow-sm", className)}>
      <div className="mb-4 flex items-center justify-between gap-3">
        <div>
          <h3 className="text-sm font-semibold text-a-text dark:text-a-dark-text">{title}</h3>
          {subtitle && <p className="text-xs text-a-muted dark:text-a-dark-muted">{subtitle}</p>}
        </div>
        {action}
      </div>
      {children}
    </div>
  );
}
