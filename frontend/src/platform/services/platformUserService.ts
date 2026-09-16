import { apiClient, type ApiItemResponse, type ApiListResponse, type QueryParams } from "../../admin/services/apiClient";
import type { PlatformUser } from "../types";

interface RawPlatformUser extends Omit<PlatformUser, "id"> {
  id: number;
}

function toPlatformUser(raw: RawPlatformUser): PlatformUser {
  return { ...raw, id: String(raw.id) };
}

export interface PlatformUserListParams {
  search?: string;
  status?: string;
  page?: number;
  perPage?: number;
}

export interface PlatformUserInput {
  name: string;
  email: string;
  phone?: string;
  password: string;
  status?: "Active" | "Inactive";
}

export const platformUserService = {
  async list(params: PlatformUserListParams, signal?: AbortSignal): Promise<ApiListResponse<PlatformUser>> {
    const response = await apiClient.get<ApiListResponse<RawPlatformUser>>("/platform/users", {
      signal,
      params: {
        search: params.search,
        status: params.status,
        page: params.page,
        per_page: params.perPage,
      } satisfies QueryParams,
    });
    return { data: response.data.map(toPlatformUser), meta: response.meta };
  },

  async create(input: PlatformUserInput): Promise<PlatformUser> {
    const response = await apiClient.post<ApiItemResponse<RawPlatformUser>>("/platform/users", input);
    return toPlatformUser(response.data);
  },

  async updateStatus(id: string, status: "Active" | "Inactive"): Promise<PlatformUser> {
    const response = await apiClient.patch<ApiItemResponse<RawPlatformUser>>(`/platform/users/${id}/status`, { status });
    return toPlatformUser(response.data);
  },
};
