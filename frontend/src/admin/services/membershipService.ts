import { apiClient, type ApiItemResponse, type ApiListResponse, type ApiMeta, type QueryParams } from "./apiClient";
import type { Membership } from "../types";

export interface MembershipPayload {
  id: number;
  memberId: number;
  memberName: string | null;
  memberAvatar: string | null;
  planId: number;
  planName: string | null;
  startDate: string;
  expiryDate: string;
  price: number;
  status: Membership["status"];
}

export function toMembership(m: MembershipPayload): Membership {
  return {
    id: String(m.id),
    memberId: String(m.memberId),
    memberName: m.memberName ?? "",
    memberAvatar: m.memberAvatar ?? "",
    planId: String(m.planId),
    planName: m.planName ?? "",
    startDate: m.startDate,
    expiryDate: m.expiryDate,
    price: m.price,
    status: m.status,
  };
}

export interface MembershipListParams {
  search?: string;
  status?: string;
  memberId?: string;
  page?: number;
  perPage?: number;
}

function toQuery(params: MembershipListParams): QueryParams {
  return {
    search: params.search,
    status: params.status,
    member_id: params.memberId,
    page: params.page,
    per_page: params.perPage,
  };
}

export const membershipService = {
  async list(params: MembershipListParams = {}, signal?: AbortSignal): Promise<{ data: Membership[]; meta: ApiMeta }> {
    const response = await apiClient.get<ApiListResponse<MembershipPayload>>("/memberships", { params: toQuery(params), signal });
    return { data: response.data.map(toMembership), meta: response.meta };
  },

  async create(memberId: string, planId: string, startDate?: string, price?: number): Promise<Membership> {
    const response = await apiClient.post<ApiItemResponse<MembershipPayload>>("/memberships", {
      member_id: Number(memberId),
      plan_id: Number(planId),
      start_date: startDate || undefined,
      price,
    });
    return toMembership(response.data);
  },

  async renew(id: string): Promise<Membership> {
    const response = await apiClient.post<ApiItemResponse<MembershipPayload>>(`/memberships/${id}/renew`);
    return toMembership(response.data);
  },

  async changePlan(id: string, planId: string): Promise<Membership> {
    const response = await apiClient.post<ApiItemResponse<MembershipPayload>>(`/memberships/${id}/change-plan`, {
      plan_id: Number(planId),
    });
    return toMembership(response.data);
  },

  async suspend(id: string): Promise<Membership> {
    const response = await apiClient.post<ApiItemResponse<MembershipPayload>>(`/memberships/${id}/suspend`);
    return toMembership(response.data);
  },

  async cancel(id: string): Promise<Membership> {
    const response = await apiClient.post<ApiItemResponse<MembershipPayload>>(`/memberships/${id}/cancel`);
    return toMembership(response.data);
  },
};
