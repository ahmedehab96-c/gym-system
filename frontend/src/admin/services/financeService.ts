import { apiClient, type ApiItemResponse } from "./apiClient";

export interface RevenueMonth {
  month: string;
  revenue: number;
  expenses: number;
}

export interface FinanceOverview {
  daily: number;
  weekly: number;
  monthly: number;
  yearly: number;
  totalRevenue: number;
  totalExpenses: number;
  netRevenue: number;
  pendingPayments: { count: number; amount: number };
  refunds: { count: number; amount: number };
  revenueByMethod: { method: string; amount: number }[];
}

export const financeService = {
  async overview(signal?: AbortSignal): Promise<FinanceOverview> {
    const response = await apiClient.get<ApiItemResponse<FinanceOverview>>("/finance/overview", { signal });
    return response.data;
  },

  async revenueOverTime(months = 12, signal?: AbortSignal): Promise<RevenueMonth[]> {
    const response = await apiClient.get<ApiItemResponse<RevenueMonth[]>>("/finance/revenue-over-time", {
      params: { months },
      signal,
    });
    return response.data;
  },
};
