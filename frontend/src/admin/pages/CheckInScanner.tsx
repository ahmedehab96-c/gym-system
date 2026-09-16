import { useEffect, useState } from "react";
import { AnimatePresence, motion } from "framer-motion";
import { CheckCircle2, Info, LogIn, LogOut, RotateCcw, Search, ShieldAlert, Video, VideoOff, XCircle } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { StatCard } from "../components/ui/StatCard";
import { Avatar } from "../components/ui/Avatar";
import { StatusBadge } from "../components/ui/Badge";
import { Button } from "../components/ui/Button";
import { useQrScanner } from "../hooks/useQrScanner";
import { useDebouncedValue } from "../hooks/useDebouncedValue";
import { attendanceService } from "../services/attendanceService";
import { memberService } from "../services/memberService";
import { ApiError } from "../services/apiClient";
import type { AttendanceRecord, Member } from "../types";
import { formatDateTime } from "../utils/format";
import { cn } from "../../utils/cn";

type Mode = "check-in" | "check-out";

interface ScanOutcome {
  id: string;
  at: number;
  mode: Mode;
  ok: boolean;
  message: string;
  duplicate?: boolean;
  member?: Member;
  attendance?: AttendanceRecord;
}

/**
 * Front-desk QR scanning (Phase 28 §2/§6) — camera decode -> backend
 * validation -> immediate result, with manual name search as a fallback
 * for a lost/undisplayable code and a running log of the session's
 * scans. Every validation (member/tenant/status/expiry/duplicate) is
 * the backend's call; this page only ever shows what it's told.
 */
