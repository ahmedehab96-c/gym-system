import { useEffect, useState } from "react";
import { Area, AreaChart, Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from "recharts";
import { Download } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { Tabs } from "../components/ui/Tabs";
import { ChartCard } from "../components/ui/ChartCard";
import { DataTable, type Column } from "../components/ui/DataTable";
import { Button } from "../components/ui/Button";
import { Input } from "../components/ui/Input";
import { StatCard } from "../components/ui/StatCard";
import { LoadingState } from "../components/ui/LoadingState";
import { ErrorState } from "../components/ui/ErrorState";
import { Users, CalendarCheck2, TrendingUp, CalendarRange } from "lucide-react";
import { useToast } from "../context/ToastContext";
import { reportService, type ReportChartPoint } from "../services/reportService";
import { ApiError } from "../services/apiClient";
import { formatCurrency } from "../utils/format";
import type { Trainer, GymClass, EquipmentItem } from "../types";

const reportTabs = ["Revenue", "Membership", "Attendance", "Trainer Performance", "Class Performance", "Expenses", "Equipment"];

interface Overview {
  totalMembers: number;
  totalRevenue: number;
  classesHeld: number;
  avgDailyAttendance: number;
}

export default function Reports() {
  const [tab, setTab] = useState("Revenue");
  const [from, setFrom] = useState("2026-01-01");
  const [to, setTo] = useState("2026-08-31");
  const { showToast } = useToast();

  const [overview, setOverview] = useState<Overview | null>(null);
  const [overviewLoading, setOverviewLoading] = useState(true);

  const [revenueChart, setRevenueChart] = useState<ReportChartPoint[]>([]);
  const [membershipChart, setMembershipChart] = useState<ReportChartPoint[]>([]);
  const [attendanceChart, setAttendanceChart] = useState<ReportChartPoint[]>([]);
  const [expenseByCategory, setExpenseByCategory] = useState<{ category: string; amount: number }[]>([]);
  const [trainers, setTrainers] = useState<Trainer[]>([]);
  const [classes, setClasses] = useState<GymClass[]>([]);
  const [equipment, setEquipment] = useState<EquipmentItem[]>([]);

  const [tabLoading, setTabLoading] = useState(true);
  const [tabError, setTabError] = useState<string | null>(null);

  useEffect(() => {
    setOverviewLoading(true);
    Promise.all([
      reportService.members({ from, to, perPage: 1 }),
      reportService.revenue({ from, to, perPage: 1 }),
      reportService.classes({ from, to, perPage: 1 }),
      reportService.attendance({ from, to, perPage: 1 }),
    ])
      .then(([members, revenue, classesRes, attendance]) => {
        const days = Math.max(1, attendance.chart.length);
        setOverview({
          totalMembers: members.summary.total,
          totalRevenue: revenue.summary.total,
          classesHeld: classesRes.summary.total,
          avgDailyAttendance: Math.round(attendance.summary.totalVisits / days),
        });
      })
      .catch(() => setOverview(null))
      .finally(() => setOverviewLoading(false));
  }, [from, to]);

  useEffect(() => {
    let cancelled = false;
    setTabLoading(true);
    setTabError(null);

    async function load() {
      try {
        if (tab === "Revenue") {
          const res = await reportService.revenue({ from, to, group: "monthly" });
          if (!cancelled) setRevenueChart(res.chart);
        } else if (tab === "Membership") {
          const res = await reportService.memberships({ from, to, group: "monthly" });
          if (!cancelled) setMembershipChart(res.chart);
        } else if (tab === "Attendance") {
          const res = await reportService.attendance({ from, to, group: "daily" });
          if (!cancelled) setAttendanceChart(res.chart);
        } else if (tab === "Trainer Performance") {
          const res = await reportService.trainers({ perPage: 50 });
          if (!cancelled) setTrainers(res.data);
        } else if (tab === "Class Performance") {
          const res = await reportService.classes({ from, to, perPage: 50 });
          if (!cancelled) setClasses(res.data);
        } else if (tab === "Expenses") {
          const res = await reportService.expenses({ from, to });
          if (!cancelled) setExpenseByCategory(res.summary.byCategory);
        } else if (tab === "Equipment") {
          const res = await reportService.equipment({ perPage: 50 });
          if (!cancelled) setEquipment(res.data);
        }
      } catch (err) {
        if (!cancelled) setTabError(err instanceof ApiError ? err.message : "Something went wrong. Please try again.");
      } finally {
        if (!cancelled) setTabLoading(false);
      }
    }

    load();
    return () => {
      cancelled = true;
    };
  }, [tab, from, to]);

  const trainerCols: Column<Trainer>[] = [
    { key: "name", header: "Trainer", render: (t) => t.name },
    { key: "sessions", header: "Sessions", render: (t) => t.sessionsCompleted },
    { key: "members", header: "Members", render: (t) => t.assignedMembers },
    { key: "rating", header: "Rating", render: (t) => t.rating },
  ];
  const classCols: Column<GymClass>[] = [
    { key: "name", header: "Class", render: (c) => c.name },
    { key: "trainer", header: "Trainer", render: (c) => c.trainerName },
    { key: "fill", header: "Fill Rate", render: (c) => `${c.capacity > 0 ? Math.round((c.booked / c.capacity) * 100) : 0}%` },
  ];
  const equipCols: Column<EquipmentItem>[] = [
    { key: "name", header: "Equipment", render: (e) => e.name },
    { key: "condition", header: "Condition", render: (e) => e.condition },
    { key: "location", header: "Location", render: (e) => e.location },
  ];

  return (
    <div>
      <PageHeader
        title="Reports"
        description="Generate detailed reports across every area of the gym"
        action={<Button icon={<Download size={16} />} onClick={() => showToast("Use your browser's print dialog to save this report as a PDF.")}>Export</Button>}
      />

      <div className="mb-4 flex flex-wrap items-end gap-3">
        <Input label="From" type="date" value={from} onChange={(e) => setFrom(e.target.value)} />
        <Input label="To" type="date" value={to} onChange={(e) => setTo(e.target.value)} />
      </div>

      <div className="mb-5 overflow-x-auto"><Tabs tabs={reportTabs} active={tab} onChange={setTab} /></div>

      <div className="grid grid-cols-2 gap-4 md:grid-cols-4 mb-5">
        <StatCard label="Total Members" value={overviewLoading ? "—" : String(overview?.totalMembers ?? 0)} icon={<Users size={18} />} />
        <StatCard label="Total Revenue" value={overviewLoading ? "—" : formatCurrency(overview?.totalRevenue ?? 0)} icon={<TrendingUp size={18} />} />
        <StatCard label="Classes Held" value={overviewLoading ? "—" : String(overview?.classesHeld ?? 0)} icon={<CalendarRange size={18} />} />
        <StatCard label="Avg. Attendance" value={overviewLoading ? "—" : `${overview?.avgDailyAttendance ?? 0}/day`} icon={<CalendarCheck2 size={18} />} />
      </div>

      {tabLoading ? (
        <LoadingState rows={5} />
      ) : tabError ? (
        <ErrorState message={tabError} onRetry={() => setTab((t) => t)} />
      ) : (
        <>
          {tab === "Revenue" && (
            <ChartCard title="Revenue Report" subtitle={`${from} to ${to}`}>
              <ResponsiveContainer width="100%" height={280}>
                <AreaChart data={revenueChart}>
                  <CartesianGrid strokeDasharray="3 3" stroke="var(--color-a-border)" vertical={false} />
                  <XAxis dataKey="label" tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} />
                  <YAxis tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} />
                  <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid var(--color-a-border)", fontSize: 12 }} />
                  <Area type="monotone" dataKey="value" stroke="#d4a72f" fill="#d4a72f30" strokeWidth={2} />
                </AreaChart>
              </ResponsiveContainer>
            </ChartCard>
          )}

          {tab === "Membership" && (
            <ChartCard title="Membership Report" subtitle={`${from} to ${to}`}>
              <ResponsiveContainer width="100%" height={280}>
                <BarChart data={membershipChart}>
                  <CartesianGrid strokeDasharray="3 3" stroke="var(--color-a-border)" vertical={false} />
                  <XAxis dataKey="label" tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} />
                  <YAxis tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} />
                  <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid var(--color-a-border)", fontSize: 12 }} />
                  <Bar dataKey="value" fill="#5b8def" radius={[6, 6, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </ChartCard>
          )}

          {tab === "Attendance" && (
            <ChartCard title="Attendance Report" subtitle={`${from} to ${to}`}>
              <ResponsiveContainer width="100%" height={280}>
                <AreaChart data={attendanceChart}>
                  <CartesianGrid strokeDasharray="3 3" stroke="var(--color-a-border)" vertical={false} />
                  <XAxis dataKey="label" tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} />
                  <YAxis tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} />
                  <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid var(--color-a-border)", fontSize: 12 }} />
                  <Area type="monotone" dataKey="value" stroke="#22c55e" fill="#22c55e30" strokeWidth={2} />
                </AreaChart>
              </ResponsiveContainer>
            </ChartCard>
          )}

          {tab === "Trainer Performance" && (
            <ChartCard title="Trainer Performance">
              <DataTable columns={trainerCols} rows={trainers} rowKey={(t) => t.id} />
            </ChartCard>
          )}

          {tab === "Class Performance" && (
            <ChartCard title="Class Performance">
              <DataTable columns={classCols} rows={classes} rowKey={(c) => c.id} />
            </ChartCard>
          )}

          {tab === "Expenses" && (
            <ChartCard title="Expense Report">
              <ResponsiveContainer width="100%" height={280}>
                <BarChart data={expenseByCategory}>
                  <CartesianGrid strokeDasharray="3 3" stroke="var(--color-a-border)" vertical={false} />
                  <XAxis dataKey="category" tick={{ fontSize: 10, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} />
                  <YAxis tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} />
                  <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid var(--color-a-border)", fontSize: 12 }} />
                  <Bar dataKey="amount" fill="#e0263c" radius={[6, 6, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </ChartCard>
          )}

          {tab === "Equipment" && (
            <ChartCard title="Equipment Report">
              <DataTable columns={equipCols} rows={equipment} rowKey={(e) => e.id} />
            </ChartCard>
          )}
        </>
      )}
    </div>
  );
}
