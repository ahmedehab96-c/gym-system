import { apiClient, type ApiItemResponse } from "./apiClient";

export interface MemberGrowthPoint {
  month: string;
  members: number;
  newMembers: number;
}

export interface MembershipDistributionSlice {
  name: string;
  value: number;
  color: string;
}

export interface TrainerPerformance {
  id: number;
  name: string;
  specialty: string;
  status: string;
  rating: number;
  sessionsCompleted: number;
  assignedMembers: number;
  classesCount: number;
}

export const analyticsService = {
  async memberGrowth(months = 12, signal?: AbortSignal): Promise<MemberGrowthPoint[]> {
    const response = await apiClient.get<ApiItemResponse<MemberGrowthPoint[]>>("/analytics/member-growth", {
      params: { months },
      signal,
    });
    return response.data;
  },

  async membershipDistribution(signal?: AbortSignal): Promise<MembershipDistributionSlice[]> {
    const response = await apiClient.get<ApiItemResponse<MembershipDistributionSlice[]>>("/analytics/membership-distribution", {
      signal,
    });
    return response.data;
  },

  async trainerPerformance(signal?: AbortSignal): Promise<TrainerPerformance[]> {
    const response = await apiClient.get<ApiItemResponse<TrainerPerformance[]>>("/analytics/trainer-performance", { signal });
    return response.data;
  },
};
