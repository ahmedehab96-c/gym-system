import { apiClient, type ApiItemResponse, type ApiListResponse, type ApiMeta, type QueryParams } from "./apiClient";
import type { TrainingProgram } from "../types";

interface ProgramPayload {
  id: number;
  name: string;
  description: string | null;
  image: string | null;
  duration: string | null;
  difficulty: TrainingProgram["difficulty"];
  trainerId: number | null;
  trainerName: string | null;
  membersEnrolled?: number;
  status: TrainingProgram["status"];
}

function toProgram(p: ProgramPayload): TrainingProgram {
  return {
    id: String(p.id),
    name: p.name,
    description: p.description ?? "",
    image: p.image ?? "",
    duration: p.duration ?? "",
    difficulty: p.difficulty,
    trainerId: p.trainerId !== null ? String(p.trainerId) : "",
    trainerName: p.trainerName ?? "",
    membersEnrolled: p.membersEnrolled ?? 0,
    status: p.status,
  };
}

export interface ProgramListParams {
  search?: string;
  status?: string;
  difficulty?: string;
  trainerId?: string;
  page?: number;
  perPage?: number;
}

function toQuery(params: ProgramListParams): QueryParams {
  return {
    search: params.search,
    status: params.status,
    difficulty: params.difficulty,
    trainer_id: params.trainerId,
    page: params.page,
    per_page: params.perPage,
  };
}

export interface ProgramInput {
  name: string;
  description?: string;
  duration?: string;
  difficulty?: TrainingProgram["difficulty"];
  trainerId?: string;
  status?: TrainingProgram["status"];
}

function toRequestBody(input: ProgramInput): Record<string, unknown> {
  return {
    name: input.name,
    description: input.description || undefined,
    duration: input.duration || undefined,
    difficulty: input.difficulty,
    trainer_id: input.trainerId ? Number(input.trainerId) : undefined,
    status: input.status,
  };
}

export const trainingProgramService = {
  async list(params: ProgramListParams = {}, signal?: AbortSignal): Promise<{ data: TrainingProgram[]; meta: ApiMeta }> {
    const response = await apiClient.get<ApiListResponse<ProgramPayload>>("/training-programs", {
      params: toQuery(params),
      signal,
    });
    return { data: response.data.map(toProgram), meta: response.meta };
  },

  async create(input: ProgramInput): Promise<TrainingProgram> {
    const response = await apiClient.post<ApiItemResponse<ProgramPayload>>("/training-programs", toRequestBody(input));
    return toProgram(response.data);
  },

  async update(id: string, input: ProgramInput): Promise<TrainingProgram> {
    const response = await apiClient.put<ApiItemResponse<ProgramPayload>>(`/training-programs/${id}`, toRequestBody(input));
    return toProgram(response.data);
  },

  async remove(id: string): Promise<void> {
    await apiClient.delete(`/training-programs/${id}`);
  },

  async uploadImage(id: string, file: File): Promise<string> {
    const formData = new FormData();
    formData.append("image", file);
    const response = await apiClient.post<ApiItemResponse<ProgramPayload>>(`/training-programs/${id}/image`, formData);
    return response.data.image ?? "";
  },

  async enroll(id: string, memberId: string): Promise<TrainingProgram> {
    const response = await apiClient.post<ApiItemResponse<ProgramPayload>>(`/training-programs/${id}/enroll`, {
      member_id: Number(memberId),
    });
    return toProgram(response.data);
  },

  async unenroll(id: string, memberId: string): Promise<TrainingProgram> {
    const response = await apiClient.delete<ApiItemResponse<ProgramPayload>>(`/training-programs/${id}/enroll/${memberId}`);
    return toProgram(response.data);
  },
};
