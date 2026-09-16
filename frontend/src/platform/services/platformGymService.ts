import { apiClient, type ApiItemResponse, type ApiListResponse, type QueryParams } from "../../admin/services/apiClient";
import type { AuditLogEntry, Gym, GymStatus } from "../types";
import { toAuditLogEntry, type RawAuditLogEntry } from "./platformAuditLogService";

interface RawGym extends Omit<Gym, "id" | "owner"> {
  id: number;
  owner?: { id: number; name: string; email: string; phone: string | null } | null;
}

function toGym(raw: RawGym): Gym {
  return {
    ...raw,
    id: String(raw.id),
    owner: raw.owner ? { ...raw.owner, id: String(raw.owner.id) } : raw.owner,
  };
}

export interface GymListParams {
  search?: string;
  status?: string;
  sortBy?: "name" | "status" | "createdAt";
  sortDir?: "asc" | "desc";
  page?: number;
  perPage?: number;
}

export interface GymInput {
  name: string;
  slug: string;
  email?: string;
  phone?: string;
  address?: string;
  timezone?: string;
  currency?: string;
  status?: GymStatus;
  ownerName?: string;
  ownerEmail?: string;
  ownerPassword?: string;
}

function toRequestBody(input: Partial<GymInput>): Record<string, unknown> {
  return {
    name: input.name,
    slug: input.slug,
    email: input.email || undefined,
    phone: input.phone || undefined,
    address: input.address || undefined,
    timezone: input.timezone || undefined,
    currency: input.currency || undefined,
    status: input.status,
    owner_name: input.ownerName || undefined,
    owner_email: input.ownerEmail || undefined,
    owner_password: input.ownerPassword || undefined,
  };
}

export const platformGymService = {
  async list(params: GymListParams, signal?: AbortSignal): Promise<ApiListResponse<Gym>> {
    const response = await apiClient.get<ApiListResponse<RawGym>>("/platform/gyms", {
      signal,
      params: {
        search: params.search,
        status: params.status,
        sort_by: params.sortBy,
        sort_dir: params.sortDir,
        page: params.page,
        per_page: params.perPage,
      } satisfies QueryParams,
    });
    return { data: response.data.map(toGym), meta: response.meta };
  },

  async get(id: string, signal?: AbortSignal): Promise<{ gym: Gym; recentActivity: AuditLogEntry[] }> {
    const response = await apiClient.get<ApiItemResponse<{ gym: RawGym; recentActivity: RawAuditLogEntry[] }>>(
      `/platform/gyms/${id}`,
      { signal },
    );
    return {
      gym: toGym(response.data.gym),
      recentActivity: response.data.recentActivity.map(toAuditLogEntry),
    };
  },

  async create(input: GymInput): Promise<Gym> {
    const response = await apiClient.post<ApiItemResponse<RawGym>>("/platform/gyms", toRequestBody(input));
    return toGym(response.data);
  },

  async update(id: string, input: Partial<GymInput>): Promise<Gym> {
    const response = await apiClient.put<ApiItemResponse<RawGym>>(`/platform/gyms/${id}`, toRequestBody(input));
    return toGym(response.data);
  },

  async activate(id: string): Promise<Gym> {
    const response = await apiClient.post<ApiItemResponse<RawGym>>(`/platform/gyms/${id}/activate`);
    return toGym(response.data);
  },

  async suspend(id: string): Promise<Gym> {
    const response = await apiClient.post<ApiItemResponse<RawGym>>(`/platform/gyms/${id}/suspend`);
    return toGym(response.data);
  },

  async reactivate(id: string): Promise<Gym> {
    const response = await apiClient.post<ApiItemResponse<RawGym>>(`/platform/gyms/${id}/reactivate`);
    return toGym(response.data);
  },
};
