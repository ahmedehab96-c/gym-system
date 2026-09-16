import { useEffect, useState } from "react";
import { RotateCcw, Lock } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { Tabs } from "../components/ui/Tabs";
import { Button } from "../components/ui/Button";
import { LoadingState } from "../components/ui/LoadingState";
import { ErrorState } from "../components/ui/ErrorState";
import { AIUsageIndicator } from "../components/ai/AIUsageIndicator";
import { aiService, INSIGHT_DOMAINS, type Insight, type InsightDomain } from "../services/aiService";
import { ApiError } from "../services/apiClient";
import { formatDateTime } from "../utils/format";

const DOMAIN_LABELS: Record<InsightDomain, string> = {
  members: "Members",
  attendance: "Attendance",
  revenue: "Revenue",
  classes: "Classes",
  equipment: "Equipment",
};

export default function AIInsights() {
  const [domain, setDomain] = useState<InsightDomain>("members");
  const [cache, setCache] = useState<Partial<Record<InsightDomain, Insight>>>({});
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [limitReached, setLimitReached] = useState(false);

  function load(target: InsightDomain, force = false) {
    if (!force && cache[target]) return;

    setLoading(true);
    setError(null);
    setLimitReached(false);

    aiService
      .insight(target)
      .then((result) => setCache((c) => ({ ...c, [target]: result })))
      .catch((err) => {
        if (err instanceof ApiError && err.status === 402) setLimitReached(true);
        else setError(err instanceof ApiError ? err.message : "Something went wrong. Please try again.");
      })
      .finally(() => setLoading(false));
  }

  useEffect(() => {
    load(domain);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [domain]);

  const current = cache[domain];

  return (
    <div>
      <PageHeader
        title="AI Insights"
        description="AI-generated observations from your gym's real data, by area."
        action={<AIUsageIndicator />}
      />

      <Tabs
        tabs={INSIGHT_DOMAINS.map((d) => DOMAIN_LABELS[d])}
        active={DOMAIN_LABELS[domain]}
        onChange={(label) => setDomain((Object.keys(DOMAIN_LABELS) as InsightDomain[]).find((d) => DOMAIN_LABELS[d] === label) ?? "members")}
        className="mb-4 w-fit"
      />

      {loading ? (
        <LoadingState rows={4} />
      ) : limitReached ? (
        <div className="flex items-center gap-2 rounded-2xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-600 dark:text-amber-400">
          <Lock size={15} className="shrink-0" />
          Your plan's AI usage limit has been reached for this billing period. Upgrade your subscription to generate more insights.
        </div>
      ) : error ? (
        <ErrorState message={error} onRetry={() => load(domain, true)} />
      ) : !current ? (
        <ErrorState message="No insight generated yet." onRetry={() => load(domain, true)} />
      ) : (
        <div className="admin-card rounded-2xl p-6 shadow-sm">
          <div className="mb-4 flex items-start justify-between gap-3">
            <div>
              <h3 className="text-sm font-semibold text-a-text dark:text-a-dark-text">{DOMAIN_LABELS[domain]} Summary</h3>
              <p className="mt-0.5 text-xs text-a-muted dark:text-a-dark-muted">Generated {formatDateTime(current.generatedAt)}</p>
            </div>
            <Button variant="secondary" size="sm" icon={<RotateCcw size={13} />} onClick={() => load(domain, true)}>
              Regenerate
            </Button>
          </div>

          <p className="rounded-xl bg-a-surface-2 p-4 text-sm leading-relaxed text-a-text dark:bg-a-dark-surface-2 dark:text-a-dark-text">
            {current.summary}
          </p>

          <details className="mt-4">
            <summary className="cursor-pointer text-xs font-medium text-a-muted hover:text-a-text dark:text-a-dark-muted dark:hover:text-a-dark-text">
              View underlying data
            </summary>
            <pre className="mt-2 max-h-80 overflow-auto rounded-xl bg-a-surface-2 p-4 text-xs text-a-muted dark:bg-a-dark-surface-2 dark:text-a-dark-muted">
              {JSON.stringify(current.metrics, null, 2)}
            </pre>
          </details>
        </div>
      )}
    </div>
  );
}
