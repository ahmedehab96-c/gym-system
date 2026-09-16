import { apiClient, type ApiItemResponse, type ApiListResponse, type ApiMeta, type QueryParams } from "./apiClient";
import type { EquipmentItem } from "../types";

export interface EquipmentPayload {
  id: number;
  name: string;
  image: string | null;
  category: EquipmentItem["category"];
  brand: string | null;
  model: string | null;
  purchaseDate: string | null;
  condition: EquipmentItem["condition"];
  location: string | null;
  lastMaintenance: string | null;
  nextMaintenance: string | null;
  status: EquipmentItem["status"];
}

export function toEquipment(e: EquipmentPayload): EquipmentItem {
  return {
    id: String(e.id),
    name: e.name,
    image: e.image ?? "",
    category: e.category,
    brand: e.brand ?? "",
    model: e.model ?? "",
    purchaseDate: e.purchaseDate ?? "",
    condition: e.condition,
    location: e.location ?? "",
    lastMaintenance: e.lastMaintenance ?? "",
    nextMaintenance: e.nextMaintenance ?? "",
    status: e.status,
  };
}

export interface EquipmentListParams {
  search?: string;
  status?: string;
  condition?: string;
  category?: string;
  location?: string;
  sortBy?: string;
  sortDir?: "asc" | "desc";
  page?: number;
  perPage?: number;
}

function toQuery(params: EquipmentListParams): QueryParams {
  return {
    search: params.search,
    status: params.status,
    condition: params.condition,
    category: params.category,
    location: params.location,
    sort_by: params.sortBy,
    sort_dir: params.sortDir,
    page: params.page,
    per_page: params.perPage,
  };
}

export interface EquipmentInput {
  name: string;
  category: EquipmentItem["category"];
  brand?: string;
  model?: string;
  purchaseDate?: string;
  condition?: EquipmentItem["condition"];
  location?: string;
  status?: EquipmentItem["status"];
}

function toRequestBody(input: EquipmentInput): Record<string, unknown> {
  return {
    name: input.name,
    category: input.category,
    brand: input.brand || undefined,
    model: input.model || undefined,
    purchase_date: input.purchaseDate || undefined,
    condition: input.condition,
    location: input.location || undefined,
    status: input.status,
  };
}

export const equipmentService = {
  async list(params: EquipmentListParams = {}, signal?: AbortSignal): Promise<{ data: EquipmentItem[]; meta: ApiMeta }> {
    const response = await apiClient.get<ApiListResponse<EquipmentPayload>>("/equipment", { params: toQuery(params), signal });
    return { data: response.data.map(toEquipment), meta: response.meta };
  },

  async create(input: EquipmentInput): Promise<EquipmentItem> {
    const response = await apiClient.post<ApiItemResponse<EquipmentPayload>>("/equipment", toRequestBody(input));
    return toEquipment(response.data);
  },

  async update(id: string, input: EquipmentInput): Promise<EquipmentItem> {
    const response = await apiClient.put<ApiItemResponse<EquipmentPayload>>(`/equipment/${id}`, toRequestBody(input));
    return toEquipment(response.data);
  },

  async remove(id: string): Promise<void> {
    await apiClient.delete(`/equipment/${id}`);
  },

  async uploadImage(id: string, file: File): Promise<string> {
    const formData = new FormData();
    formData.append("image", file);
    const response = await apiClient.post<ApiItemResponse<EquipmentPayload>>(`/equipment/${id}/image`, formData);
    return response.data.image ?? "";
  },
};
