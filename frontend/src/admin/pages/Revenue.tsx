import { useEffect, useState } from "react";
import { Area, AreaChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from "recharts";
import { TrendingUp, Wallet, PiggyBank, Percent } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { StatCard } from "../components/ui/StatCard";
import { ChartCard } from "../components/ui/ChartCard";
import { LoadingState } from "../components/ui/LoadingState";
import { ErrorState } from "../components/ui/ErrorState";
import { financeService, type FinanceOverview, type RevenueMonth } from "../services/financeService";
import { formatCurrency } from "../utils/format";
import { ApiError } from "../services/apiClient";

export default function Revenue() {
  const [overview, setOverview] = useState<FinanceOverview | null>(null);
  const [trend, setTrend] = useState<RevenueMonth[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  function load() {
    setLoading(true);
    setError(null);
    Promise.all([financeService.overview(), financeService.revenueOverTime(12)])
      .then(([overviewRes, trendRes]) => {
        setOverview(overviewRes);
        setTrend(trendRes);
      })
      .catch((err) => setError(err instanceof ApiError ? err.message : "Something went wrong. Please try again."))
      .finally(() => setLoading(false));
  }

  useEffect(load, []);

  if (loading) return <div><PageHeader title="Revenue" description="Full financial performance overview" /><LoadingState rows={6} /></div>;
  if (error || !overview) return <div><PageHeader title="Revenue" description="Full financial performance overview" /><ErrorState message={error ?? undefined} onRetry={load} /></div>;

  const margin = overview.totalRevenue > 0 ? ((overview.netRevenue / overview.totalRevenue) * 100).toFixed(1) : "0.0";

  return (
    <div>
      <PageHeader title="Revenue" description="Full financial performance overview" />

      <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
        <StatCard label="Total Revenue (YTD)" value={formatCurrency(overview.totalRevenue)} icon={<Wallet size={18} />} accent="from-emerald-400/20 to-emerald-500/10" />
        <StatCard label="Total Expenses" value={formatCurrency(overview.totalExpenses)} icon={<TrendingUp size={18} />} accent="from-rose-400/20 to-rose-500/10" />
        <StatCard label="Net Profit" value={formatCurrency(overview.netRevenue)} icon={<PiggyBank size={18} />} />
        <StatCard label="Profit Margin" value={`${margin}%`} icon={<Percent size={18} />} />
      </div>

      <div className="mt-5">
        <ChartCard title="Revenue vs. Expenses" subtitle="12-month trend">
          <ResponsiveContainer width="100%" height={320}>
            <AreaChart data={trend}>
              <defs>
                <linearGradient id="rev2" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stopColor="#22c55e" stopOpacity={0.3} />
                  <stop offset="100%" stopColor="#22c55e" stopOpacity={0} />
                </linearGradient>
                <linearGradient id="exp2" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stopColor="#e0263c" stopOpacity={0.25} />
                  <stop offset="100%" stopColor="#e0263c" stopOpacity={0} />
                </linearGradient>
              </defs>
              <CartesianGrid strokeDasharray="3 3" stroke="var(--color-a-border)" vertical={false} />
              <XAxis dataKey="month" tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} />
              <YAxis tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} tickFormatter={(v) => `${v / 1000}k`} />
              <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid var(--color-a-border)", fontSize: 12 }} />
              <Area type="monotone" dataKey="revenue" stroke="#22c55e" fill="url(#rev2)" strokeWidth={2} name="Revenue" />
              <Area type="monotone" dataKey="expenses" stroke="#e0263c" fill="url(#exp2)" strokeWidth={2} name="Expenses" />
            </AreaChart>
          </ResponsiveContainer>
        </ChartCard>
      </div>
    </div>
  );
}
