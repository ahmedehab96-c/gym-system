// Talks to Laravel Sanctum's token-based auth endpoints. Response shapes
// match `App\Http\Resources\UserResource` (`{data: {...}}` for /auth/me,
// `{data: {token, user}}` for /auth/login).
import { apiClient, type ApiItemResponse } from "./apiClient";
import type { PermissionModule, StaffRole } from "../types";

export interface AuthUser {
  id: string;
  name: string;
  email: string;
  role: StaffRole;
  photo: string;
  permissions: PermissionModule[];
  /** Platform-level Super Admin (manages every gym), not a single gym's own staff. See Phase 21. */
  isPlatformAdmin: boolean;
}

export interface LoginResult {
  token: string;
  user: AuthUser;
}

interface PermissionPayload {
  module: string;
  canView: boolean;
  canCreate: boolean;
  canEdit: boolean;
  canDelete: boolean;
}

interface UserPayload {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  photo: string | null;
  role: StaffRole;
  status: string;
  lastLoginAt: string | null;
  permissions: PermissionPayload[];
  isPlatformAdmin?: boolean;
}

function toAuthUser(payload: UserPayload): AuthUser {
  return {
    id: String(payload.id),
    name: payload.name,
    email: payload.email,
    role: payload.role,
    photo: payload.photo ?? "",
    permissions: (payload.permissions ?? []).map((p) => ({
      module: p.module,
      view: p.canView,
      create: p.canCreate,
      edit: p.canEdit,
      delete: p.canDelete,
    })),
    isPlatformAdmin: payload.isPlatformAdmin ?? false,
  };
}

export const authService = {
  async login(email: string, password: string): Promise<LoginResult> {
    const response = await apiClient.post<ApiItemResponse<{ token: string; user: UserPayload }>>("/auth/login", {
      email,
      password,
    });
    return { token: response.data.token, user: toAuthUser(response.data.user) };
  },

  async logout(): Promise<void> {
    await apiClient.post("/auth/logout");
  },

  /** Validates the stored token and fetches the current user — used to restore a session on page load. */
  async me(): Promise<AuthUser> {
    const response = await apiClient.get<ApiItemResponse<UserPayload>>("/auth/me");
    return toAuthUser(response.data);
  },
};
