import { useEffect, useState } from "react";
import { Cell, Pie, PieChart, RadarChart, Radar, PolarGrid, PolarAngleAxis, ResponsiveContainer, Tooltip, Legend } from "recharts";
import { PageHeader } from "../components/layout/PageHeader";
import { ChartCard } from "../components/ui/ChartCard";
import { StatCard } from "../components/ui/StatCard";
import { LoadingState } from "../components/ui/LoadingState";
import { ErrorState } from "../components/ui/ErrorState";
import { Activity, Target, Users, Zap } from "lucide-react";
import { analyticsService, type TrainerPerformance } from "../services/analyticsService";
import { reportService } from "../services/reportService";
import { attendanceService } from "../services/attendanceService";
import { membershipPlanService } from "../services/membershipPlanService";
import { ApiError } from "../services/apiClient";
import { formatMinutes } from "../utils/format";

interface AnalyticsData {
  retentionRate: number;
  conversionRate: number;
  engagementScore: number;
  averageSessionLength: string;
  planDistribution: { name: string; value: number; color: string }[];
  trainerRadar: { trainer: string; rating: number }[];
}

export default function Analytics() {
  const [data, setData] = useState<AnalyticsData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  function load() {
    setLoading(true);
    setError(null);
    Promise.all([
      reportService.members({ perPage: 1 }),
      reportService.memberships({ perPage: 1 }),
      attendanceService.stats(),
      membershipPlanService.list(),
      analyticsService.trainerPerformance(),
    ])
      .then(([members, memberships, attendance, plans, trainers]: [
        Awaited<ReturnType<typeof reportService.members>>,
        Awaited<ReturnType<typeof reportService.memberships>>,
        Awaited<ReturnType<typeof attendanceService.stats>>,
        Awaited<ReturnType<typeof membershipPlanService.list>>,
        TrainerPerformance[],
      ]) => {
        setData({
          retentionRate: members.summary.total > 0 ? Math.round((members.summary.active / members.summary.total) * 100) : 0,
          conversionRate: memberships.summary.total > 0 ? Math.round((memberships.summary.active / memberships.summary.total) * 100) : 0,
          engagementScore: attendance.today.averageAttendanceRate,
          averageSessionLength: formatMinutes(attendance.averageSessionMinutes),
          planDistribution: plans.map((p) => ({ name: p.name, value: p.memberCount, color: p.color })),
          trainerRadar: trainers.slice(0, 5).map((t) => ({ trainer: t.name.split(" ")[0], rating: t.rating * 20 })),
        });
      })
      .catch((err) => setError(err instanceof ApiError ? err.message : "Something went wrong. Please try again."))
      .finally(() => setLoading(false));
  }

  useEffect(load, []);

  if (loading) return <div><PageHeader title="Analytics" description="Deeper insight into gym performance metrics" /><LoadingState rows={6} /></div>;
  if (error || !data) return <div><PageHeader title="Analytics" description="Deeper insight into gym performance metrics" /><ErrorState message={error ?? undefined} onRetry={load} /></div>;

  return (
    <div>
      <PageHeader title="Analytics" description="Deeper insight into gym performance metrics" />

      <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
        <StatCard label="Retention Rate" value={`${data.retentionRate}%`} icon={<Users size={18} />} />
        <StatCard label="Avg. Session Length" value={data.averageSessionLength} icon={<Activity size={18} />} />
        <StatCard label="Conversion Rate" value={`${data.conversionRate}%`} icon={<Target size={18} />} />
        <StatCard label="Engagement Score" value={String(data.engagementScore)} icon={<Zap size={18} />} />
      </div>

      <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <ChartCard title="Members by Plan" subtitle="Distribution across membership tiers">
          <ResponsiveContainer width="100%" height={280}>
            <PieChart>
              <Pie data={data.planDistribution} dataKey="value" nameKey="name" innerRadius={0} outerRadius={100} label>
                {data.planDistribution.map((p) => <Cell key={p.name} fill={p.color} />)}
              </Pie>
              <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid var(--color-a-border)", fontSize: 12 }} />
              <Legend wrapperStyle={{ fontSize: 12 }} />
            </PieChart>
          </ResponsiveContainer>
        </ChartCard>

        <ChartCard title="Trainer Rating Comparison">
          <ResponsiveContainer width="100%" height={280}>
            <RadarChart data={data.trainerRadar}>
              <PolarGrid stroke="var(--color-a-border)" />
              <PolarAngleAxis dataKey="trainer" tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} />
              <Radar dataKey="rating" stroke="#d4a72f" fill="#d4a72f" fillOpacity={0.35} />
              <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid var(--color-a-border)", fontSize: 12 }} />
            </RadarChart>
          </ResponsiveContainer>
        </ChartCard>
      </div>
    </div>
  );
}
