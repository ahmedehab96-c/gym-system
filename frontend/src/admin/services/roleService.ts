import { apiClient, type ApiItemResponse } from "./apiClient";
import type { PermissionModule, RolePermissions, StaffRole } from "../types";

interface PermissionPayload {
  module: string;
  canView: boolean;
  canCreate: boolean;
  canEdit: boolean;
  canDelete: boolean;
}

interface RolePermissionsPayload {
  role: StaffRole;
  permissions: PermissionPayload[];
}

function toPermission(p: PermissionPayload): PermissionModule {
  return { module: p.module, view: p.canView, create: p.canCreate, edit: p.canEdit, delete: p.canDelete };
}

function toRolePermissions(r: RolePermissionsPayload): RolePermissions {
  return { role: r.role, permissions: r.permissions.map(toPermission) };
}

function toPayload(p: PermissionModule): Record<string, unknown> {
  return { module: p.module, canView: p.view, canCreate: p.create, canEdit: p.edit, canDelete: p.delete };
}

export const roleService = {
  async list(signal?: AbortSignal): Promise<RolePermissions[]> {
    const response = await apiClient.get<ApiItemResponse<RolePermissionsPayload[]>>("/roles", { signal });
    return response.data.map(toRolePermissions);
  },

  async update(role: StaffRole, permissions: PermissionModule[]): Promise<RolePermissions> {
    const response = await apiClient.put<ApiItemResponse<RolePermissionsPayload>>(`/roles/${encodeURIComponent(role)}`, {
      permissions: permissions.map(toPayload),
    });
    return toRolePermissions(response.data);
  },
};
