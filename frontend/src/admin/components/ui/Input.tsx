import type { InputHTMLAttributes, ReactNode } from "react";
import { cn } from "../../../utils/cn";

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  icon?: ReactNode;
  rightElement?: ReactNode;
  label?: string;
  /** Laravel 422 field-level validation message, shown below the input. */
  error?: string;
}

export function Input({ icon, rightElement, label, error, className, id, ...rest }: InputProps) {
  return (
    <label className="flex flex-col gap-1.5">
      {label && <span className="text-xs font-medium text-a-muted dark:text-a-dark-muted">{label}</span>}
      <div className="relative flex items-center">
        {icon && <span className="pointer-events-none absolute left-3 text-a-muted dark:text-a-dark-muted">{icon}</span>}
        <input
          id={id}
          aria-invalid={Boolean(error)}
          className={cn(
            "w-full rounded-xl border border-a-border bg-a-surface px-3.5 py-2.5 text-sm text-a-text outline-none transition-colors placeholder:text-a-muted focus:border-a-accent",
            "dark:border-a-dark-border dark:bg-a-dark-surface-2 dark:text-a-dark-text dark:placeholder:text-a-dark-muted",
            Boolean(icon) && "pl-9",
            Boolean(rightElement) && "pr-9",
            error && "border-rose-500 focus:border-rose-500 dark:border-rose-500",
            className,
          )}
          {...rest}
        />
        {rightElement && <span className="absolute right-3 flex items-center text-a-muted dark:text-a-dark-muted">{rightElement}</span>}
      </div>
      {error && <span className="text-xs text-rose-500">{error}</span>}
    </label>
  );
}
