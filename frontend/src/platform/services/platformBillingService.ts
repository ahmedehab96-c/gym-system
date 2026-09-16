import { apiClient, type ApiListResponse, type QueryParams } from "../../admin/services/apiClient";
import type { PlatformInvoice, TenantRef } from "../types";

interface RawTenantRef extends Omit<TenantRef, "id"> {
  id: number;
}
interface RawInvoice extends Omit<PlatformInvoice, "id" | "tenant"> {
  id: number;
  tenant: RawTenantRef | null;
}

function toInvoice(raw: RawInvoice): PlatformInvoice {
  return { ...raw, id: String(raw.id), tenant: raw.tenant ? { ...raw.tenant, id: String(raw.tenant.id) } : null };
}

export interface BillingListParams {
  search?: string;
  status?: string;
  tenantId?: string;
  from?: string;
  to?: string;
  page?: number;
  perPage?: number;
}

export const platformBillingService = {
  async list(params: BillingListParams, signal?: AbortSignal): Promise<ApiListResponse<PlatformInvoice>> {
    const response = await apiClient.get<ApiListResponse<RawInvoice>>("/platform/billing", {
      signal,
      params: {
        search: params.search,
        status: params.status,
        tenant_id: params.tenantId,
        from: params.from,
        to: params.to,
        page: params.page,
        per_page: params.perPage,
      } satisfies QueryParams,
    });
    return { data: response.data.map(toInvoice), meta: response.meta };
  },
};
