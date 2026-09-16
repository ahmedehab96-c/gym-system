import { useState } from "react";
import type { GymClass } from "../../types";
import { cn } from "../../../utils/cn";

interface WeekCalendarProps {
  classes: GymClass[];
  onSelect?: (item: GymClass) => void;
  onMove?: (id: string, day: string) => void;
}

const days = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
const hours = Array.from({ length: 15 }, (_, i) => 6 + i);

function timeToRow(time: string) {
  const [h, m] = time.split(":").map(Number);
  return (h - 6) * 2 + (m >= 30 ? 1 : 0) + 1;
}

export function WeekCalendar({ classes, onSelect, onMove }: WeekCalendarProps) {
  const [dragId, setDragId] = useState<string | null>(null);

  return (
    <div className="overflow-x-auto">
      <div className="grid min-w-[820px] grid-cols-[64px_repeat(7,1fr)]">
        <div />
        {days.map((d) => (
          <div key={d} className="border-b border-a-border px-2 pb-2 text-center text-xs font-semibold text-a-muted dark:border-a-dark-border dark:text-a-dark-muted">
            {d}
          </div>
        ))}
        {hours.map((h) => (
          <div key={h} className="contents">
            <div className="border-t border-a-border py-3 pr-2 text-right text-[11px] text-a-muted dark:border-a-dark-border dark:text-a-dark-muted">
              {h}:00
            </div>
            {days.map((d) => (
              <div
                key={d + h}
                onDragOver={(e) => e.preventDefault()}
                onDrop={() => {
                  if (dragId && onMove) onMove(dragId, d);
                  setDragId(null);
                }}
                className="relative h-10 border-t border-l border-a-border/60 dark:border-a-dark-border/60"
              />
            ))}
          </div>
        ))}
        {days.map((d, di) =>
          classes
            .filter((c) => c.day === d)
            .map((c) => {
              const row = timeToRow(c.startTime);
              const endRow = timeToRow(c.endTime);
              const span = Math.max(1, endRow - row);
              return (
                <div
                  key={c.id}
                  draggable
                  onDragStart={() => setDragId(c.id)}
                  onClick={() => onSelect?.(c)}
                  style={{
                    gridColumn: di + 2,
                    gridRow: `${row + 1} / span ${span}`,
                    backgroundColor: `${c.color}1f`,
                    borderColor: `${c.color}55`,
                  }}
                  className={cn(
                    "z-10 m-0.5 cursor-pointer overflow-hidden rounded-lg border p-1.5 text-[11px] leading-tight transition-transform hover:scale-[1.02]",
                  )}
                >
                  <p className="font-semibold" style={{ color: c.color }}>{c.name}</p>
                  <p className="text-a-muted dark:text-a-dark-muted">{c.startTime} · {c.trainerName}</p>
                </div>
              );
            }),
        )}
      </div>
    </div>
  );
}
