import { apiClient, type ApiItemResponse, type ApiListResponse, type ApiMeta, type QueryParams } from "./apiClient";
import type { GymClass } from "../types";

export interface ClassPayload {
  id: number;
  name: string;
  category: string | null;
  trainerId: number;
  trainerName: string | null;
  date: string | null;
  day: string;
  startTime: string;
  endTime: string;
  capacity: number;
  booked: number;
  status: GymClass["status"];
  color: string | null;
}

export function toClass(c: ClassPayload): GymClass {
  return {
    id: String(c.id),
    name: c.name,
    category: c.category ?? "",
    trainerId: String(c.trainerId),
    trainerName: c.trainerName ?? "",
    date: c.date ?? "",
    day: c.day,
    startTime: c.startTime,
    endTime: c.endTime,
    capacity: c.capacity,
    booked: c.booked,
    status: c.status,
    color: c.color ?? "#5b8def",
  };
}

export interface ClassListParams {
  search?: string;
  status?: string;
  category?: string;
  trainerId?: string;
  day?: string;
  date?: string;
  page?: number;
  perPage?: number;
}

function toQuery(params: ClassListParams): QueryParams {
  return {
    search: params.search,
    status: params.status,
    category: params.category,
    trainer_id: params.trainerId,
    day: params.day,
    date: params.date,
    page: params.page,
    per_page: params.perPage,
  };
}

export interface ClassInput {
  name: string;
  category?: string;
  trainerId: string;
  date?: string;
  day: string;
  startTime: string;
  endTime: string;
  capacity: number;
  status?: GymClass["status"];
  color?: string;
}

function toRequestBody(input: ClassInput): Record<string, unknown> {
  return {
    name: input.name,
    category: input.category || undefined,
    trainer_id: Number(input.trainerId),
    date: input.date || undefined,
    day: input.day,
    start_time: input.startTime,
    end_time: input.endTime,
    capacity: input.capacity,
    status: input.status,
    color: input.color,
  };
}

export const classService = {
  async list(params: ClassListParams = {}, signal?: AbortSignal): Promise<{ data: GymClass[]; meta: ApiMeta }> {
    const response = await apiClient.get<ApiListResponse<ClassPayload>>("/classes", { params: toQuery(params), signal });
    return { data: response.data.map(toClass), meta: response.meta };
  },

  async create(input: ClassInput): Promise<GymClass> {
    const response = await apiClient.post<ApiItemResponse<ClassPayload>>("/classes", toRequestBody(input));
    return toClass(response.data);
  },

  async update(id: string, input: Partial<ClassInput>): Promise<GymClass> {
    const body: Record<string, unknown> = {};
    if (input.name !== undefined) body.name = input.name;
    if (input.category !== undefined) body.category = input.category;
    if (input.trainerId !== undefined) body.trainer_id = Number(input.trainerId);
    if (input.date !== undefined) body.date = input.date;
    if (input.day !== undefined) body.day = input.day;
    if (input.startTime !== undefined) body.start_time = input.startTime;
    if (input.endTime !== undefined) body.end_time = input.endTime;
    if (input.capacity !== undefined) body.capacity = input.capacity;
    if (input.status !== undefined) body.status = input.status;
    if (input.color !== undefined) body.color = input.color;

    const response = await apiClient.put<ApiItemResponse<ClassPayload>>(`/classes/${id}`, body);
    return toClass(response.data);
  },

  async remove(id: string): Promise<void> {
    await apiClient.delete(`/classes/${id}`);
  },

  async book(classId: string, memberId: string): Promise<GymClass> {
    const response = await apiClient.post<ApiItemResponse<ClassPayload>>(`/classes/${classId}/book`, {
      member_id: Number(memberId),
    });
    return toClass(response.data);
  },

  async cancelBooking(classId: string, memberId: string): Promise<GymClass> {
    const response = await apiClient.delete<ApiItemResponse<ClassPayload>>(`/classes/${classId}/book/${memberId}`);
    return toClass(response.data);
  },
};