export default function CheckInScanner() {
  const [mode, setMode] = useState<Mode>("check-in");
  const [cameraOn, setCameraOn] = useState(true);
  const [paused, setPaused] = useState(false);
  const [busy, setBusy] = useState(false);
  const [outcome, setOutcome] = useState<ScanOutcome | null>(null);
  const [recent, setRecent] = useState<ScanOutcome[]>([]);
  const [today, setToday] = useState<AttendanceRecord[]>([]);
  const [manualOpen, setManualOpen] = useState(false);
  const [manualQuery, setManualQuery] = useState("");
  const [manualResults, setManualResults] = useState<Member[]>([]);
  const [manualBusyId, setManualBusyId] = useState<string | null>(null);
  const debouncedManualQuery = useDebouncedValue(manualQuery, 250);

  function loadToday() {
    attendanceService.today().then(setToday).catch(() => undefined);
  }
  useEffect(loadToday, []);

  useEffect(() => {
    if (debouncedManualQuery.length < 2) {
      setManualResults([]);
      return;
    }
    const controller = new AbortController();
    memberService
      .list({ search: debouncedManualQuery, status: "Active", perPage: 5 }, controller.signal)
      .then(({ data }) => setManualResults(data))
      .catch(() => undefined);
    return () => controller.abort();
  }, [debouncedManualQuery]);

  function recordOutcome(partial: Omit<ScanOutcome, "id" | "at" | "mode">) {
    const entry: ScanOutcome = { id: `${Date.now()}-${Math.random()}`, at: Date.now(), mode, ...partial };
    setOutcome(entry);
    setRecent((prev) => [entry, ...prev].slice(0, 8));
  }

  async function handleDecoded(token: string) {
    if (busy || paused) return;
    setBusy(true);
    setPaused(true);
    try {
      if (mode === "check-in") {
        const result = await attendanceService.qrCheckIn(token);
        recordOutcome({
          ok: true,
          duplicate: result.duplicate,
          message: result.duplicate ? `${result.member.name} is already checked in.` : `${result.member.name} checked in.`,
          member: result.member,
          attendance: result.attendance,
        });
      } else {
        const result = await attendanceService.qrCheckOut(token);
        recordOutcome({ ok: true, message: `${result.member.name} checked out.`, member: result.member, attendance: result.attendance });
      }
      loadToday();
    } catch (err) {
      recordOutcome({ ok: false, message: err instanceof ApiError ? err.message : "Could not process this QR code." });
    } finally {
      setBusy(false);
    }
  }

  function resumeScanning() {
    setOutcome(null);
    setPaused(false);
  }

  const { videoRef, error: cameraError } = useQrScanner(cameraOn && !manualOpen && !paused, handleDecoded);

  async function handleManualCheckIn(member: Member) {
    setManualBusyId(member.id);
    try {
      const record = await attendanceService.checkIn(member.id, "Manual");
      recordOutcome({ ok: true, message: `${member.name} checked in.`, member, attendance: record });
      setManualOpen(false);
      setManualQuery("");
      setManualResults([]);
      loadToday();
    } catch (err) {
      recordOutcome({ ok: false, message: err instanceof ApiError ? err.message : "Could not check in member." });
    } finally {
      setManualBusyId(null);
    }
  }

  return (
    <div>
      <PageHeader
        title="Check-in Scanner"
        description="Scan a member's QR code for instant, validated check-in / check-out"
        action={
          <div className="flex flex-wrap items-center gap-2">
            <div className="flex rounded-xl border border-a-border p-1 dark:border-a-dark-border">
              <button
                onClick={() => setMode("check-in")}
                className={cn(
                  "flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors",
                  mode === "check-in" ? "bg-a-accent text-black" : "text-a-muted hover:text-a-text dark:text-a-dark-muted dark:hover:text-a-dark-text",
                )}
              >
                <LogIn size={13} /> Check-In
              </button>
              <button
                onClick={() => setMode("check-out")}
                className={cn(
                  "flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors",
                  mode === "check-out" ? "bg-a-accent text-black" : "text-a-muted hover:text-a-text dark:text-a-dark-muted dark:hover:text-a-dark-text",
                )}
              >
                <LogOut size={13} /> Check-Out
              </button>
            </div>
            <Button variant="secondary" icon={<Search size={15} />} onClick={() => setManualOpen((v) => !v)}>
              {manualOpen ? "Hide Search" : "Manual Search"}
            </Button>
          </div>
        }
      />

      <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
        <StatCard label="Today's Check-ins" value={String(today.length)} icon={<LogIn size={18} />} />
        <StatCard label="Currently In" value={String(today.filter((r) => !r.checkOut).length)} icon={<Video size={18} />} accent="from-emerald-400/20 to-emerald-500/10" />
        <StatCard label="Checked Out" value={String(today.filter((r) => r.checkOut).length)} icon={<LogOut size={18} />} accent="from-sky-400/20 to-sky-500/10" />
        <StatCard label="This Session" value={String(recent.filter((r) => r.ok).length)} icon={<CheckCircle2 size={18} />} accent="from-a-accent/20 to-a-accent-2/10" />
      </div>

      <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-5">
        <div className="lg:col-span-3">
          <div className="admin-card overflow-hidden rounded-2xl shadow-sm">
            <div className="relative flex aspect-square w-full items-center justify-center bg-black sm:aspect-video">
              <video ref={videoRef} muted playsInline className={cn("h-full w-full object-cover", (!cameraOn || manualOpen) && "hidden")} />

              {(!cameraOn || manualOpen) && (
                <div className="flex flex-col items-center gap-2 text-a-dark-muted">
                  <VideoOff size={36} />
                  <p className="text-sm">Camera paused</p>
                </div>
              )}

              {cameraOn && !manualOpen && !paused && (
                <div className="pointer-events-none absolute inset-0 flex items-center justify-center p-10">
                  <div className="h-full max-h-64 w-full max-w-64 rounded-2xl border-2 border-dashed border-white/60" />
                </div>
              )}

              {cameraError && (
                <div className="absolute inset-x-4 bottom-4 flex items-center gap-2 rounded-xl bg-rose-500/90 px-3 py-2 text-xs font-medium text-white">
                  <ShieldAlert size={14} /> {cameraError}
                </div>
              )}

              <AnimatePresence>
                {outcome && (
                  <motion.div
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    className="absolute inset-0 flex flex-col items-center justify-center gap-4 bg-black/85 p-6 text-center"
                  >
                    {outcome.ok ? (
                      outcome.duplicate ? (
                        <Info size={56} className="text-amber-400" />
                      ) : (
                        <CheckCircle2 size={56} className="text-emerald-400" />
                      )
                    ) : (
                      <XCircle size={56} className="text-rose-400" />
                    )}

                    {outcome.member && (
                      <Avatar src={outcome.member.avatar} name={outcome.member.name} size="lg" className="ring-4 ring-white/20" />
                    )}

                    <div>
                      <p className="text-lg font-semibold text-white">{outcome.message}</p>
                      {outcome.member && (
                        <div className="mt-2 flex items-center justify-center gap-2">
                          <StatusBadge status={outcome.member.status} />
                          {outcome.attendance && <span className="text-xs text-white/60">at {outcome.attendance.checkIn}</span>}
                        </div>
                      )}
                    </div>

                    <Button variant="secondary" icon={<RotateCcw size={15} />} onClick={resumeScanning}>
                      Scan Next
                    </Button>
                  </motion.div>
                )}
              </AnimatePresence>

              {busy && !outcome && (
                <div className="absolute inset-0 flex items-center justify-center bg-black/60">
                  <div className="h-10 w-10 animate-spin rounded-full border-2 border-white/20 border-t-white" />
                </div>
              )}
            </div>

            <div className="flex items-center justify-between border-t border-a-border p-3 dark:border-a-dark-border">
              <p className="text-xs text-a-muted dark:text-a-dark-muted">
                {manualOpen ? "Camera paused while searching manually." : "Point the camera at a member's QR code."}
              </p>
              <Button
                variant="ghost"
                size="sm"
                icon={cameraOn ? <VideoOff size={14} /> : <Video size={14} />}
                onClick={() => setCameraOn((v) => !v)}
                disabled={manualOpen}
              >
                {cameraOn ? "Pause" : "Resume"}
              </Button>
            </div>
          </div>

          {manualOpen && (
            <div className="admin-card mt-4 rounded-2xl p-4 shadow-sm">
              <div className="relative">
                <Search size={15} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-a-muted dark:text-a-dark-muted" />
                <input
                  autoFocus
                  value={manualQuery}
                  onChange={(e) => setManualQuery(e.target.value)}
                  placeholder="Search active members by name..."
                  className="w-full rounded-xl border border-a-border bg-a-surface py-2.5 pl-9 pr-3 text-sm text-a-text outline-none transition-colors focus:border-a-accent dark:border-a-dark-border dark:bg-a-dark-surface-2 dark:text-a-dark-text"
                />
              </div>
              <div className="mt-3 max-h-72 space-y-1 overflow-y-auto">
                {debouncedManualQuery.length >= 2 && manualResults.length === 0 && (
                  <p className="py-4 text-center text-xs text-a-muted dark:text-a-dark-muted">No active members found.</p>
                )}
                {manualResults.map((m) => (
                  <button
                    key={m.id}
                    disabled={manualBusyId === m.id}
                    onClick={() => handleManualCheckIn(m)}
                    className="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left transition-colors hover:bg-a-surface-2 disabled:opacity-50 dark:hover:bg-a-dark-surface-2"
                  >
                    <Avatar src={m.avatar} name={m.name} size="sm" />
                    <span className="min-w-0 flex-1 truncate text-sm text-a-text dark:text-a-dark-text">{m.name}</span>
                    <span className="text-xs text-a-muted dark:text-a-dark-muted">{manualBusyId === m.id ? "Checking in..." : m.memberId}</span>
                  </button>
                ))}
              </div>
            </div>
          )}
        </div>

        <div className="lg:col-span-2">
          <div className="admin-card rounded-2xl p-4 shadow-sm">
            <h3 className="mb-3 text-sm font-semibold text-a-text dark:text-a-dark-text">Recent Scans</h3>
            {recent.length === 0 ? (
              <p className="py-8 text-center text-xs text-a-muted dark:text-a-dark-muted">Scans from this session will appear here.</p>
            ) : (
              <ul className="space-y-1.5">
                {recent.map((r) => (
                  <li key={r.id} className="flex items-center gap-3 rounded-xl px-2 py-2 hover:bg-a-surface-2 dark:hover:bg-a-dark-surface-2">
                    {r.member ? (
                      <Avatar src={r.member.avatar} name={r.member.name} size="sm" />
                    ) : (
                      <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-rose-500/10 text-rose-500">
                        <XCircle size={16} />
                      </span>
                    )}
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-sm font-medium text-a-text dark:text-a-dark-text">{r.member?.name ?? "Unresolved scan"}</p>
                      <p className="truncate text-xs text-a-muted dark:text-a-dark-muted">{r.message}</p>
                    </div>
                    <span className="shrink-0 text-[11px] text-a-muted dark:text-a-dark-muted">{formatDateTime(new Date(r.at).toISOString())}</span>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
