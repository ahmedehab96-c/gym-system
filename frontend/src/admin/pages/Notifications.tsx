import { useEffect, useState } from "react";
import { Bell, Check, Trash2, CreditCard, UserPlus, CalendarClock, Wrench, Info, XCircle, Receipt } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { Tabs } from "../components/ui/Tabs";
import { Button } from "../components/ui/Button";
import { Pagination } from "../components/ui/Pagination";
import { EmptyState } from "../components/ui/EmptyState";
import { LoadingState } from "../components/ui/LoadingState";
import { ErrorState } from "../components/ui/ErrorState";
import { useApiList } from "../hooks/useApiList";
import { notificationService } from "../services/notificationService";
import type { NotificationType } from "../types";
import { timeAgo } from "../utils/format";
import { useToast } from "../context/ToastContext";
import { ApiError } from "../services/apiClient";
import { cn } from "../../utils/cn";

const PER_PAGE = 15;

const typeIcon: Record<NotificationType, typeof Bell> = {
  "Membership Expiring": CalendarClock,
  "Membership Expired": CalendarClock,
  "Payment Received": CreditCard,
  "Payment Pending": CreditCard,
  "Payment Failed": XCircle,
  "New Member": UserPlus,
  "Class Reminder": CalendarClock,
  "Class Cancellation": XCircle,
  "Maintenance Due": Wrench,
  "Maintenance Overdue": Wrench,
  "System Notification": Info,
  "Invoice Due Reminder": Receipt,
};

const tabs = [
  "All", "Unread", "Membership Expiring", "Membership Expired", "Payment Received", "Payment Pending",
  "Payment Failed", "New Member", "Class Reminder", "Class Cancellation", "Maintenance Due", "Maintenance Overdue",
  "Invoice Due Reminder", "System Notification",
];

export default function Notifications() {
  const [tab, setTab] = useState("All");
  const [page, setPage] = useState(1);
  const [unreadCount, setUnreadCount] = useState(0);
  const { showToast } = useToast();

  useEffect(() => setPage(1), [tab]);

  const { data: notifications, meta, loading, error, refetch } = useApiList(
    (signal) =>
      notificationService.list(
        {
          read: tab === "Unread" ? false : undefined,
          type: tab === "All" || tab === "Unread" ? undefined : tab,
          page,
          perPage: PER_PAGE,
        },
        signal,
      ),
    [tab, page],
  );

  function loadUnreadCount() {
    notificationService.list({ read: false, perPage: 1 }).then(({ meta }) => setUnreadCount(meta.total)).catch(() => undefined);
  }

  useEffect(loadUnreadCount, []);

  async function markRead(id: string) {
    try {
      await notificationService.markRead(id);
      refetch();
      loadUnreadCount();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not mark as read.", "error");
    }
  }

  async function remove(id: string) {
    try {
      await notificationService.remove(id);
      refetch();
      loadUnreadCount();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not delete notification.", "error");
    }
  }

  async function markAllRead() {
    try {
      await notificationService.markAllRead();
      showToast("All notifications marked as read");
      refetch();
      loadUnreadCount();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not mark all as read.", "error");
    }
  }

  return (
    <div>
      <PageHeader
        title="Notifications"
        description={`${unreadCount} unread notifications`}
        action={<Button variant="secondary" icon={<Check size={15} />} onClick={markAllRead}>Mark all as read</Button>}
      />

      <div className="mb-4 overflow-x-auto"><Tabs tabs={tabs} active={tab} onChange={setTab} /></div>

      {loading ? (
        <LoadingState rows={5} />
      ) : error ? (
        <ErrorState message={error} onRetry={refetch} />
      ) : notifications.length === 0 ? (
        <EmptyState title="No notifications" description="You're all caught up." />
      ) : (
        <div className="admin-card divide-y divide-a-border rounded-2xl shadow-sm dark:divide-a-dark-border">
          {notifications.map((n) => {
            const Icon = typeIcon[n.type] ?? Info;
            return (
              <div key={n.id} className={cn("flex items-start gap-4 p-4", !n.read && "bg-a-accent/5")}>
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-a-surface-2 text-a-accent-2 dark:bg-a-dark-surface-2 dark:text-a-accent">
                  <Icon size={17} />
                </div>
                <div className="min-w-0 flex-1">
                  <div className="flex items-center gap-2">
                    <p className="text-sm font-semibold text-a-text dark:text-a-dark-text">{n.title}</p>
                    {!n.read && <span className="h-1.5 w-1.5 shrink-0 rounded-full bg-a-accent" />}
                  </div>
                  <p className="mt-0.5 text-sm text-a-muted dark:text-a-dark-muted">{n.message}</p>
                  <p className="mt-1 text-xs text-a-muted/70 dark:text-a-dark-muted/70">{timeAgo(n.date)}</p>
                </div>
                <div className="flex shrink-0 gap-1">
                  {!n.read && (
                    <button onClick={() => markRead(n.id)} className="rounded-lg p-1.5 text-a-muted hover:bg-a-surface-2 dark:hover:bg-a-dark-surface-2"><Check size={15} /></button>
                  )}
                  <button onClick={() => remove(n.id)} className="rounded-lg p-1.5 text-rose-500 hover:bg-rose-500/10"><Trash2 size={15} /></button>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {meta && meta.totalPages > 1 && (
        <Pagination page={meta.page} totalPages={meta.totalPages} onChange={setPage} totalItems={meta.total} pageSize={PER_PAGE} />
      )}
    </div>
  );
}
