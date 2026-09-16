import { apiClient, type ApiListResponse, type QueryParams } from "../../admin/services/apiClient";
import type { PlatformPlan, PlatformSubscription, TenantRef } from "../types";

interface RawPlatformPlan extends Omit<PlatformPlan, "id"> {
  id: number;
}
interface RawTenantRef extends Omit<TenantRef, "id"> {
  id: number;
}
interface RawSubscription extends Omit<PlatformSubscription, "id" | "plan" | "tenant"> {
  id: number;
  plan: RawPlatformPlan | null;
  tenant: RawTenantRef | null;
}

function toSubscription(raw: RawSubscription): PlatformSubscription {
  return {
    ...raw,
    id: String(raw.id),
    plan: raw.plan ? { ...raw.plan, id: String(raw.plan.id) } : null,
    tenant: raw.tenant ? { ...raw.tenant, id: String(raw.tenant.id) } : null,
  };
}

export interface SubscriptionListParams {
  search?: string;
  status?: string;
  planId?: string;
  tenantId?: string;
  billingCycle?: string;
  page?: number;
  perPage?: number;
}

export const platformSubscriptionService = {
  async list(params: SubscriptionListParams, signal?: AbortSignal): Promise<ApiListResponse<PlatformSubscription>> {
    const response = await apiClient.get<ApiListResponse<RawSubscription>>("/platform/subscriptions", {
      signal,
      params: {
        search: params.search,
        status: params.status,
        plan_id: params.planId,
        tenant_id: params.tenantId,
        billing_cycle: params.billingCycle,
        page: params.page,
        per_page: params.perPage,
      } satisfies QueryParams,
    });
    return { data: response.data.map(toSubscription), meta: response.meta };
  },
};
