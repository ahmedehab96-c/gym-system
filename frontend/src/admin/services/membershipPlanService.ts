import { apiClient, type ApiItemResponse } from "./apiClient";
import type { MembershipPlan } from "../types";

interface PlanPayload {
  id: number;
  name: string;
  tagline: string | null;
  price: number;
  duration: string;
  durationDays: number;
  features: string[];
  memberCount?: number;
  status: MembershipPlan["status"];
  color: string | null;
  popular: boolean;
}

function toPlan(p: PlanPayload): MembershipPlan {
  return {
    id: String(p.id),
    name: p.name,
    tagline: p.tagline ?? "",
    price: p.price,
    duration: p.duration,
    durationDays: p.durationDays,
    features: p.features ?? [],
    memberCount: p.memberCount ?? 0,
    status: p.status,
    color: p.color ?? "#8b8f9a",
    popular: p.popular,
  };
}

export interface PlanInput {
  name: string;
  tagline?: string;
  price: number;
  duration: string;
  durationDays: number;
  features?: string[];
  status?: MembershipPlan["status"];
  color?: string;
  popular?: boolean;
}

function toRequestBody(input: PlanInput): Record<string, unknown> {
  return {
    name: input.name,
    tagline: input.tagline || undefined,
    price: input.price,
    duration_label: input.duration,
    duration_days: input.durationDays,
    features: input.features,
    status: input.status,
    color: input.color,
    popular: input.popular,
  };
}

export const membershipPlanService = {
  async list(signal?: AbortSignal): Promise<MembershipPlan[]> {
    const response = await apiClient.get<ApiItemResponse<PlanPayload[]>>("/membership-plans", { signal });
    return response.data.map(toPlan);
  },

  async create(input: PlanInput): Promise<MembershipPlan> {
    const response = await apiClient.post<ApiItemResponse<PlanPayload>>("/membership-plans", toRequestBody(input));
    return toPlan(response.data);
  },

  async update(id: string, input: PlanInput): Promise<MembershipPlan> {
    const response = await apiClient.put<ApiItemResponse<PlanPayload>>(`/membership-plans/${id}`, toRequestBody(input));
    return toPlan(response.data);
  },

  async remove(id: string): Promise<void> {
    await apiClient.delete(`/membership-plans/${id}`);
  },
};
