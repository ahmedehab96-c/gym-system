import { Navigate, Outlet, useLocation } from "react-router-dom";
import { useAuth } from "../../context/AuthContext";
import { navSections } from "../../data/navigation";

// Same path -> permission module mapping the Sidebar uses to hide nav links,
// applied here so navigating straight to a URL is gated too. Detail routes
// (e.g. /admin/members/:id) aren't in navSections, so they're added
// explicitly, matched by prefix.
const modulesByPath = new Map(
  navSections.flatMap((section) => section.items).filter((item) => item.module).map((item) => [item.path, item.module as string]),
);
const modulesByPrefix: [string, string][] = [
  ["/admin/members/", "Members"],
  ["/admin/trainers/", "Trainers"],
];

function moduleForPath(pathname: string): string | undefined {
  const exact = modulesByPath.get(pathname);
  if (exact) return exact;
  return modulesByPrefix.find(([prefix]) => pathname.startsWith(prefix))?.[1];
}

export function ProtectedRoute() {
  const { isAuthenticated, hasPermission } = useAuth();
  const location = useLocation();

  if (!isAuthenticated) {
    return <Navigate to="/admin/login" replace state={{ from: location.pathname }} />;
  }

  const module = moduleForPath(location.pathname);
  if (module && !hasPermission(module, "view")) {
    return <Navigate to="/admin/dashboard" replace />;
  }

  return <Outlet />;
}
