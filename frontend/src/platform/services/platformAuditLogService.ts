import { apiClient, type ApiListResponse, type QueryParams } from "../../admin/services/apiClient";
import type { AuditLogEntry } from "../types";

export interface RawAuditLogEntry extends Omit<AuditLogEntry, "id" | "actorId" | "entityId" | "tenant"> {
  id: number;
  actorId: number | null;
  entityId: number | null;
  tenant: { id: number; name: string } | null;
}

export function toAuditLogEntry(raw: RawAuditLogEntry): AuditLogEntry {
  return {
    ...raw,
    id: String(raw.id),
    actorId: raw.actorId !== null ? String(raw.actorId) : null,
    entityId: raw.entityId !== null ? String(raw.entityId) : null,
    tenant: raw.tenant ? { id: String(raw.tenant.id), name: raw.tenant.name } : null,
  };
}

export interface AuditLogListParams {
  search?: string;
  action?: string;
  tenantId?: string;
  from?: string;
  to?: string;
  page?: number;
  perPage?: number;
}

export const platformAuditLogService = {
  async list(params: AuditLogListParams, signal?: AbortSignal): Promise<ApiListResponse<AuditLogEntry>> {
    const response = await apiClient.get<ApiListResponse<RawAuditLogEntry>>("/platform/audit-logs", {
      signal,
      params: {
        search: params.search,
        action: params.action,
        tenant_id: params.tenantId,
        from: params.from,
        to: params.to,
        page: params.page,
        per_page: params.perPage,
      } satisfies QueryParams,
    });
    return { data: response.data.map(toAuditLogEntry), meta: response.meta };
  },
};
