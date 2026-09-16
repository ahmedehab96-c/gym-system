import { apiClient, type ApiItemResponse } from "./apiClient";
import type { QueryParams } from "./apiClient";

export interface Facility {
  id: string;
  name: string;
  description: string;
  image: string;
  capacity: number | null;
  area: string;
  status: "Open" | "Maintenance";
}

interface FacilityPayload {
  id: number;
  name: string;
  description: string | null;
  image: string | null;
  capacity: number | null;
  area: string | null;
  status: Facility["status"];
}

function toFacility(f: FacilityPayload): Facility {
  return {
    id: String(f.id),
    name: f.name,
    description: f.description ?? "",
    image: f.image ?? "",
    capacity: f.capacity,
    area: f.area ?? "",
    status: f.status,
  };
}

export interface FacilityListParams {
  search?: string;
  status?: string;
}

function toQuery(params: FacilityListParams): QueryParams {
  return { search: params.search, status: params.status };
}

export interface FacilityInput {
  name: string;
  description?: string;
  capacity?: number;
  area?: string;
  status?: Facility["status"];
}

export const facilityService = {
  async list(params: FacilityListParams = {}, signal?: AbortSignal): Promise<Facility[]> {
    const response = await apiClient.get<ApiItemResponse<FacilityPayload[]>>("/public/facilities", {
      params: toQuery(params),
      signal,
    });
    return response.data.map(toFacility);
  },

  async create(input: FacilityInput): Promise<Facility> {
    const response = await apiClient.post<ApiItemResponse<FacilityPayload>>("/facilities", input);
    return toFacility(response.data);
  },

  async update(id: string, input: FacilityInput): Promise<Facility> {
    const response = await apiClient.put<ApiItemResponse<FacilityPayload>>(`/facilities/${id}`, input);
    return toFacility(response.data);
  },

  async remove(id: string): Promise<void> {
    await apiClient.delete(`/facilities/${id}`);
  },

  async uploadImage(id: string, file: File): Promise<string> {
    const formData = new FormData();
    formData.append("image", file);
    const response = await apiClient.post<ApiItemResponse<FacilityPayload>>(`/facilities/${id}/image`, formData);
    return response.data.image ?? "";
  },
};
