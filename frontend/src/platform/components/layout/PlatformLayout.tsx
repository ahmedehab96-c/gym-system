import { useState } from "react";
import { Outlet } from "react-router-dom";
import { cn } from "../../../utils/cn";
import { useTheme } from "../../../admin/context/ThemeContext";
import { PlatformSidebar } from "./PlatformSidebar";
import { PlatformTopbar } from "./PlatformTopbar";

export function PlatformLayout() {
  const { theme } = useTheme();
  const [mobileOpen, setMobileOpen] = useState(false);

  return (
    <div className={cn("admin-root", theme === "dark" && "admin-dark")}>
      <div className="flex min-h-screen">
        <PlatformSidebar mobileOpen={mobileOpen} onCloseMobile={() => setMobileOpen(false)} />
        <div className="flex min-w-0 flex-1 flex-col">
          <PlatformTopbar onMenuClick={() => setMobileOpen(true)} />
          <main className="flex-1 px-4 py-5 sm:px-6 lg:px-8 lg:py-6">
            <Outlet />
          </main>
        </div>
      </div>
    </div>
  );
}
