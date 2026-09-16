import { apiClient, type ApiItemResponse, type ApiListResponse, type ApiMeta, type QueryParams } from "./apiClient";
import type { Trainer } from "../types";

export interface TrainerPayload {
  id: number;
  name: string;
  photo: string | null;
  specialty: string;
  specialties: string[];
  experience: string | null;
  phone: string | null;
  email: string;
  bio: string | null;
  assignedMembers?: number;
  classesCount?: number;
  status: Trainer["status"];
  rating: number;
  sessionsCompleted: number;
  schedule: { day: string; time: string; activity: string }[];
}

export function toTrainer(t: TrainerPayload): Trainer {
  return {
    id: String(t.id),
    name: t.name,
    photo: t.photo ?? "",
    specialty: t.specialty,
    specialties: t.specialties ?? [],
    experience: t.experience ?? "",
    phone: t.phone ?? "",
    email: t.email,
    bio: t.bio ?? "",
    assignedMembers: t.assignedMembers ?? 0,
    classesCount: t.classesCount ?? 0,
    status: t.status,
    rating: t.rating,
    sessionsCompleted: t.sessionsCompleted,
    schedule: t.schedule ?? [],
  };
}

export interface TrainerListParams {
  search?: string;
  status?: string;
  specialty?: string;
  sortBy?: string;
  sortDir?: "asc" | "desc";
  page?: number;
  perPage?: number;
}

function toQuery(params: TrainerListParams): QueryParams {
  return {
    search: params.search,
    status: params.status,
    specialty: params.specialty,
    sort_by: params.sortBy,
    sort_dir: params.sortDir,
    page: params.page,
    per_page: params.perPage,
  };
}

export interface TrainerInput {
  name: string;
  photo?: string;
  specialty: string;
  specialties?: string[];
  experience?: string;
  phone?: string;
  email: string;
  bio?: string;
  status?: Trainer["status"];
  rating?: number;
  sessionsCompleted?: number;
  schedule?: { day: string; time: string; activity: string }[];
}

function toRequestBody(input: TrainerInput): Record<string, unknown> {
  return {
    name: input.name,
    specialty: input.specialty,
    specialties: input.specialties,
    experience: input.experience || undefined,
    phone: input.phone || undefined,
    email: input.email,
    bio: input.bio || undefined,
    status: input.status,
    rating: input.rating,
    sessions_completed: input.sessionsCompleted,
    schedule: input.schedule,
  };
}

export interface TrainerStats {
  total: number;
  active: number;
  onLeave: number;
  inactive: number;
  averageRating: number;
  totalSessionsCompleted: number;
  totalAssignedMembers: number;
}

export const trainerService = {
  async list(params: TrainerListParams = {}, signal?: AbortSignal): Promise<{ data: Trainer[]; meta: ApiMeta }> {
    const response = await apiClient.get<ApiListResponse<TrainerPayload>>("/trainers", { params: toQuery(params), signal });
    return { data: response.data.map(toTrainer), meta: response.meta };
  },

  async getById(id: string, signal?: AbortSignal): Promise<Trainer> {
    const response = await apiClient.get<ApiItemResponse<TrainerPayload>>(`/trainers/${id}`, { signal });
    return toTrainer(response.data);
  },

  async create(input: TrainerInput): Promise<Trainer> {
    const response = await apiClient.post<ApiItemResponse<TrainerPayload>>("/trainers", toRequestBody(input));
    return toTrainer(response.data);
  },

  async update(id: string, input: TrainerInput): Promise<Trainer> {
    const response = await apiClient.put<ApiItemResponse<TrainerPayload>>(`/trainers/${id}`, toRequestBody(input));
    return toTrainer(response.data);
  },

  async remove(id: string): Promise<void> {
    await apiClient.delete(`/trainers/${id}`);
  },

  async uploadPhoto(id: string, file: File): Promise<string> {
    const formData = new FormData();
    formData.append("photo", file);
    const response = await apiClient.post<ApiItemResponse<TrainerPayload>>(`/trainers/${id}/photo`, formData);
    return response.data.photo ?? "";
  },

  async stats(signal?: AbortSignal): Promise<TrainerStats> {
    const response = await apiClient.get<ApiItemResponse<TrainerStats>>("/trainers/stats", { signal });
    return response.data;
  },
};
