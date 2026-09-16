import type { ReactNode } from "react";
import { Breadcrumb } from "../ui/Breadcrumb";

interface PageHeaderProps {
  title: string;
  description?: string;
  breadcrumb?: { label: string; to?: string }[];
  action?: ReactNode;
}

export function PageHeader({ title, description, breadcrumb, action }: PageHeaderProps) {
  return (
    <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        {breadcrumb && <div className="mb-2"><Breadcrumb items={breadcrumb} /></div>}
        <h1 className="text-2xl font-bold tracking-tight text-a-text dark:text-a-dark-text">{title}</h1>
        {description && <p className="mt-1 text-sm text-a-muted dark:text-a-dark-muted">{description}</p>}
      </div>
      {action && <div className="flex items-center gap-2">{action}</div>}
    </div>
  );
}
