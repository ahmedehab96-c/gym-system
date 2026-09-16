import { createContext, useContext, useEffect, useState, type ReactNode } from "react";
import { authService, type AuthUser } from "../services/authService";
import { setUnauthorizedHandler } from "../services/apiClient";

// Token/user persist under these keys so a future fetch/axios client only
// needs to read TOKEN_KEY and attach `Authorization: Bearer ${token}` — see
// services/apiClient.ts, which does exactly that.
const TOKEN_KEY = "gym_auth_token";
const USER_KEY = "gym_auth_user";

interface AuthContextValue {
  user: AuthUser | null;
  token: string | null;
  isAuthenticated: boolean;
  /** True until the stored token (if any) has been verified against the API on first load. */
  isBootstrapping: boolean;
  isSubmitting: boolean;
  error: string | null;
  login: (email: string, password: string) => Promise<AuthUser | null>;
  logout: () => void;
  /**
   * Mirrors the backend's `permission:module,ability` middleware: true if the
   * user's role has this ability on this module. A module with no row in the
   * matrix (e.g. Dashboard, Notifications) isn't gated on the backend either,
   * so it defaults to true here.
   */
  hasPermission: (module: string, ability?: "view" | "create" | "edit" | "delete") => boolean;
}

const AuthContext = createContext<AuthContextValue | null>(null);

function readStoredUser(): AuthUser | null {
  try {
    const raw = window.localStorage.getItem(USER_KEY);
    return raw ? (JSON.parse(raw) as AuthUser) : null;
  } catch {
    return null;
  }
}

export function AuthProvider({ children }: { children: ReactNode }) {
  const [token, setToken] = useState<string | null>(() => window.localStorage.getItem(TOKEN_KEY));
  const [user, setUser] = useState<AuthUser | null>(() => readStoredUser());
  const [isBootstrapping, setIsBootstrapping] = useState(() => Boolean(window.localStorage.getItem(TOKEN_KEY)));
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  function clearSession() {
    window.localStorage.removeItem(TOKEN_KEY);
    window.localStorage.removeItem(USER_KEY);
    setToken(null);
    setUser(null);
  }

  // A 401 from any API call (expired/revoked token) clears the session so
  // ProtectedRoute redirects to /admin/login on the next render.
  useEffect(() => {
    setUnauthorizedHandler(clearSession);
    return () => setUnauthorizedHandler(null);
  }, []);

  // Restore session on refresh: a token in storage is trusted optimistically
  // (isAuthenticated stays true immediately), but is verified in the
  // background — an invalid/expired token 401s and clears itself via the
  // handler above.
  useEffect(() => {
    if (!token) return;
    let cancelled = false;

    authService
      .me()
      .then((freshUser) => {
        if (cancelled) return;
        setUser(freshUser);
        window.localStorage.setItem(USER_KEY, JSON.stringify(freshUser));
      })
      .catch(() => {
        // 401 is already handled by the unauthorized handler above; other
        // errors (e.g. offline) just keep the optimistic cached user.
      })
      .finally(() => {
        if (!cancelled) setIsBootstrapping(false);
      });

    return () => {
      cancelled = true;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  async function login(email: string, password: string): Promise<AuthUser | null> {
    setIsSubmitting(true);
    setError(null);
    try {
      const result = await authService.login(email, password);
      window.localStorage.setItem(TOKEN_KEY, result.token);
      window.localStorage.setItem(USER_KEY, JSON.stringify(result.user));
      setToken(result.token);
      setUser(result.user);
      setIsBootstrapping(false);
      return result.user;
    } catch (e) {
      setError(e instanceof Error ? e.message : "Login failed.");
      return null;
    } finally {
      setIsSubmitting(false);
    }
  }

  function logout() {
    void authService.logout();
    clearSession();
  }

  function hasPermission(module: string, ability: "view" | "create" | "edit" | "delete" = "view"): boolean {
    const entry = user?.permissions?.find((p) => p.module === module);
    if (!entry) return true;
    return entry[ability];
  }

  return (
    <AuthContext.Provider
      value={{ user, token, isAuthenticated: Boolean(token), isBootstrapping, isSubmitting, error, login, logout, hasPermission }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used within AuthProvider");
  return ctx;
}
