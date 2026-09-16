import { apiClient, type ApiItemResponse, type ApiListResponse, type ApiMeta, type QueryParams } from "./apiClient";
import type { AppNotification } from "../types";

interface NotificationPayload {
  id: number;
  type: AppNotification["type"];
  title: string;
  message: string;
  read: boolean;
  userId: number | null;
  createdAt: string;
}

function toNotification(n: NotificationPayload): AppNotification {
  return {
    id: String(n.id),
    type: n.type,
    title: n.title,
    message: n.message,
    date: n.createdAt,
    read: n.read,
  };
}

export interface NotificationListParams {
  read?: boolean;
  type?: string;
  page?: number;
  perPage?: number;
}

function toQuery(params: NotificationListParams): QueryParams {
  return {
    read: params.read === undefined ? undefined : params.read ? "1" : "0",
    type: params.type,
    page: params.page,
    per_page: params.perPage,
  };
}

export const notificationService = {
  async list(params: NotificationListParams = {}, signal?: AbortSignal): Promise<{ data: AppNotification[]; meta: ApiMeta }> {
    const response = await apiClient.get<ApiListResponse<NotificationPayload>>("/notifications", {
      params: toQuery(params),
      signal,
    });
    return { data: response.data.map(toNotification), meta: response.meta };
  },

  async markRead(id: string, read = true): Promise<AppNotification> {
    const response = await apiClient.put<ApiItemResponse<NotificationPayload>>(`/notifications/${id}`, { read });
    return toNotification(response.data);
  },

  async markAllRead(): Promise<void> {
    await apiClient.post("/notifications/mark-all-read");
  },

  async remove(id: string): Promise<void> {
    await apiClient.delete(`/notifications/${id}`);
  },
};
