import { apiClient, type ApiItemResponse, type ApiListResponse, type ApiMeta, type QueryParams } from "./apiClient";
import type { Announcement } from "../types";

interface AnnouncementPayload {
  id: number;
  title: string;
  description: string | null;
  image: string | null;
  audience: Announcement["audience"];
  planId: number | null;
  planName: string | null;
  status: Announcement["status"];
  publishDate: string;
}

function toAnnouncement(a: AnnouncementPayload): Announcement {
  return {
    id: String(a.id),
    title: a.title,
    description: a.description ?? "",
    image: a.image ?? "",
    audience: a.audience,
    planId: a.planId !== null ? String(a.planId) : "",
    planName: a.planName ?? "",
    publishDate: a.publishDate,
    status: a.status,
  };
}

export interface AnnouncementListParams {
  search?: string;
  audience?: string;
  status?: string;
  planId?: string;
  page?: number;
  perPage?: number;
}

function toQuery(params: AnnouncementListParams): QueryParams {
  return {
    search: params.search,
    audience: params.audience,
    status: params.status,
    plan_id: params.planId,
    page: params.page,
    per_page: params.perPage,
  };
}

export interface AnnouncementInput {
  title: string;
  description?: string;
  audience: Announcement["audience"];
  planId?: string;
  status?: Announcement["status"];
  publishDate: string;
}

function toRequestBody(input: AnnouncementInput): Record<string, unknown> {
  return {
    title: input.title,
    description: input.description || undefined,
    audience: input.audience,
    plan_id: input.audience === "Specific Plan" && input.planId ? Number(input.planId) : undefined,
    status: input.status,
    publish_date: input.publishDate,
  };
}

export const announcementService = {
  async list(params: AnnouncementListParams = {}, signal?: AbortSignal): Promise<{ data: Announcement[]; meta: ApiMeta }> {
    const response = await apiClient.get<ApiListResponse<AnnouncementPayload>>("/announcements", {
      params: toQuery(params),
      signal,
    });
    return { data: response.data.map(toAnnouncement), meta: response.meta };
  },

  async create(input: AnnouncementInput): Promise<Announcement> {
    const response = await apiClient.post<ApiItemResponse<AnnouncementPayload>>("/announcements", toRequestBody(input));
    return toAnnouncement(response.data);
  },

  async update(id: string, input: Partial<AnnouncementInput>): Promise<Announcement> {
    const response = await apiClient.put<ApiItemResponse<AnnouncementPayload>>(`/announcements/${id}`, {
      title: input.title,
      description: input.description,
      audience: input.audience,
      plan_id: input.audience === "Specific Plan" && input.planId ? Number(input.planId) : undefined,
      status: input.status,
      publish_date: input.publishDate,
    });
    return toAnnouncement(response.data);
  },

  async remove(id: string): Promise<void> {
    await apiClient.delete(`/announcements/${id}`);
  },

  async uploadImage(id: string, file: File): Promise<string> {
    const formData = new FormData();
    formData.append("image", file);
    const response = await apiClient.post<ApiItemResponse<AnnouncementPayload>>(`/announcements/${id}/image`, formData);
    return response.data.image ?? "";
  },

  async publish(id: string): Promise<Announcement> {
    const response = await apiClient.post<ApiItemResponse<AnnouncementPayload>>(`/announcements/${id}/publish`);
    return toAnnouncement(response.data);
  },

  async unpublish(id: string): Promise<Announcement> {
    const response = await apiClient.post<ApiItemResponse<AnnouncementPayload>>(`/announcements/${id}/unpublish`);
    return toAnnouncement(response.data);
  },
};
