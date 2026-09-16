import { useState } from "react";
import { PageHeader } from "../components/layout/PageHeader";
import { Modal } from "../components/ui/Modal";
import { WeekCalendar } from "../components/ui/WeekCalendar";
import { StatusBadge } from "../components/ui/Badge";
import { LoadingState } from "../components/ui/LoadingState";
import { ErrorState } from "../components/ui/ErrorState";
import { useApiResource } from "../hooks/useApiResource";
import { classService } from "../services/classService";
import { scheduleService } from "../services/scheduleService";
import type { GymClass } from "../types";
import { useToast } from "../context/ToastContext";
import { ApiError } from "../services/apiClient";

export default function ClassSchedule() {
  const [selected, setSelected] = useState<GymClass | null>(null);
  const { showToast } = useToast();

  const { data: week, loading, error, refetch } = useApiResource(
    (signal) => scheduleService.weekly(undefined, signal).then((data) => ({ data })),
    [],
  );

  const classes = week?.days.flatMap((d) => d.classes) ?? [];

  async function handleMove(id: string, day: string) {
    try {
      await classService.update(id, { day });
      showToast("Class rescheduled");
      refetch();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not reschedule class.", "error");
    }
  }

  return (
    <div>
      <PageHeader
        title="Class Schedule"
        description={week ? `Week of ${week.weekStart} to ${week.weekEnd} — drag classes between days to reschedule` : "Drag classes between days to reschedule"}
      />

      <div className="admin-card rounded-2xl p-4 shadow-sm">
        {loading ? (
          <LoadingState rows={6} />
        ) : error ? (
          <ErrorState message={error} onRetry={refetch} />
        ) : (
          <WeekCalendar classes={classes} onSelect={setSelected} onMove={handleMove} />
        )}
      </div>

      <Modal open={!!selected} onClose={() => setSelected(null)} title={selected?.name} size="sm">
        {selected && (
          <div className="space-y-3 text-sm">
            <div className="flex justify-between"><span className="text-a-muted dark:text-a-dark-muted">Trainer</span><span className="font-medium text-a-text dark:text-a-dark-text">{selected.trainerName}</span></div>
            <div className="flex justify-between"><span className="text-a-muted dark:text-a-dark-muted">Day</span><span className="font-medium text-a-text dark:text-a-dark-text">{selected.day}</span></div>
            <div className="flex justify-between"><span className="text-a-muted dark:text-a-dark-muted">Time</span><span className="font-medium text-a-text dark:text-a-dark-text">{selected.startTime} - {selected.endTime}</span></div>
            <div className="flex justify-between"><span className="text-a-muted dark:text-a-dark-muted">Capacity</span><span className="font-medium text-a-text dark:text-a-dark-text">{selected.booked}/{selected.capacity}</span></div>
            <div className="flex justify-between"><span className="text-a-muted dark:text-a-dark-muted">Status</span><StatusBadge status={selected.status} /></div>
          </div>
        )}
      </Modal>
    </div>
  );
}
