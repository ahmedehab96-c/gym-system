import { useState, type FormEvent } from "react";
import { Navigate, useLocation, useNavigate } from "react-router-dom";
import { Dumbbell, Mail, Lock, Eye, EyeOff, Sun, Moon, LogIn } from "lucide-react";
import { Button } from "../components/ui/Button";
import { Input } from "../components/ui/Input";
import { useAuth } from "../context/AuthContext";
import { useTheme } from "../context/ThemeContext";
import { cn } from "../../utils/cn";

export default function Login() {
  const { isAuthenticated, user, login, isSubmitting, error } = useAuth();
  const { theme, toggleTheme } = useTheme();
  const navigate = useNavigate();
  const location = useLocation();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);

  const explicitRedirect = (location.state as { from?: string } | null)?.from;
  const defaultDashboard = (u: typeof user) => (u?.isPlatformAdmin ? "/platform/dashboard" : "/admin/dashboard");
  const redirectTo = explicitRedirect || defaultDashboard(user);

  if (isAuthenticated) {
    return <Navigate to={redirectTo} replace />;
  }

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    const loggedInUser = await login(email, password);
    if (loggedInUser) navigate(explicitRedirect || defaultDashboard(loggedInUser), { replace: true });
  }

  return (
    <div className={cn("admin-root flex min-h-screen items-center justify-center bg-a-surface-2 px-4 py-10 dark:bg-a-dark-surface-2", theme === "dark" && "admin-dark")}>
      <button
        onClick={toggleTheme}
        aria-label="Toggle theme"
        className="fixed right-5 top-5 flex h-10 w-10 items-center justify-center rounded-xl border border-a-border bg-a-surface text-a-muted transition-colors hover:text-a-text dark:border-a-dark-border dark:bg-a-dark-surface dark:text-a-dark-muted dark:hover:text-a-dark-text"
      >
        {theme === "dark" ? <Sun size={18} /> : <Moon size={18} />}
      </button>

      <div className="w-full max-w-[420px]">
        <div className="mb-8 flex flex-col items-center gap-3 text-center">
          <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-a-accent to-a-accent-2 text-black shadow-[0_8px_24px_-8px_rgba(212,167,47,0.6)]">
            <Dumbbell size={22} strokeWidth={2.5} />
          </div>
          <div>
            <p className="text-lg font-bold text-a-text dark:text-a-dark-text">Premium Gym</p>
            <p className="text-xs text-a-muted dark:text-a-dark-muted">Admin Console</p>
          </div>
        </div>

        <div className="admin-card rounded-2xl p-7 shadow-xl sm:p-8">
          <h1 className="text-lg font-semibold text-a-text dark:text-a-dark-text">Sign in to your account</h1>
          <p className="mt-1 text-sm text-a-muted dark:text-a-dark-muted">Staff and admin access only.</p>

          <form onSubmit={handleSubmit} className="mt-6 flex flex-col gap-4">
            <Input
              label="Email address"
              type="email"
              icon={<Mail size={15} />}
              placeholder="you@premiumgym.com"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              autoComplete="username"
              required
            />
            <Input
              label="Password"
              type={showPassword ? "text" : "password"}
              icon={<Lock size={15} />}
              rightElement={
                <button
                  type="button"
                  onClick={() => setShowPassword((s) => !s)}
                  className="hover:text-a-text dark:hover:text-a-dark-text"
                  aria-label={showPassword ? "Hide password" : "Show password"}
                >
                  {showPassword ? <EyeOff size={15} /> : <Eye size={15} />}
                </button>
              }
              placeholder="••••••••"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              autoComplete="current-password"
              required
            />

            {error && (
              <p className="rounded-xl border border-rose-500/30 bg-rose-500/10 px-3.5 py-2.5 text-xs font-medium text-rose-500">
                {error}
              </p>
            )}

            <Button type="submit" size="lg" icon={<LogIn size={16} />} disabled={isSubmitting} className="mt-1 w-full justify-center">
              {isSubmitting ? "Signing in..." : "Sign In"}
            </Button>
          </form>
        </div>
      </div>
    </div>
  );
}
