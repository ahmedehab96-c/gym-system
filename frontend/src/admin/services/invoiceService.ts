import { apiClient, type ApiItemResponse, type ApiListResponse, type ApiMeta, type QueryParams } from "./apiClient";
import type { Invoice, InvoiceBusiness, InvoiceItem } from "../types";

interface InvoicePayload {
  id: number;
  invoiceNumber: string;
  memberId: number;
  memberName: string | null;
  memberAvatar: string | null;
  issueDate: string;
  dueDate: string;
  items: InvoiceItem[];
  subtotal: number;
  discount: number;
  total: number;
  amountPaid: number | null;
  balanceDue: number | null;
  status: Invoice["status"];
  business: InvoiceBusiness;
}

function toInvoice(i: InvoicePayload): Invoice {
  return {
    id: String(i.id),
    invoiceNumber: i.invoiceNumber,
    memberId: String(i.memberId),
    memberName: i.memberName ?? "",
    memberAvatar: i.memberAvatar ?? "",
    issueDate: i.issueDate,
    dueDate: i.dueDate,
    items: i.items,
    subtotal: i.subtotal,
    discount: i.discount,
    total: i.total,
    amountPaid: i.amountPaid,
    balanceDue: i.balanceDue,
    status: i.status,
    business: i.business,
  };
}

export interface InvoiceListParams {
  search?: string;
  status?: string;
  memberId?: string;
  dateFrom?: string;
  dateTo?: string;
  page?: number;
  perPage?: number;
}

function toQuery(params: InvoiceListParams): QueryParams {
  return {
    search: params.search,
    status: params.status,
    member_id: params.memberId,
    date_from: params.dateFrom,
    date_to: params.dateTo,
    page: params.page,
    per_page: params.perPage,
  };
}

export interface InvoiceInput {
  memberId: string;
  issueDate?: string;
  dueDate: string;
  discount?: number;
  status?: Invoice["status"];
  items: InvoiceItem[];
}

function toRequestBody(input: InvoiceInput): Record<string, unknown> {
  return {
    member_id: Number(input.memberId),
    issue_date: input.issueDate || undefined,
    due_date: input.dueDate,
    discount: input.discount,
    status: input.status,
    items: input.items,
  };
}

export const invoiceService = {
  async list(params: InvoiceListParams = {}, signal?: AbortSignal): Promise<{ data: Invoice[]; meta: ApiMeta }> {
    const response = await apiClient.get<ApiListResponse<InvoicePayload>>("/invoices", { params: toQuery(params), signal });
    return { data: response.data.map(toInvoice), meta: response.meta };
  },

  async create(input: InvoiceInput): Promise<Invoice> {
    const response = await apiClient.post<ApiItemResponse<InvoicePayload>>("/invoices", toRequestBody(input));
    return toInvoice(response.data);
  },

  async update(id: string, input: Partial<InvoiceInput>): Promise<Invoice> {
    const response = await apiClient.put<ApiItemResponse<InvoicePayload>>(`/invoices/${id}`, {
      member_id: input.memberId ? Number(input.memberId) : undefined,
      issue_date: input.issueDate,
      due_date: input.dueDate,
      discount: input.discount,
      status: input.status,
      items: input.items,
    });
    return toInvoice(response.data);
  },

  async remove(id: string): Promise<void> {
    await apiClient.delete(`/invoices/${id}`);
  },
};
