import type { ReactNode } from "react";
import { cn } from "../../utils/cn";

export function Badge({ children, className }: { children: ReactNode; className?: string }) {
  return (
    <span
      className={cn(
        "inline-flex items-center gap-2 rounded-full border border-gold-400/30 bg-gold-400/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-gold-300",
        className,
      )}
    >
      <span className="h-1.5 w-1.5 rounded-full bg-gold-400" />
      {children}
    </span>
  );
}
