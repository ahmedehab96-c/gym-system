import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { Bell, LogOut, Menu, Moon, Settings, Sun, User } from "lucide-react";
import { useTheme } from "../../../admin/context/ThemeContext";
import { useAuth } from "../../../admin/context/AuthContext";
import { Avatar } from "../../../admin/components/ui/Avatar";
import { Dropdown, DropdownItem } from "../../../admin/components/ui/Dropdown";
import { timeAgo } from "../../../admin/utils/format";
import { platformAuditLogService } from "../../services/platformAuditLogService";
import type { AuditLogEntry } from "../../types";

export function PlatformTopbar({ onMenuClick }: { onMenuClick: () => void }) {
  const { theme, toggleTheme } = useTheme();
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [recentActivity, setRecentActivity] = useState<AuditLogEntry[]>([]);

  useEffect(() => {
    platformAuditLogService
      .list({ perPage: 5 })
      .then(({ data }) => setRecentActivity(data))
      .catch(() => undefined);
  }, []);

  function handleSignOut() {
    logout();
    navigate("/admin/login", { replace: true });
  }

  return (
    <header className="sticky top-0 z-30 flex items-center gap-3 border-b border-a-border bg-a-surface/90 px-4 py-3 backdrop-blur dark:border-a-dark-border dark:bg-a-dark-surface/90 sm:px-6">
      <button onClick={onMenuClick} className="text-a-muted lg:hidden">
        <Menu size={20} />
      </button>

      <div className="flex-1" />

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
            </button>
          }
          className="w-80 max-h-96 overflow-y-auto"
        >
          <div className="flex items-center justify-between border-b border-a-border px-4 py-3 dark:border-a-dark-border">
            <p className="text-sm font-semibold text-a-text dark:text-a-dark-text">Platform Activity</p>
            <Link to="/platform/audit-logs" className="text-xs font-medium text-sky-600 dark:text-sky-400">View all</Link>
          </div>
          {recentActivity.length === 0 && (
            <p className="px-4 py-6 text-center text-xs text-a-muted dark:text-a-dark-muted">No recent activity.</p>
          )}
          {recentActivity.map((entry) => (
            <div key={entry.id} className="border-b border-a-border/60 px-4 py-3 last:border-0 dark:border-a-dark-border/60">
              <p className="text-xs font-semibold text-a-text dark:text-a-dark-text">{entry.description}</p>
              <p className="mt-1 text-[10px] text-a-muted/70 dark:text-a-dark-muted/70">{entry.actorName} · {timeAgo(entry.createdAt)}</p>
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
            <p className="text-xs text-a-muted dark:text-a-dark-muted">Platform Admin</p>
          </div>
          <DropdownItem>
            <User size={15} /> My Profile
          </DropdownItem>
          <Link to="/platform/users">
            <DropdownItem>
              <Settings size={15} /> Platform Users
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
