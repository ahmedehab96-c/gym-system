import { useState } from "react";
import { Link } from "react-router-dom";
import {
  Area, AreaChart, Bar, BarChart, CartesianGrid, Cell, Line, LineChart, Pie, PieChart,
  ResponsiveContainer, Tooltip, XAxis, YAxis,
} from "recharts";
import { Building2, CheckCircle2, Clock, Ban, CreditCard, XCircle, Wallet, TrendingUp } from "lucide-react";
import { PageHeader } from "../../admin/components/layout/PageHeader";
import { StatCard } from "../../admin/components/ui/StatCard";
import { ChartCard } from "../../admin/components/ui/ChartCard";
import { StatusBadge } from "../../admin/components/ui/Badge";
import { LoadingState } from "../../admin/components/ui/LoadingState";
import { ErrorState } from "../../admin/components/ui/ErrorState";
import { formatCurrency, formatDate, timeAgo } from "../../admin/utils/format";
import { useApiResource } from "../../admin/hooks/useApiResource";
import { platformDashboardService } from "../services/platformDashboardService";

const PLAN_COLORS = ["#5b8def", "#d4a72f", "#22c55e", "#a855f7", "#f97316", "#ef4444"];

export default function PlatformDashboard() {
  const [reloadToken, setReloadToken] = useState(0);

  const { data: summary, loading: summaryLoading, error: summaryError } = useApiResourceLike(
    (signal) => platformDashboardService.summary(signal),
    [reloadToken],
  );
  const { data: charts, loading: chartsLoading, error: chartsError } = useApiResourceLike(
    (signal) => platformDashboardService.charts(signal),
    [reloadToken],
  );
  const { data: activity, loading: activityLoading, error: activityError } = useApiResourceLike(
    (signal) => platformDashboardService.activity(signal),
    [reloadToken],
  );

  const loading = summaryLoading || chartsLoading || activityLoading;
  const error = summaryError ?? chartsError ?? activityError;

  return (
    <div>
      <PageHeader title="Platform Dashboard" description="Real-time metrics across every gym on the platform." />

      {loading ? (
        <LoadingState rows={8} />
      ) : error || !summary || !charts || !activity ? (
        <ErrorState message={error ?? undefined} onRetry={() => setReloadToken((t) => t + 1)} />
      ) : (
        <>
          <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
            <StatCard label="Total Gyms" value={String(summary.totalGyms)} icon={<Building2 size={18} />} />
            <StatCard label="Active Gyms" value={String(summary.activeGyms)} icon={<CheckCircle2 size={18} />} accent="from-emerald-400/20 to-emerald-500/10" />
            <StatCard label="Trial Gyms" value={String(summary.trialGyms)} icon={<Clock size={18} />} accent="from-sky-400/20 to-sky-500/10" />
            <StatCard label="Suspended Gyms" value={String(summary.suspendedGyms)} icon={<Ban size={18} />} accent="from-rose-400/20 to-rose-500/10" />
            <StatCard label="Active Subscriptions" value={String(summary.activeSubscriptions)} icon={<CreditCard size={18} />} accent="from-emerald-400/20 to-emerald-500/10" />
            <StatCard label="Expired Subscriptions" value={String(summary.expiredSubscriptions)} icon={<XCircle size={18} />} accent="from-rose-400/20 to-rose-500/10" />
            <StatCard label="Monthly Recurring Revenue" value={formatCurrency(summary.mrr)} icon={<Wallet size={18} />} accent="from-amber-400/20 to-amber-500/10" />
            <StatCard label="Yearly Revenue" value={formatCurrency(summary.yearlyRevenue)} icon={<TrendingUp size={18} />} accent="from-amber-400/20 to-amber-500/10" />
          </div>

          <div className="mt-5 grid grid-cols-1 gap-4 xl:grid-cols-3">
            <ChartCard title="Revenue Trend" subtitle="Paid SaaS invoices (12 months)" className="xl:col-span-2">
              <ResponsiveContainer width="100%" height={280}>
                <AreaChart data={charts.revenueTrend}>
                  <defs>
                    <linearGradient id="platform-rev" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stopColor="#5b8def" stopOpacity={0.35} />
                      <stop offset="100%" stopColor="#5b8def" stopOpacity={0} />
                    </linearGradient>
                  </defs>
                  <CartesianGrid strokeDasharray="3 3" stroke="var(--color-a-border)" vertical={false} />
                  <XAxis dataKey="month" tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} />
                  <YAxis tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} tickFormatter={(v) => `${v / 1000}k`} />
                  <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid var(--color-a-border)", fontSize: 12 }} />
                  <Area type="monotone" dataKey="revenue" stroke="#5b8def" fill="url(#platform-rev)" strokeWidth={2} name="Revenue" />
                </AreaChart>
              </ResponsiveContainer>
            </ChartCard>

            <ChartCard title="Plan Distribution" subtitle="Active subscriptions by plan">
              {charts.planDistribution.length === 0 ? (
                <p className="py-16 text-center text-xs text-a-muted dark:text-a-dark-muted">No active subscriptions yet.</p>
              ) : (
                <>
                  <ResponsiveContainer width="100%" height={220}>
                    <PieChart>
                      <Pie data={charts.planDistribution} dataKey="count" nameKey="plan" innerRadius={55} outerRadius={85} paddingAngle={3}>
                        {charts.planDistribution.map((e, i) => (
                          <Cell key={e.plan} fill={PLAN_COLORS[i % PLAN_COLORS.length]} />
                        ))}
                      </Pie>
                      <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid var(--color-a-border)", fontSize: 12 }} />
                    </PieChart>
                  </ResponsiveContainer>
                  <div className="mt-2 grid grid-cols-2 gap-2">
                    {charts.planDistribution.map((e, i) => (
                      <div key={e.plan} className="flex items-center gap-2 text-xs">
                        <span className="h-2 w-2 rounded-full" style={{ background: PLAN_COLORS[i % PLAN_COLORS.length] }} />
                        <span className="text-a-muted dark:text-a-dark-muted">{e.plan}</span>
                        <span className="ml-auto font-semibold text-a-text dark:text-a-dark-text">{e.count}</span>
                      </div>
                    ))}
                  </div>
                </>
              )}
            </ChartCard>
          </div>

          <div className="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
            <ChartCard title="Tenant Growth" subtitle="New gyms per month">
              <ResponsiveContainer width="100%" height={220}>
                <LineChart data={charts.tenantGrowth}>
                  <CartesianGrid strokeDasharray="3 3" stroke="var(--color-a-border)" vertical={false} />
                  <XAxis dataKey="month" tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} />
                  <YAxis tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} allowDecimals={false} />
                  <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid var(--color-a-border)", fontSize: 12 }} />
                  <Line type="monotone" dataKey="count" stroke="#5b8def" strokeWidth={2.5} dot={false} name="New gyms" />
                </LineChart>
              </ResponsiveContainer>
            </ChartCard>

            <ChartCard title="Subscription Growth" subtitle="New SaaS subscriptions per month">
              <ResponsiveContainer width="100%" height={220}>
                <BarChart data={charts.subscriptionGrowth}>
                  <CartesianGrid strokeDasharray="3 3" stroke="var(--color-a-border)" vertical={false} />
                  <XAxis dataKey="month" tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} />
                  <YAxis tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} allowDecimals={false} />
                  <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid var(--color-a-border)", fontSize: 12 }} />
                  <Bar dataKey="count" fill="#d4a72f" radius={[6, 6, 0, 0]} name="New subscriptions" />
                </BarChart>
              </ResponsiveContainer>
            </ChartCard>
          </div>

          <div className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
            <ChartCard title="Recent Gyms" action={<Link to="/platform/gyms" className="text-xs font-medium text-sky-600 dark:text-sky-400">View all</Link>}>
              {activity.recentGyms.length === 0 ? (
                <p className="text-xs text-a-muted dark:text-a-dark-muted">No gyms yet.</p>
              ) : (
                <div className="space-y-3">
                  {activity.recentGyms.map((gym) => (
                    <Link key={gym.id} to={`/platform/gyms/${gym.id}`} className="flex items-center gap-3 rounded-xl p-1.5 transition-colors hover:bg-a-surface-2 dark:hover:bg-a-dark-surface-2">
                      <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400">
                        <Building2 size={16} />
                      </div>
                      <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-medium text-a-text dark:text-a-dark-text">{gym.name}</p>
                        <p className="text-xs text-a-muted dark:text-a-dark-muted">Joined {formatDate(gym.createdAt)}</p>
                      </div>
                      <StatusBadge status={gym.status} />
                    </Link>
                  ))}
                </div>
              )}
            </ChartCard>

            <ChartCard title="Recent Subscriptions" action={<Link to="/platform/subscriptions" className="text-xs font-medium text-sky-600 dark:text-sky-400">View all</Link>}>
              {activity.recentSubscriptions.length === 0 ? (
                <p className="text-xs text-a-muted dark:text-a-dark-muted">No subscriptions yet.</p>
              ) : (
                <div className="space-y-3">
                  {activity.recentSubscriptions.map((sub) => (
                    <div key={sub.id} className="flex items-center gap-3">
                      <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-medium text-a-text dark:text-a-dark-text">{sub.tenant?.name ?? "—"}</p>
                        <p className="text-xs text-a-muted dark:text-a-dark-muted">{sub.plan?.name ?? "No plan"} · {sub.billingCycle}</p>
                      </div>
                      <StatusBadge status={sub.status} />
                    </div>
                  ))}
                </div>
              )}
            </ChartCard>

            <ChartCard title="Recent Billing Activity" action={<Link to="/platform/billing" className="text-xs font-medium text-sky-600 dark:text-sky-400">View all</Link>}>
              {activity.recentBilling.length === 0 ? (
                <p className="text-xs text-a-muted dark:text-a-dark-muted">No billing activity yet.</p>
              ) : (
                <div className="space-y-3">
                  {activity.recentBilling.map((invoice) => (
                    <div key={invoice.id} className="flex items-center gap-3">
                      <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-medium text-a-text dark:text-a-dark-text">{invoice.tenant?.name ?? "—"}</p>
                        <p className="text-xs text-a-muted dark:text-a-dark-muted">{invoice.invoiceNumber} · {formatDate(invoice.issueDate)}</p>
                      </div>
                      <div className="text-right">
                        <p className="text-sm font-semibold text-a-text dark:text-a-dark-text">{formatCurrency(invoice.amount)}</p>
                        <StatusBadge status={invoice.status} />
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </ChartCard>

            <ChartCard title="Recent Platform Activity" action={<Link to="/platform/audit-logs" className="text-xs font-medium text-sky-600 dark:text-sky-400">View all</Link>}>
              {activity.recentActivity.length === 0 ? (
                <p className="text-xs text-a-muted dark:text-a-dark-muted">No activity recorded yet.</p>
              ) : (
                <div className="space-y-3">
                  {activity.recentActivity.map((entry) => (
                    <div key={entry.id} className="flex items-start gap-3">
                      <div className="min-w-0 flex-1">
                        <p className="text-sm text-a-text dark:text-a-dark-text">{entry.description}</p>
                        <p className="text-xs text-a-muted dark:text-a-dark-muted">{entry.actorName} · {timeAgo(entry.createdAt)}</p>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </ChartCard>
          </div>
        </>
      )}
    </div>
  );
}

/** Same contract as useApiResource but for a plain-value fetcher (no `{data: {...}}` unwrap needed — the service already returns the value). */
function useApiResourceLike<T>(fetcher: (signal: AbortSignal) => Promise<T>, deps: unknown[]) {
  return useApiResource<T>((signal) => fetcher(signal).then((data) => ({ data })), deps);
}
