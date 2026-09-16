import { NavLink } from "react-router-dom";
import { ShieldCheck, X } from "lucide-react";
import { platformNavSections } from "../../data/navigation";
import { cn } from "../../../utils/cn";

interface PlatformSidebarProps {
  mobileOpen: boolean;
  onCloseMobile: () => void;
}

export function PlatformSidebar({ mobileOpen, onCloseMobile }: PlatformSidebarProps) {
  return (
    <>
      {mobileOpen && <div className="fixed inset-0 z-40 bg-black/50 lg:hidden" onClick={onCloseMobile} />}
      <aside
        className={cn(
          "fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-a-border bg-a-surface transition-transform duration-300 dark:border-a-dark-border dark:bg-a-dark-surface lg:sticky lg:top-0 lg:h-screen lg:translate-x-0",
          mobileOpen ? "translate-x-0" : "-translate-x-full",
        )}
      >
        <div className="flex items-center justify-between px-5 py-5">
          <div className="flex items-center gap-2.5">
            <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-sky-500 to-indigo-500 text-white">
              <ShieldCheck size={18} strokeWidth={2.5} />
            </div>
            <div>
              <p className="text-sm font-bold leading-none text-a-text dark:text-a-dark-text">Platform Admin</p>
              <p className="mt-0.5 text-[11px] text-a-muted dark:text-a-dark-muted">Super Admin Console</p>
            </div>
          </div>
          <button onClick={onCloseMobile} className="text-a-muted lg:hidden">
            <X size={18} />
          </button>
        </div>

        <nav className="flex-1 space-y-5 overflow-y-auto px-3 pb-6">
          {platformNavSections.map((section) => (
            <div key={section.title}>
              <p className="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-a-muted/70 dark:text-a-dark-muted/70">
                {section.title}
              </p>
              <div className="space-y-0.5">
                {section.items.map((item) => (
                  <NavLink
                    key={item.path}
                    to={item.path}
                    onClick={onCloseMobile}
                    className={({ isActive }) =>
                      cn(
                        "flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition-colors",
                        isActive
                          ? "bg-sky-500/10 text-sky-600 dark:text-sky-400"
                          : "text-a-muted hover:bg-a-surface-2 hover:text-a-text dark:text-a-dark-muted dark:hover:bg-a-dark-surface-2 dark:hover:text-a-dark-text",
                      )
                    }
                  >
                    <item.icon size={17} strokeWidth={2} />
                    {item.label}
                  </NavLink>
                ))}
              </div>
            </div>
          ))}
        </nav>
      </aside>
    </>
  );
}
