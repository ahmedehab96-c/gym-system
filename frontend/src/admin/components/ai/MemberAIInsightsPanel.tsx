import { useState } from "react";
import { Sparkles, Lock } from "lucide-react";
import { Button } from "../ui/Button";
import { LoadingState } from "../ui/LoadingState";
import { ErrorState } from "../ui/ErrorState";
import { EmptyState } from "../ui/EmptyState";
import { aiService, type MemberInsight } from "../../services/aiService";
import { ApiError } from "../../services/apiClient";
import { formatDate } from "../../utils/format";

/**
 * Phase 22 §5 — generated on demand (never automatically) so viewing a
 * member's profile never silently spends the tenant's AI quota.
 */
export function MemberAIInsightsPanel({ memberId }: { memberId: string }) {
  const [insight, setInsight] = useState<MemberInsight | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [limitReached, setLimitReached] = useState(false);

  function generate() {
    setLoading(true);
    setError(null);
    setLimitReached(false);

    aiService
      .memberInsight(memberId)
      .then(setInsight)
      .catch((err) => {
        if (err instanceof ApiError && err.status === 402) setLimitReached(true);
        else setError(err instanceof ApiError ? err.message : "Something went wrong. Please try again.");
      })
      .finally(() => setLoading(false));
  }

  if (loading) return <LoadingState rows={3} />;

  if (limitReached) {
    return (
      <div className="flex items-center gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-600 dark:text-amber-400">
        <Lock size={15} className="shrink-0" />
        Your plan's AI usage limit has been reached for this billing period.
      </div>
    );
  }

  if (error) return <ErrorState message={error} onRetry={generate} />;

  if (!insight) {
    return (
      <EmptyState
        title="No AI insights generated yet"
        description="Generate attendance, membership, and engagement suggestions from this member's data."
        icon={<Sparkles size={20} />}
        action={<Button size="sm" onClick={generate}>Generate Insights</Button>}
      />
    );
  }

  return (
    <div className="space-y-4">
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <Stat label="Attendance Rate" value={`${insight.data.attendanceRate}%`} />
        <Stat label="Last 30 Days" value={`${insight.data.attendanceLast30Days} visits`} />
        <Stat label="Membership" value={insight.data.membership?.status ?? "None"} />
        <Stat label="Balance Due" value={String(insight.data.balanceDue)} />
      </div>

      <div>
        <p className="mb-1.5 text-xs font-semibold uppercase tracking-wide text-a-muted dark:text-a-dark-muted">Suggested Engagement Actions</p>
        <p className="rounded-xl bg-a-surface-2 p-4 text-sm leading-relaxed text-a-text dark:bg-a-dark-surface-2 dark:text-a-dark-text">
          {insight.suggestedActions}
        </p>
      </div>

      <div className="flex items-center justify-between">
        <p className="text-xs text-a-muted dark:text-a-dark-muted">Generated {formatDate(insight.generatedAt)}</p>
        <Button variant="secondary" size="sm" onClick={generate}>Regenerate</Button>
      </div>
    </div>
  );
}

function Stat({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-xl bg-a-surface-2 p-3 dark:bg-a-dark-surface-2">
      <p className="text-xs text-a-muted dark:text-a-dark-muted">{label}</p>
      <p className="mt-1 text-sm font-semibold text-a-text dark:text-a-dark-text">{value}</p>
    </div>
  );
}
