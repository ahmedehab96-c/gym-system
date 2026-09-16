import { apiClient, type ApiItemResponse, type ApiListResponse, type ApiMeta, type QueryParams } from "./apiClient";
import type { Expense } from "../types";

export interface ExpensePayload {
  id: number;
  title: string;
  category: Expense["category"];
  amount: number;
  date: string;
  vendor: string | null;
  notes: string | null;
  receipt: string | null;
}

export function toExpense(e: ExpensePayload): Expense {
  return {
    id: String(e.id),
    title: e.title,
    category: e.category,
    amount: e.amount,
    date: e.date,
    vendor: e.vendor ?? "",
    notes: e.notes ?? "",
    receipt: e.receipt ?? "",
  };
}

export interface ExpenseListParams {
  search?: string;
  category?: string;
  dateFrom?: string;
  dateTo?: string;
  page?: number;
  perPage?: number;
}

function toQuery(params: ExpenseListParams): QueryParams {
  return {
    search: params.search,
    category: params.category,
    date_from: params.dateFrom,
    date_to: params.dateTo,
    page: params.page,
    per_page: params.perPage,
  };
}

export interface ExpenseInput {
  title: string;
  category: Expense["category"];
  amount: number;
  date?: string;
  vendor?: string;
  notes?: string;
}

function toRequestBody(input: ExpenseInput): Record<string, unknown> {
  return {
    title: input.title,
    category: input.category,
    amount: input.amount,
    date: input.date || undefined,
    vendor: input.vendor || undefined,
    notes: input.notes || undefined,
  };
}

export interface ExpenseStats {
  total: number;
  thisMonth: number;
  totalRecords: number;
  largestCategory: string | null;
  byCategory: { category: string; amount: number }[];
}

export const expenseService = {
  async list(params: ExpenseListParams = {}, signal?: AbortSignal): Promise<{ data: Expense[]; meta: ApiMeta }> {
    const response = await apiClient.get<ApiListResponse<ExpensePayload>>("/expenses", { params: toQuery(params), signal });
    return { data: response.data.map(toExpense), meta: response.meta };
  },

  async stats(signal?: AbortSignal): Promise<ExpenseStats> {
    const response = await apiClient.get<ApiItemResponse<ExpenseStats>>("/expenses/stats", { signal });
    return response.data;
  },

  async create(input: ExpenseInput): Promise<Expense> {
    const response = await apiClient.post<ApiItemResponse<ExpensePayload>>("/expenses", toRequestBody(input));
    return toExpense(response.data);
  },

  async update(id: string, input: Partial<ExpenseInput>): Promise<Expense> {
    const body: Record<string, unknown> = {};
    if (input.title !== undefined) body.title = input.title;
    if (input.category !== undefined) body.category = input.category;
    if (input.amount !== undefined) body.amount = input.amount;
    if (input.date !== undefined) body.date = input.date;
    if (input.vendor !== undefined) body.vendor = input.vendor;
    if (input.notes !== undefined) body.notes = input.notes;

    const response = await apiClient.put<ApiItemResponse<ExpensePayload>>(`/expenses/${id}`, body);
    return toExpense(response.data);
  },

  async remove(id: string): Promise<void> {
    await apiClient.delete(`/expenses/${id}`);
  },

  async uploadReceipt(id: string, file: File): Promise<string> {
    const formData = new FormData();
    formData.append("receipt", file);
    const response = await apiClient.post<ApiItemResponse<ExpensePayload>>(`/expenses/${id}/receipt`, formData);
    return response.data.receipt ?? "";
  },
};
