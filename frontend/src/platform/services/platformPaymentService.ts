import { apiClient, type ApiItemResponse, type ApiListResponse, type QueryParams } from "../../admin/services/apiClient";
import type { TenantRef } from "../types";

export type TransactionStatus = "Pending" | "Paid" | "Failed" | "Refunded";
export type TransactionType = "Charge" | "Refund";

export interface PaymentTransaction {
  id: string;
  gateway: string;
  type: TransactionType;
  reference: string;
  amount: number;
  currency: string;
  status: TransactionStatus;
  paidAt: string | null;
  planName: string | null;
  billingCycle: string | null;
  invoiceNumber: string | null;
  tenant: TenantRef | null;
  createdAt: string;
}

export interface RevenueBreakdownSlice {
  plan?: string;
  cycle?: string;
  revenue: number;
}

export interface PaymentStats {
  totalRevenue: number;
  totalRefunded: number;
  netRevenue: number;
  successfulPayments: number;
  failedPayments: number;
  refundCount: number;
  revenueByPlan: RevenueBreakdownSlice[];
  revenueByBillingCycle: RevenueBreakdownSlice[];
}

interface RawTenantRef extends Omit<TenantRef, "id"> {
  id: number;
}
interface RawTransaction extends Omit<PaymentTransaction, "id" | "tenant"> {
  id: number;
  tenant: RawTenantRef | null;
}
function toTransaction(raw: RawTransaction): PaymentTransaction {
  return { ...raw, id: String(raw.id), tenant: raw.tenant ? { ...raw.tenant, id: String(raw.tenant.id) } : null };
}

export interface TransactionListParams {
  search?: string;
  status?: string;
  type?: string;
  tenantId?: string;
  from?: string;
  to?: string;
  page?: number;
  perPage?: number;
}

export const platformPaymentService = {
  async list(params: TransactionListParams, signal?: AbortSignal): Promise<ApiListResponse<PaymentTransaction>> {
    const response = await apiClient.get<ApiListResponse<RawTransaction>>("/platform/billing/transactions", {
      signal,
      params: {
        status: params.status,
        type: params.type,
        tenant_id: params.tenantId,
        from: params.from,
        to: params.to,
        page: params.page,
        per_page: params.perPage,
      } satisfies QueryParams,
    });
    return { data: response.data.map(toTransaction), meta: response.meta };
  },

  async stats(signal?: AbortSignal): Promise<ApiItemResponse<PaymentStats>> {
    return apiClient.get<ApiItemResponse<PaymentStats>>("/platform/billing/stats", { signal });
  },

  async refund(transactionId: string, amount?: number): Promise<ApiItemResponse<PaymentTransaction>> {
    const response = await apiClient.post<ApiItemResponse<RawTransaction>>(`/platform/billing/transactions/${transactionId}/refund`, {
      amount,
    });
    return { data: toTransaction(response.data) };
  },
};
