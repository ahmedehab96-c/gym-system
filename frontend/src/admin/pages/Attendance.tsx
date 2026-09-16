import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from "recharts";
import { ScanLine, QrCode, LogIn, LogOut, Search } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { StatCard } from "../components/ui/StatCard";
import { ChartCard } from "../components/ui/ChartCard";
import { SearchBar } from "../components/ui/SearchBar";
import { DataTable, type Column } from "../components/ui/DataTable";
import { Pagination } from "../components/ui/Pagination";
import { Avatar } from "../components/ui/Avatar";
import { Badge } from "../components/ui/Badge";
import { Button } from "../components/ui/Button";
import { Modal } from "../components/ui/Modal";
import { useApiList } from "../hooks/useApiList";
import { useDebouncedValue } from "../hooks/useDebouncedValue";
import { attendanceService, type AttendanceStats } from "../services/attendanceService";
import { memberService } from "../services/memberService";
import type { AttendanceRecord, Member } from "../types";
import { formatDate } from "../utils/format";
import { useToast } from "../context/ToastContext";
import { ApiError } from "../services/apiClient";

const PER_PAGE = 10;

export default function Attendance() {
  const navigate = useNavigate();
  const [query, setQuery] = useState("");
  const [page, setPage] = useState(1);
  const [checkInOpen, setCheckInOpen] = useState(false);
  const [checkInQuery, setCheckInQuery] = useState("");
  const [checkInResults, setCheckInResults] = useState<Member[]>([]);
  const [checkingInId, setCheckingInId] = useState<string | null>(null);
  const [stats, setStats] = useState<AttendanceStats | null>(null);
  const { showToast } = useToast();
  const debouncedQuery = useDebouncedValue(query);
  const debouncedCheckInQuery = useDebouncedValue(checkInQuery, 250);

  useEffect(() => setPage(1), [debouncedQuery]);

  const { data: records, meta, loading, error, refetch } = useApiList(
    (signal) => attendanceService.list({ search: debouncedQuery || undefined, page, perPage: PER_PAGE }, signal),
    [debouncedQuery, page],
  );

  function loadStats() {
    attendanceService.stats().then(setStats).catch(() => undefined);
  }

  useEffect(loadStats, []);

  useEffect(() => {
    if (!checkInOpen || debouncedCheckInQuery.length < 2) {
      setCheckInResults([]);
      return;
    }
    const controller = new AbortController();
    memberService
      .list({ search: debouncedCheckInQuery, status: "Active", perPage: 5 }, controller.signal)
      .then(({ data }) => setCheckInResults(data))
      .catch(() => undefined);
    return () => controller.abort();
  }, [checkInOpen, debouncedCheckInQuery]);

  async function handleManualCheckIn(memberId: string) {
    setCheckingInId(memberId);
    try {
      await attendanceService.checkIn(memberId, "Manual");
      showToast("Member checked in");
      setCheckInOpen(false);
      setCheckInQuery("");
      refetch();
      loadStats();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not check in member.", "error");
    } finally {
      setCheckingInId(null);
    }
  }

  async function handleCheckOut(id: string) {
    try {
      await attendanceService.checkOut(id);
      showToast("Member checked out");
      refetch();
      loadStats();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not check out member.", "error");
    }
  }

  const cols: Column<AttendanceRecord>[] = [
    { key: "member", header: "Member", render: (a) => (
      <div className="flex items-center gap-3">
        <Avatar src={a.memberAvatar} name={a.memberName} size="sm" />
        <span className="font-medium">{a.memberName}</span>
      </div>
    ) },
    { key: "date", header: "Date", render: (a) => formatDate(a.date) },
    { key: "checkIn", header: "Check In", render: (a) => a.checkIn },
    { key: "checkOut", header: "Check Out", render: (a) =>
      a.checkOut ?? (
        <button
          onClick={(e) => { e.stopPropagation(); handleCheckOut(a.id); }}
          className="inline-flex items-center gap-1 rounded-lg bg-a-accent/10 px-2.5 py-1 text-xs font-medium text-a-accent-2 transition-colors hover:bg-a-accent/20 dark:text-a-accent"
        >
          <LogOut size={12} /> Check Out
        </button>
      )
    },
    { key: "duration", header: "Duration", render: (a) => a.duration ?? "—" },
    { key: "method", header: "Method", render: (a) => <Badge>{a.method}</Badge> },
  ];

  return (
    <div>
      <PageHeader
        title="Attendance"
        description="Monitor daily check-ins and gym traffic"
        action={
          <>
            <Button variant="secondary" icon={<ScanLine size={15} />} onClick={() => navigate("/admin/attendance/scanner")}>QR Scanner</Button>
            <Button icon={<LogIn size={15} />} onClick={() => setCheckInOpen(true)}>Manual Check-in</Button>
          </>
        }
      />

      <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
        <StatCard label="Present Today" value={String(stats?.today.presentToday ?? "—")} icon={<LogIn size={18} />} />
        <StatCard label="Checked In" value={String(stats?.today.checkedIn ?? "—")} icon={<LogIn size={18} />} accent="from-emerald-400/20 to-emerald-500/10" />
        <StatCard label="Checked Out" value={String(stats?.today.checkedOut ?? "—")} icon={<LogOut size={18} />} accent="from-sky-400/20 to-sky-500/10" />
        <StatCard label="Average Attendance" value={stats ? `${stats.today.averageAttendanceRate}%` : "—"} icon={<QrCode size={18} />} />
      </div>

      <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <ChartCard title="Daily / Weekly Attendance" subtitle="Visits by day of week">
          <ResponsiveContainer width="100%" height={220}>
            <BarChart data={stats?.weekly ?? []}>
              <CartesianGrid strokeDasharray="3 3" stroke="var(--color-a-border)" vertical={false} />
              <XAxis dataKey="day" tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} />
              <YAxis tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} />
              <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid var(--color-a-border)", fontSize: 12 }} />
              <Bar dataKey="visits" fill="#5b8def" radius={[6, 6, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </ChartCard>
        <ChartCard title="Monthly Attendance" subtitle="Total visits per month">
          <ResponsiveContainer width="100%" height={220}>
            <BarChart data={stats?.monthly ?? []}>
              <CartesianGrid strokeDasharray="3 3" stroke="var(--color-a-border)" vertical={false} />
              <XAxis dataKey="month" tick={{ fontSize: 10, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} />
              <YAxis tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} />
              <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid var(--color-a-border)", fontSize: 12 }} />
              <Bar dataKey="visits" fill="#d4a72f" radius={[6, 6, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </ChartCard>
      </div>

      <div className="mt-5">
        <div className="mb-3"><SearchBar value={query} onChange={setQuery} placeholder="Search member..." className="sm:max-w-xs" /></div>
        <div className="admin-card rounded-2xl p-4 shadow-sm">
          <DataTable columns={cols} rows={records} rowKey={(a) => a.id} loading={loading} error={error} onRetry={refetch} />
          {meta && meta.totalPages > 1 && (
            <Pagination page={meta.page} totalPages={meta.totalPages} onChange={setPage} totalItems={meta.total} pageSize={PER_PAGE} />
          )}
        </div>
      </div>

      <Modal
        open={checkInOpen}
        onClose={() => { setCheckInOpen(false); setCheckInQuery(""); setCheckInResults([]); }}
        title="Manual Check-in"
        size="sm"
      >
        <div className="relative">
          <Search size={15} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-a-muted dark:text-a-dark-muted" />
          <input
            autoFocus
            value={checkInQuery}
            onChange={(e) => setCheckInQuery(e.target.value)}
            placeholder="Search active members by name..."
            className="w-full rounded-xl border border-a-border bg-a-surface py-2.5 pl-9 pr-3 text-sm text-a-text outline-none transition-colors focus:border-a-accent dark:border-a-dark-border dark:bg-a-dark-surface-2 dark:text-a-dark-text"
          />
        </div>
        <div className="mt-3 max-h-72 space-y-1 overflow-y-auto">
          {debouncedCheckInQuery.length >= 2 && checkInResults.length === 0 && (
            <p className="py-4 text-center text-xs text-a-muted dark:text-a-dark-muted">No active members found.</p>
          )}
          {checkInResults.map((m) => (
            <button
              key={m.id}
              disabled={checkingInId === m.id}
              onClick={() => handleManualCheckIn(m.id)}
              className="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left transition-colors hover:bg-a-surface-2 disabled:opacity-50 dark:hover:bg-a-dark-surface-2"
            >
              <Avatar src={m.avatar} name={m.name} size="sm" />
              <span className="min-w-0 flex-1 truncate text-sm text-a-text dark:text-a-dark-text">{m.name}</span>
              <span className="text-xs text-a-muted dark:text-a-dark-muted">{checkingInId === m.id ? "Checking in..." : m.memberId}</span>
            </button>
          ))}
        </div>
      </Modal>
    </div>
  );
}
