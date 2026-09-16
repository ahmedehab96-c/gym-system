import type { SelectHTMLAttributes } from "react";
import { ChevronDown } from "lucide-react";
import { cn } from "../../../utils/cn";

interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
  label?: string;
  options: { label: string; value: string }[];
  /** Laravel 422 field-level validation message, shown below the select. */
  error?: string;
}

export function Select({ label, options, error, className, ...rest }: SelectProps) {
  return (
    <label className="flex flex-col gap-1.5">
      {label && <span className="text-xs font-medium text-a-muted dark:text-a-dark-muted">{label}</span>}
      <div className="relative">
        <select
          aria-invalid={Boolean(error)}
          className={cn(
            "w-full appearance-none rounded-xl border border-a-border bg-a-surface px-3.5 py-2.5 pr-9 text-sm text-a-text outline-none transition-colors focus:border-a-accent",
            "dark:border-a-dark-border dark:bg-a-dark-surface-2 dark:text-a-dark-text",
            error && "border-rose-500 focus:border-rose-500 dark:border-rose-500",
            className,
          )}
          {...rest}
        >
          {options.map((o) => (
            <option key={o.value} value={o.value}>
              {o.label}
            </option>
          ))}
        </select>
        <ChevronDown size={16} className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-a-muted dark:text-a-dark-muted" />
      </div>
      {error && <span className="text-xs text-rose-500">{error}</span>}
    </label>
  );
}
