import { useEffect, useState } from "react";
import { Check, X } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { Tabs } from "../components/ui/Tabs";
import { Button } from "../components/ui/Button";
import { LoadingState } from "../components/ui/LoadingState";
import { ErrorState } from "../components/ui/ErrorState";
import { roleService } from "../services/roleService";
import type { RolePermissions, StaffRole } from "../types";
import { useToast } from "../context/ToastContext";
import { ApiError } from "../services/apiClient";
import { cn } from "../../utils/cn";

const roles: StaffRole[] = ["Super Admin", "Admin", "Manager", "Receptionist", "Trainer", "Accountant"];
const actions: ("view" | "create" | "edit" | "delete")[] = ["view", "create", "edit", "delete"];

export default function RolesPermissions() {
  const [permissions, setPermissions] = useState<RolePermissions[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [role, setRole] = useState<StaffRole>("Super Admin");
  const { showToast } = useToast();

  function load() {
    setLoading(true);
    setError(null);
    roleService
      .list()
      .then(setPermissions)
      .catch((err) => setError(err instanceof ApiError ? err.message : "Something went wrong. Please try again."))
      .finally(() => setLoading(false));
  }

  useEffect(load, []);

  const current = permissions.find((p) => p.role === role);

  function toggle(module: string, action: (typeof actions)[number]) {
    if (role === "Super Admin") return;
    setPermissions((prev) =>
      prev.map((p) =>
        p.role === role
          ? { ...p, permissions: p.permissions.map((m) => (m.module === module ? { ...m, [action]: !m[action] } : m)) }
          : p,
      ),
    );
  }

  async function save() {
    if (!current) return;
    setSaving(true);
    try {
      await roleService.update(role, current.permissions);
      showToast("Permissions saved");
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not save permissions.", "error");
    } finally {
      setSaving(false);
    }
  }

  return (
    <div>
      <PageHeader
        title="Roles & Permissions"
        description="Control what each role can access"
        action={<Button disabled={saving || role === "Super Admin"} onClick={save}>{saving ? "Saving..." : "Save Changes"}</Button>}
      />

      <div className="mb-5 overflow-x-auto"><Tabs tabs={roles} active={role} onChange={(r) => setRole(r as StaffRole)} /></div>

      {loading ? (
        <LoadingState rows={6} />
      ) : error ? (
        <ErrorState message={error} onRetry={load} />
      ) : !current ? (
        <ErrorState message="No permissions found for this role." onRetry={load} />
      ) : (
        <div className="admin-card overflow-x-auto rounded-2xl p-4 shadow-sm">
          <table className="w-full min-w-[560px] border-collapse text-sm">
            <thead>
              <tr className="border-b border-a-border dark:border-a-dark-border">
                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-a-muted dark:text-a-dark-muted">Module</th>
                {actions.map((a) => (
                  <th key={a} className="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-a-muted dark:text-a-dark-muted">{a}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {current.permissions.map((m) => (
                <tr key={m.module} className="border-b border-a-border/60 last:border-0 dark:border-a-dark-border/60">
                  <td className="px-4 py-3 font-medium text-a-text dark:text-a-dark-text">{m.module}</td>
                  {actions.map((a) => (
                    <td key={a} className="px-4 py-3 text-center">
                      <button
                        onClick={() => toggle(m.module, a)}
                        disabled={role === "Super Admin"}
                        className={cn(
                          "mx-auto flex h-6 w-6 items-center justify-center rounded-md transition-colors disabled:cursor-not-allowed",
                          m[a] ? "bg-emerald-500/15 text-emerald-500" : "bg-a-surface-2 text-a-muted dark:bg-a-dark-surface-2 dark:text-a-dark-muted",
                        )}
                      >
                        {m[a] ? <Check size={13} /> : <X size={13} />}
                      </button>
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
