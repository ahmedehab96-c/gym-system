import { apiClient, type ApiItemResponse, type ApiListResponse, type ApiMeta, type QueryParams } from "./apiClient";
import type { Payment } from "../types";

export interface PaymentPayload {
  id: number;
  reference: string;
  invoiceId: number | null;
  memberId: number;
  memberName: string | null;
  memberAvatar: string | null;
  amount: number;
  method: Payment["method"];
  date: string;
  status: Payment["status"];
}

export function toPayment(p: PaymentPayload): Payment {
  return {
    id: String(p.id),
    invoiceId: p.invoiceId !== null ? String(p.invoiceId) : "",
    memberId: String(p.memberId),
    memberName: p.memberName ?? "",
    memberAvatar: p.memberAvatar ?? "",
    amount: p.amount,
    method: p.method,
    date: p.date,
    status: p.status,
  };
}

export interface PaymentListParams {
  search?: string;
  status?: string;
  method?: string;
  memberId?: string;
  invoiceId?: string;
  dateFrom?: string;
  dateTo?: string;
  page?: number;
  perPage?: number;
}

function toQuery(params: PaymentListParams): QueryParams {
  return {
    search: params.search,
    status: params.status,
    method: params.method,
    member_id: params.memberId,
    invoice_id: params.invoiceId,
    date_from: params.dateFrom,
    date_to: params.dateTo,
    page: params.page,
    per_page: params.perPage,
  };
}

export interface PaymentInput {
  memberId: string;
  invoiceId?: string;
  amount: number;
  method?: Payment["method"];
  date?: string;
  status?: Payment["status"];
}

function toRequestBody(input: PaymentInput): Record<string, unknown> {
  return {
    member_id: Number(input.memberId),
    invoice_id: input.invoiceId ? Number(input.invoiceId) : undefined,
    amount: input.amount,
    method: input.method,
    date: input.date || undefined,
    status: input.status,
  };
}

export interface PaymentStats {
  todayRevenue: number;
  monthlyRevenue: number;
  pending: number;
  refunds: number;
}

export const paymentService = {
  async list(params: PaymentListParams = {}, signal?: AbortSignal): Promise<{ data: Payment[]; meta: ApiMeta }> {
    const response = await apiClient.get<ApiListResponse<PaymentPayload>>("/payments", { params: toQuery(params), signal });
    return { data: response.data.map(toPayment), meta: response.meta };
  },

  async stats(signal?: AbortSignal): Promise<PaymentStats> {
    const response = await apiClient.get<ApiItemResponse<PaymentStats>>("/payments/stats", { signal });
    return response.data;
  },

  async create(input: PaymentInput): Promise<Payment> {
    const response = await apiClient.post<ApiItemResponse<PaymentPayload>>("/payments", toRequestBody(input));
    return toPayment(response.data);
  },

  async update(id: string, input: Partial<PaymentInput>): Promise<Payment> {
    const body: Record<string, unknown> = {};
    if (input.memberId !== undefined) body.member_id = Number(input.memberId);
    if (input.invoiceId !== undefined) body.invoice_id = input.invoiceId ? Number(input.invoiceId) : null;
    if (input.amount !== undefined) body.amount = input.amount;
    if (input.method !== undefined) body.method = input.method;
    if (input.date !== undefined) body.date = input.date;
    if (input.status !== undefined) body.status = input.status;

    const response = await apiClient.put<ApiItemResponse<PaymentPayload>>(`/payments/${id}`, body);
    return toPayment(response.data);
  },

  async remove(id: string): Promise<void> {
    await apiClient.delete(`/payments/${id}`);
  },

  async refund(id: string): Promise<Payment> {
    const response = await apiClient.post<ApiItemResponse<PaymentPayload>>(`/payments/${id}/refund`);
    return toPayment(response.data);
  },
};
