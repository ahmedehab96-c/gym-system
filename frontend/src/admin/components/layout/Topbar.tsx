import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { Bell, LogOut, Menu, Moon, Search, Settings, Sun, User } from "lucide-react";
import { useTheme } from "../../context/ThemeContext";
import { useAuth } from "../../context/AuthContext";
import { Avatar } from "../ui/Avatar";
import { Dropdown, DropdownItem } from "../ui/Dropdown";
import { notificationService } from "../../services/notificationService";
import { memberService } from "../../services/memberService";
import { useDebouncedValue } from "../../hooks/useDebouncedValue";
import { timeAgo } from "../../utils/format";
import type { AppNotification, Member } from "../../types";

export function Topbar({ onMenuClick }: { onMenuClick: () => void }) {
  const { theme, toggleTheme } = useTheme();
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [query, setQuery] = useState("");
  const [results, setResults] = useState<Member[]>([]);
  const [notifications, setNotifications] = useState<AppNotification[]>([]);
  const [unread, setUnread] = useState(0);
  const debouncedQuery = useDebouncedValue(query, 250);

  function loadNotifications() {
    notificationService.list({ perPage: 5 }).then(({ data }) => setNotifications(data)).catch(() => undefined);
    notificationService.list({ read: false, perPage: 1 }).then(({ meta }) => setUnread(meta.total)).catch(() => undefined);
  }

  useEffect(loadNotifications, []);

  useEffect(() => {
    if (debouncedQuery.length < 2) {
      setResults([]);
      return;
    }
    const controller = new AbortController();
    memberService
      .list({ search: debouncedQuery, perPage: 5 }, controller.signal)
      .then(({ data }) => setResults(data))
      .catch(() => undefined);
    return () => controller.abort();
  }, [debouncedQuery]);

  function handleSignOut() {
    logout();
    navigate("/admin/login", { replace: true });
  }

  return (
    <header className="sticky top-0 z-30 flex items-center gap-3 border-b border-a-border bg-a-surface/90 px-4 py-3 backdrop-blur dark:border-a-dark-border dark:bg-a-dark-surface/90 sm:px-6">
      <button onClick={onMenuClick} className="text-a-muted lg:hidden">
        <Menu size={20} />
      </button>

      <div className="relative hidden flex-1 max-w-md sm:block">
        <Search size={15} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-a-muted dark:text-a-dark-muted" />
        <input
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder="Search members, trainers, invoices..."
          className="w-full rounded-xl border border-a-border bg-a-surface-2 py-2 pl-9 pr-3 text-sm text-a-text outline-none transition-colors placeholder:text-a-muted focus:border-a-accent dark:border-a-dark-border dark:bg-a-dark-surface-2 dark:text-a-dark-text dark:placeholder:text-a-dark-muted"
        />
        {results.length > 0 && (
          <div className="admin-card absolute left-0 right-0 top-full z-20 mt-1.5 overflow-hidden rounded-xl shadow-xl">
            {results.map((m) => (
              <Link
                key={m.id}
                to={`/admin/members/${m.id}`}
                onClick={() => setQuery("")}
                className="flex items-center gap-2.5 px-3 py-2 text-sm hover:bg-a-surface-2 dark:hover:bg-a-dark-surface-2"
              >
                <Avatar src={m.avatar} name={m.name} size="sm" />
                <span className="text-a-text dark:text-a-dark-text">{m.name}</span>
                <span className="ml-auto text-xs text-a-muted dark:text-a-dark-muted">{m.memberId}</span>
              </Link>
            ))}
          </div>
        )}
      </div>

      <div className="ml-auto flex items-center gap-2">
        <button
          onClick={toggleTheme}
          className="flex h-9 w-9 items-center justify-center rounded-xl text-a-muted transition-colors hover:bg-a-surface-2 hover:text-a-text dark:text-a-dark-muted dark:hover:bg-a-dark-surface-2 dark:hover:text-a-dark-text"
          aria-label="Toggle theme"
        >
          {theme === "dark" ? <Sun size={18} /> : <Moon size={18} />}
        </button>

        <Dropdown
          trigger={
            <button className="relative flex h-9 w-9 items-center justify-center rounded-xl text-a-muted transition-colors hover:bg-a-surface-2 hover:text-a-text dark:text-a-dark-muted dark:hover:bg-a-dark-surface-2 dark:hover:text-a-dark-text">
              <Bell size={18} />
              {unread > 0 && (
                <span className="absolute right-1.5 top-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-a-accent text-[9px] font-bold text-black">
                  {unread}
                </span>
              )}
            </button>
          }
          className="w-80 max-h-96 overflow-y-auto"
        >
          <div className="flex items-center justify-between border-b border-a-border px-4 py-3 dark:border-a-dark-border">
            <p className="text-sm font-semibold text-a-text dark:text-a-dark-text">Notifications</p>
            <Link to="/admin/notifications" className="text-xs font-medium text-a-accent-2 dark:text-a-accent">View all</Link>
          </div>
          {notifications.length === 0 && (
            <p className="px-4 py-6 text-center text-xs text-a-muted dark:text-a-dark-muted">No notifications yet.</p>
          )}
          {notifications.map((n) => (
            <div key={n.id} className="border-b border-a-border/60 px-4 py-3 last:border-0 dark:border-a-dark-border/60">
              <div className="flex items-start gap-2">
                {!n.read && <span className="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-a-accent" />}
                <div className={n.read ? "pl-3.5" : ""}>
                  <p className="text-xs font-semibold text-a-text dark:text-a-dark-text">{n.title}</p>
                  <p className="mt-0.5 text-xs text-a-muted dark:text-a-dark-muted">{n.message}</p>
                  <p className="mt-1 text-[10px] text-a-muted/70 dark:text-a-dark-muted/70">{timeAgo(n.date)}</p>
                </div>
              </div>
            </div>
          ))}
        </Dropdown>

        <Dropdown
          trigger={
            <button className="flex items-center gap-2 rounded-xl py-1 pl-1 pr-2 transition-colors hover:bg-a-surface-2 dark:hover:bg-a-dark-surface-2">
              <Avatar src={user?.photo} name={user?.name ?? "Admin"} size="sm" />
              <span className="hidden text-sm font-medium text-a-text dark:text-a-dark-text sm:block">{user?.name ?? "Admin"}</span>
            </button>
          }
        >
          <div className="border-b border-a-border px-4 py-3 dark:border-a-dark-border">
            <p className="text-sm font-semibold text-a-text dark:text-a-dark-text">{user?.name ?? "Admin"}</p>
            <p className="text-xs text-a-muted dark:text-a-dark-muted">{user?.role ?? "Staff"}</p>
          </div>
          <DropdownItem>
            <User size={15} /> My Profile
          </DropdownItem>
          <Link to="/admin/settings">
            <DropdownItem>
              <Settings size={15} /> Settings
            </DropdownItem>
          </Link>
          <DropdownItem className="text-rose-500" onClick={handleSignOut}>
            <LogOut size={15} /> Sign Out
          </DropdownItem>
        </Dropdown>
      </div>
    </header>
  );
}
