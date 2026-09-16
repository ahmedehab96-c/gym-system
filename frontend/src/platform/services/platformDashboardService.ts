import { apiClient, type ApiItemResponse } from "../../admin/services/apiClient";
import type { PlatformActivity, PlatformCharts, PlatformSummary } from "../types";

export const platformDashboardService = {
  async summary(signal?: AbortSignal): Promise<PlatformSummary> {
    const response = await apiClient.get<ApiItemResponse<PlatformSummary>>("/platform/dashboard/summary", { signal });
    return response.data;
  },

  async charts(signal?: AbortSignal): Promise<PlatformCharts> {
    const response = await apiClient.get<ApiItemResponse<PlatformCharts>>("/platform/dashboard/charts", { signal });
    return response.data;
  },

  async activity(signal?: AbortSignal): Promise<PlatformActivity> {
    const response = await apiClient.get<ApiItemResponse<PlatformActivity>>("/platform/dashboard/activity", { signal });
    return response.data;
  },
};
