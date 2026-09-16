import { apiClient, type ApiItemResponse } from "../../admin/services/apiClient";
import type { PlanStatus, PlatformPlan, PlatformPlanLimits } from "../types";

interface RawPlan extends Omit<PlatformPlan, "id"> {
  id: number;
}

function toPlan(raw: RawPlan): PlatformPlan {
  return { ...raw, id: String(raw.id) };
}

export interface PlanInput {
  name: string;
  slug: string;
  description?: string;
  monthlyPrice: number;
  yearlyPrice: number;
  trialDays?: number;
  features?: string[];
  limits: PlatformPlanLimits;
  status?: PlanStatus;
  sortOrder?: number;
}

function toRequestBody(input: Partial<PlanInput>): Record<string, unknown> {
  return {
    name: input.name,
    slug: input.slug,
    description: input.description || undefined,
    monthly_price: input.monthlyPrice,
    yearly_price: input.yearlyPrice,
    trial_days: input.trialDays,
    features: input.features,
    limits: input.limits,
    status: input.status,
    sort_order: input.sortOrder,
  };
}

export const platformPlanService = {
  async list(signal?: AbortSignal): Promise<PlatformPlan[]> {
    const response = await apiClient.get<ApiItemResponse<RawPlan[]>>("/platform/subscription-plans", { signal });
    return response.data.map(toPlan);
  },

  async create(input: PlanInput): Promise<PlatformPlan> {
    const response = await apiClient.post<ApiItemResponse<RawPlan>>("/platform/subscription-plans", toRequestBody(input));
    return toPlan(response.data);
  },

  async update(id: string, input: Partial<PlanInput>): Promise<PlatformPlan> {
    const response = await apiClient.put<ApiItemResponse<RawPlan>>(`/platform/subscription-plans/${id}`, toRequestBody(input));
    return toPlan(response.data);
  },

  async remove(id: string): Promise<void> {
    await apiClient.delete(`/platform/subscription-plans/${id}`);
  },
};
