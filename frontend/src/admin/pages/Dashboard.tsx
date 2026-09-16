import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import {
  Area, AreaChart, Bar, BarChart, CartesianGrid, Cell, Line, LineChart, Pie, PieChart,
  ResponsiveContainer, Tooltip, XAxis, YAxis,
} from "recharts";
import {
  Users, UserCheck, UserPlus, UserX, CalendarCheck2, Wallet, Clock, Dumbbell,
} from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { StatCard } from "../components/ui/StatCard";
import { ChartCard } from "../components/ui/ChartCard";
import { Tabs } from "../components/ui/Tabs";
import { Avatar } from "../components/ui/Avatar";
import { StatusBadge } from "../components/ui/Badge";
import { LoadingState } from "../components/ui/LoadingState";
import { ErrorState } from "../components/ui/ErrorState";
import { dashboardService, type DashboardSummary } from "../services/dashboardService";
import { financeService, type RevenueMonth } from "../services/financeService";
import { analyticsService, type MemberGrowthPoint, type MembershipDistributionSlice } from "../services/analyticsService";
import { attendanceService, type AttendanceStats } from "../services/attendanceService";
import { ApiError } from "../services/apiClient";
import { formatCurrency, formatDate } from "../utils/format";

interface DashboardData {
  summary: DashboardSummary;
  revenue: RevenueMonth[];
  distribution: MembershipDistributionSlice[];
  growth: MemberGrowthPoint[];
  attendance: AttendanceStats;
}

export default function Dashboard() {
  const [range, setRange] = useState("This Month");
  const [data, setData] = useState<DashboardData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [reloadToken, setReloadToken] = useState(0);

  useEffect(() => {
    const controller = new AbortController();
    setLoading(true);
    setError(null);

    Promise.all([
      dashboardService.summary(controller.signal),
      financeService.revenueOverTime(12, controller.signal),
      analyticsService.membershipDistribution(controller.signal),
      analyticsService.memberGrowth(8, controller.signal),
      attendanceService.stats(controller.signal),
    ])
      .then(([summary, revenue, distribution, growth, attendance]) => {
        setData({ summary, revenue, distribution, growth, attendance });
      })
      .catch((err) => {
        if (controller.signal.aborted) return;
        setError(err instanceof ApiError ? err.message : "Something went wrong. Please try again.");
      })
      .finally(() => {
        if (!controller.signal.aborted) setLoading(false);
      });

    return () => controller.abort();
  }, [reloadToken]);

  return (
    <div>
      <PageHeader
        title="Dashboard"
        description="Welcome back — here's what's happening at your gym today."
        action={<Tabs tabs={["Today", "This Week", "This Month", "This Year"]} active={range === "This Month" ? "This Month" : range} onChange={setRange} />}
      />

      {loading ? (
        <LoadingState rows={8} />
      ) : error || !data ? (
        <ErrorState message={error ?? undefined} onRetry={() => setReloadToken((t) => t + 1)} />
      ) : (
        <DashboardContent data={data} />
      )}
    </div>
  );
}

