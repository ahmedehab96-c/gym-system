import { apiClient, type ApiItemResponse, type ApiListResponse, type ApiMeta, type QueryParams } from "./apiClient";
import type { MaintenanceRecord } from "../types";

interface MaintenancePayload {
  id: number;
  equipmentId: number;
  equipmentName: string | null;
  type: string;
  technician: string | null;
  date: string;
  cost: number;
  status: MaintenanceRecord["status"];
  notes: string | null;
}

function toRecord(m: MaintenancePayload): MaintenanceRecord {
  return {
    id: String(m.id),
    equipmentId: String(m.equipmentId),
    equipmentName: m.equipmentName ?? "",
    type: m.type,
    technician: m.technician ?? "",
    date: m.date,
    cost: m.cost,
    status: m.status,
    notes: m.notes ?? "",
  };
}

export interface MaintenanceListParams {
  search?: string;
  status?: string;
  equipmentId?: string;
  date?: string;
  page?: number;
  perPage?: number;
}

function toQuery(params: MaintenanceListParams): QueryParams {
  return {
    search: params.search,
    status: params.status,
    equipment_id: params.equipmentId,
    date: params.date,
    page: params.page,
    per_page: params.perPage,
  };
}

export interface MaintenanceInput {
  equipmentId: string;
  type: string;
  technician?: string;
  date?: string;
  cost?: number;
  status?: MaintenanceRecord["status"];
  notes?: string;
}

function toRequestBody(input: MaintenanceInput): Record<string, unknown> {
  return {
    equipment_id: Number(input.equipmentId),
    type: input.type,
    technician: input.technician || undefined,
    date: input.date || undefined,
    cost: input.cost,
    status: input.status,
    notes: input.notes || undefined,
  };
}

export interface MaintenanceStats {
  total: number;
  upcoming: number;
  overdue: number;
  inProgress: number;
  completed: number;
  totalCost: number;
}

export const maintenanceService = {
  async list(params: MaintenanceListParams = {}, signal?: AbortSignal): Promise<{ data: MaintenanceRecord[]; meta: ApiMeta }> {
    const response = await apiClient.get<ApiListResponse<MaintenancePayload>>("/maintenance", { params: toQuery(params), signal });
    return { data: response.data.map(toRecord), meta: response.meta };
  },

  async upcoming(signal?: AbortSignal): Promise<MaintenanceRecord[]> {
    const response = await apiClient.get<ApiItemResponse<MaintenancePayload[]>>("/maintenance/upcoming", { signal });
    return response.data.map(toRecord);
  },

  async overdue(signal?: AbortSignal): Promise<MaintenanceRecord[]> {
    const response = await apiClient.get<ApiItemResponse<MaintenancePayload[]>>("/maintenance/overdue", { signal });
    return response.data.map(toRecord);
  },

  async stats(signal?: AbortSignal): Promise<MaintenanceStats> {
    const response = await apiClient.get<ApiItemResponse<MaintenanceStats>>("/maintenance/stats", { signal });
    return response.data;
  },

  async create(input: MaintenanceInput): Promise<MaintenanceRecord> {
    const response = await apiClient.post<ApiItemResponse<MaintenancePayload>>("/maintenance", toRequestBody(input));
    return toRecord(response.data);
  },

  async update(id: string, input: Partial<MaintenanceInput>): Promise<MaintenanceRecord> {
    const body: Record<string, unknown> = {};
    if (input.equipmentId !== undefined) body.equipment_id = Number(input.equipmentId);
    if (input.type !== undefined) body.type = input.type;
    if (input.technician !== undefined) body.technician = input.technician;
    if (input.date !== undefined) body.date = input.date;
    if (input.cost !== undefined) body.cost = input.cost;
    if (input.status !== undefined) body.status = input.status;
    if (input.notes !== undefined) body.notes = input.notes;

    const response = await apiClient.put<ApiItemResponse<MaintenancePayload>>(`/maintenance/${id}`, body);
    return toRecord(response.data);
  },
};
