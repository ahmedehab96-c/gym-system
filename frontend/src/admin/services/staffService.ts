import { apiClient, type ApiItemResponse, type ApiListResponse, type ApiMeta, type QueryParams } from "./apiClient";
import type { StaffMember, StaffRole } from "../types";

interface StaffPayload {
  id: number;
  name: string;
  photo: string | null;
  email: string;
  phone: string | null;
  role: StaffRole;
  status: StaffMember["status"];
  position: string | null;
  hireDate: string | null;
  lastLoginAt: string | null;
  createdAt: string;
}

function toStaff(s: StaffPayload): StaffMember {
  return {
    id: String(s.id),
    name: s.name,
    photo: s.photo ?? "",
    role: s.role,
    email: s.email,
    phone: s.phone ?? "",
    status: s.status,
    lastLogin: s.lastLoginAt ?? "",
  };
}

export interface StaffListParams {
  search?: string;
  role?: string;
  status?: string;
  position?: string;
  sortBy?: string;
  sortDir?: "asc" | "desc";
  page?: number;
  perPage?: number;
}

function toQuery(params: StaffListParams): QueryParams {
  return {
    search: params.search,
    role: params.role,
    status: params.status,
    position: params.position,
    sort_by: params.sortBy,
    sort_dir: params.sortDir,
    page: params.page,
    per_page: params.perPage,
  };
}

export interface StaffInput {
  name: string;
  email: string;
  phone?: string;
  password?: string;
  role: StaffRole;
  status?: StaffMember["status"];
  position?: string;
  hireDate?: string;
}

function toRequestBody(input: StaffInput): Record<string, unknown> {
  return {
    name: input.name,
    email: input.email,
    phone: input.phone || undefined,
    password: input.password || undefined,
    role: input.role,
    status: input.status,
    position: input.position || undefined,
    hire_date: input.hireDate || undefined,
  };
}

export const staffService = {
  async list(params: StaffListParams = {}, signal?: AbortSignal): Promise<{ data: StaffMember[]; meta: ApiMeta }> {
    const response = await apiClient.get<ApiListResponse<StaffPayload>>("/staff", { params: toQuery(params), signal });
    return { data: response.data.map(toStaff), meta: response.meta };
  },

  async create(input: StaffInput): Promise<StaffMember> {
    const response = await apiClient.post<ApiItemResponse<StaffPayload>>("/staff", toRequestBody(input));
    return toStaff(response.data);
  },

  async update(id: string, input: Partial<StaffInput>): Promise<StaffMember> {
    const body: Record<string, unknown> = {};
    if (input.name !== undefined) body.name = input.name;
    if (input.email !== undefined) body.email = input.email;
    if (input.phone !== undefined) body.phone = input.phone;
    if (input.password) body.password = input.password;
    if (input.role !== undefined) body.role = input.role;
    if (input.status !== undefined) body.status = input.status;
    if (input.position !== undefined) body.position = input.position;
    if (input.hireDate !== undefined) body.hire_date = input.hireDate;

    const response = await apiClient.put<ApiItemResponse<StaffPayload>>(`/staff/${id}`, body);
    return toStaff(response.data);
  },

  async remove(id: string): Promise<void> {
    await apiClient.delete(`/staff/${id}`);
  },

  async updateStatus(id: string, status: StaffMember["status"]): Promise<StaffMember> {
    const response = await apiClient.patch<ApiItemResponse<StaffPayload>>(`/staff/${id}/status`, { status });
    return toStaff(response.data);
  },

  async updateRole(id: string, role: StaffRole): Promise<StaffMember> {
    const response = await apiClient.patch<ApiItemResponse<StaffPayload>>(`/staff/${id}/role`, { role });
    return toStaff(response.data);
  },

  async uploadPhoto(id: string, file: File): Promise<string> {
    const formData = new FormData();
    formData.append("photo", file);
    const response = await apiClient.post<ApiItemResponse<StaffPayload>>(`/staff/${id}/photo`, formData);
    return response.data.photo ?? "";
  },
};