function DashboardContent({ data }: { data: DashboardData }) {
  const { summary, revenue, distribution, growth, attendance } = data;
  const { stats } = summary;

  return (
    <>
      <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
        <StatCard label="Total Members" value={String(stats.totalMembers)} icon={<Users size={18} />} />
        <StatCard label="Active Members" value={String(stats.activeMembers)} icon={<UserCheck size={18} />} accent="from-emerald-400/20 to-emerald-500/10" />
        <StatCard label="New Members" value={String(stats.newMembers)} icon={<UserPlus size={18} />} accent="from-sky-400/20 to-sky-500/10" />
        <StatCard label="Expired Memberships" value={String(stats.expiredMemberships)} icon={<UserX size={18} />} accent="from-rose-400/20 to-rose-500/10" />
        <StatCard label="Today's Attendance" value={String(stats.todayAttendance)} icon={<CalendarCheck2 size={18} />} />
        <StatCard label="Monthly Revenue" value={formatCurrency(stats.monthlyRevenue)} icon={<Wallet size={18} />} accent="from-emerald-400/20 to-emerald-500/10" />
        <StatCard label="Pending Payments" value={String(stats.pendingPayments)} icon={<Clock size={18} />} accent="from-amber-400/20 to-amber-500/10" />
        <StatCard label="Active Trainers" value={String(stats.activeTrainers)} icon={<Dumbbell size={18} />} />
      </div>

      <div className="mt-5 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <ChartCard title="Revenue Overview" subtitle="Revenue vs. expenses (12 months)" className="xl:col-span-2">
          <ResponsiveContainer width="100%" height={280}>
            <AreaChart data={revenue}>
              <defs>
                <linearGradient id="rev" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stopColor="#d4a72f" stopOpacity={0.35} />
                  <stop offset="100%" stopColor="#d4a72f" stopOpacity={0} />
                </linearGradient>
              </defs>
              <CartesianGrid strokeDasharray="3 3" stroke="var(--color-a-border)" vertical={false} />
              <XAxis dataKey="month" tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} />
              <YAxis tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} tickFormatter={(v) => `${v / 1000}k`} />
              <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid var(--color-a-border)", fontSize: 12 }} />
              <Area type="monotone" dataKey="revenue" stroke="#d4a72f" fill="url(#rev)" strokeWidth={2} name="Revenue" />
              <Area type="monotone" dataKey="expenses" stroke="#6b7280" fill="transparent" strokeWidth={2} strokeDasharray="4 4" name="Expenses" />
            </AreaChart>
          </ResponsiveContainer>
        </ChartCard>

        <ChartCard title="Membership Expiration" subtitle="By current status">
          <ResponsiveContainer width="100%" height={280}>
            <PieChart>
              <Pie data={distribution} dataKey="value" nameKey="name" innerRadius={55} outerRadius={85} paddingAngle={3}>
                {distribution.map((e) => (
                  <Cell key={e.name} fill={e.color} />
                ))}
              </Pie>
              <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid var(--color-a-border)", fontSize: 12 }} />
            </PieChart>
          </ResponsiveContainer>
          <div className="mt-2 grid grid-cols-2 gap-2">
            {distribution.map((e) => (
              <div key={e.name} className="flex items-center gap-2 text-xs">
                <span className="h-2 w-2 rounded-full" style={{ background: e.color }} />
                <span className="text-a-muted dark:text-a-dark-muted">{e.name}</span>
                <span className="ml-auto font-semibold text-a-text dark:text-a-dark-text">{e.value}</span>
              </div>
            ))}
          </div>
        </ChartCard>
      </div>

      <div className="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <ChartCard title="Membership Growth" subtitle="Total members over time">
          <ResponsiveContainer width="100%" height={220}>
            <LineChart data={growth}>
              <CartesianGrid strokeDasharray="3 3" stroke="var(--color-a-border)" vertical={false} />
              <XAxis dataKey="month" tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} />
              <YAxis tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} />
              <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid var(--color-a-border)", fontSize: 12 }} />
              <Line type="monotone" dataKey="members" stroke="#5b8def" strokeWidth={2.5} dot={false} />
            </LineChart>
          </ResponsiveContainer>
        </ChartCard>

        <ChartCard title="Attendance Analytics" subtitle="This week's visits">
          <ResponsiveContainer width="100%" height={220}>
            <BarChart data={attendance.weekly}>
              <CartesianGrid strokeDasharray="3 3" stroke="var(--color-a-border)" vertical={false} />
              <XAxis dataKey="day" tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} />
              <YAxis tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} />
              <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid var(--color-a-border)", fontSize: 12 }} />
              <Bar dataKey="visits" fill="#d4a72f" radius={[6, 6, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </ChartCard>

        <ChartCard title="Monthly Attendance" subtitle="Visits trend">
          <ResponsiveContainer width="100%" height={220}>
            <AreaChart data={attendance.monthly}>
              <defs>
                <linearGradient id="att" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stopColor="#22c55e" stopOpacity={0.3} />
                  <stop offset="100%" stopColor="#22c55e" stopOpacity={0} />
                </linearGradient>
              </defs>
              <CartesianGrid strokeDasharray="3 3" stroke="var(--color-a-border)" vertical={false} />
              <XAxis dataKey="month" tick={{ fontSize: 10, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} />
              <YAxis hide />
              <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid var(--color-a-border)", fontSize: 12 }} />
              <Area type="monotone" dataKey="visits" stroke="#22c55e" fill="url(#att)" strokeWidth={2} />
            </AreaChart>
          </ResponsiveContainer>
        </ChartCard>
      </div>

      <div className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <ChartCard title="Recent Members" action={<Link to="/admin/members" className="text-xs font-medium text-a-accent-2 dark:text-a-accent">View all</Link>}>
          {summary.recentMembers.length === 0 ? (
            <p className="text-xs text-a-muted dark:text-a-dark-muted">No members yet.</p>
          ) : (
            <div className="space-y-3">
              {summary.recentMembers.map((m) => (
                <Link key={m.id} to={`/admin/members/${m.id}`} className="flex items-center gap-3 rounded-xl p-1.5 transition-colors hover:bg-a-surface-2 dark:hover:bg-a-dark-surface-2">
                  <Avatar src={m.avatar} name={m.name} size="sm" />
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-medium text-a-text dark:text-a-dark-text">{m.name}</p>
                    <p className="text-xs text-a-muted dark:text-a-dark-muted">{m.planName} · Joined {formatDate(m.joinDate)}</p>
                  </div>
                  <StatusBadge status={m.status} />
                </Link>
              ))}
            </div>
          )}
        </ChartCard>

        <ChartCard title="Recent Payments" action={<Link to="/admin/payments" className="text-xs font-medium text-a-accent-2 dark:text-a-accent">View all</Link>}>
          {summary.recentPayments.length === 0 ? (
            <p className="text-xs text-a-muted dark:text-a-dark-muted">No payments yet.</p>
          ) : (
            <div className="space-y-3">
              {summary.recentPayments.map((p) => (
                <div key={p.id} className="flex items-center gap-3">
                  <Avatar src={p.memberAvatar} name={p.memberName} size="sm" />
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-medium text-a-text dark:text-a-dark-text">{p.memberName}</p>
                    <p className="text-xs text-a-muted dark:text-a-dark-muted">{p.method} · {formatDate(p.date)}</p>
                  </div>
                  <div className="text-right">
                    <p className="text-sm font-semibold text-a-text dark:text-a-dark-text">{formatCurrency(p.amount)}</p>
                    <StatusBadge status={p.status} />
                  </div>
                </div>
              ))}
            </div>
          )}
        </ChartCard>

        <ChartCard title="Upcoming Classes" action={<Link to="/admin/schedule" className="text-xs font-medium text-a-accent-2 dark:text-a-accent">View schedule</Link>}>
          {summary.upcomingClasses.length === 0 ? (
            <p className="text-xs text-a-muted dark:text-a-dark-muted">No upcoming classes.</p>
          ) : (
            <div className="space-y-3">
              {summary.upcomingClasses.map((c) => (
                <div key={c.id} className="flex items-center gap-3">
                  <div className="flex h-10 w-10 shrink-0 flex-col items-center justify-center rounded-xl text-xs font-bold" style={{ background: `${c.color}20`, color: c.color }}>
                    {c.day}
                  </div>
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-medium text-a-text dark:text-a-dark-text">{c.name}</p>
                    <p className="text-xs text-a-muted dark:text-a-dark-muted">{c.startTime} · {c.trainerName}</p>
                  </div>
                  <p className="text-xs font-medium text-a-muted dark:text-a-dark-muted">{c.booked}/{c.capacity}</p>
                </div>
              ))}
            </div>
          )}
        </ChartCard>

        <ChartCard title="Memberships Expiring Soon" action={<Link to="/admin/memberships" className="text-xs font-medium text-a-accent-2 dark:text-a-accent">View all</Link>}>
          {summary.expiringMemberships.length === 0 ? (
            <p className="text-xs text-a-muted dark:text-a-dark-muted">No memberships expiring soon.</p>
          ) : (
            <div className="space-y-3">
              {summary.expiringMemberships.map((m) => (
                <div key={m.id} className="flex items-center gap-3">
                  <Avatar src={m.memberAvatar} name={m.memberName} size="sm" />
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-medium text-a-text dark:text-a-dark-text">{m.memberName}</p>
                    <p className="text-xs text-a-muted dark:text-a-dark-muted">{m.planName}</p>
                  </div>
                  <p className="text-xs font-medium text-amber-500">{formatDate(m.expiryDate)}</p>
                </div>
              ))}
            </div>
          )}
        </ChartCard>
      </div>
    </>
  );
}
