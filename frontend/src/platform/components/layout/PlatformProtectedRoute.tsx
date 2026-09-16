import { Navigate, Outlet, useLocation } from "react-router-dom";
import { useAuth } from "../../../admin/context/AuthContext";

/**
 * Frontend gate for every /platform/* route: only an authenticated user
 * with isPlatformAdmin=true gets in. A regular gym Admin/Trainer/
 * Receptionist/Accountant is bounced to their own dashboard — the real
 * enforcement is still the `platform.admin` middleware on every backend
 * endpoint (App\Http\Middleware\EnsurePlatformAdmin); this only avoids
 * rendering a Platform Admin screen that would fail on every request.
 */
export function PlatformProtectedRoute() {
  const { isAuthenticated, user, isBootstrapping } = useAuth();
  const location = useLocation();

  if (!isAuthenticated) {
    return <Navigate to="/admin/login" replace state={{ from: location.pathname }} />;
  }

  if (isBootstrapping) {
    return null;
  }

  if (!user?.isPlatformAdmin) {
    return <Navigate to="/admin/dashboard" replace />;
  }

  return <Outlet />;
}
