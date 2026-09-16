import { useEffect, useState } from "react";
import { Sparkles } from "lucide-react";
import { aiService, type AIUsageStats } from "../../services/aiService";
import { cn } from "../../../utils/cn";

/**
 * The "AI usage indicator" required by Phase 22 §10 — reused wherever an
 * AI feature is surfaced. Always real data from GET /ai/usage, never a
 * placeholder number.
 */
export function AIUsageIndicator({ className }: { className?: string }) {
  const [usage, setUsage] = useState<AIUsageStats | null>(null);

  useEffect(() => {
    const controller = new AbortController();
    aiService.usage(controller.signal).then(setUsage).catch(() => undefined);
    return () => controller.abort();
  }, []);

  if (!usage) return null;

  const atLimit = usage.limit !== null && usage.remaining === 0;

  return (
    <div
      className={cn(
        "inline-flex items-center gap-2 rounded-xl border px-3 py-1.5 text-xs font-medium",
        atLimit
          ? "border-rose-500/30 bg-rose-500/10 text-rose-500"
          : "border-a-border bg-a-surface-2 text-a-muted dark:border-a-dark-border dark:bg-a-dark-surface-2 dark:text-a-dark-muted",
        className,
      )}
    >
      <Sparkles size={13} />
      {usage.limit === null ? (
        <span>{usage.used} AI requests used this month · Unlimited</span>
      ) : (
        <span>{usage.used} / {usage.limit} AI requests used this month</span>
      )}
    </div>
  );
}
