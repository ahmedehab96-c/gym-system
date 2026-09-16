import { apiClient, type ApiItemResponse, type ApiListResponse, type QueryParams } from "./apiClient";
import type { NotificationType } from "../types";

export type CommunicationChannel = "email" | "whatsapp" | "push";

export interface ChannelDefaults {
  email: boolean;
  whatsapp: boolean;
  push: boolean;
}

export interface PreferenceOverride {
  type: string;
  channel: CommunicationChannel;
  enabled: boolean;
}

export interface CommunicationPreferences {
  channels: ChannelDefaults;
  overrides: PreferenceOverride[];
}

export type DeliveryStatus = "Pending" | "Sent" | "Failed";

export interface NotificationDelivery {
  id: string;
  type: string;
  channel: CommunicationChannel;
  recipient: string;
  status: DeliveryStatus;
  error: string | null;
  sentAt: string | null;
  createdAt: string;
}

interface RawDelivery extends Omit<NotificationDelivery, "id"> {
  id: number;
}
function toDelivery(raw: RawDelivery): NotificationDelivery {
  return { ...raw, id: String(raw.id) };
}

export const communicationService = {
  async getPreferences(signal?: AbortSignal): Promise<CommunicationPreferences> {
    const response = await apiClient.get<ApiItemResponse<CommunicationPreferences>>("/communication/preferences", { signal });
    return response.data;
  },

  async setPreference(type: NotificationType | string, channel: CommunicationChannel, enabled: boolean): Promise<CommunicationPreferences> {
    const response = await apiClient.put<ApiItemResponse<CommunicationPreferences>>("/communication/preferences", { type, channel, enabled });
    return response.data;
  },

  async listDeliveries(
    params: { status?: DeliveryStatus; channel?: CommunicationChannel; page?: number; perPage?: number } = {},
    signal?: AbortSignal,
  ): Promise<ApiListResponse<NotificationDelivery>> {
    const response = await apiClient.get<ApiListResponse<RawDelivery>>("/communication/deliveries", {
      signal,
      params: { status: params.status, channel: params.channel, page: params.page, per_page: params.perPage } satisfies QueryParams,
    });
    return { data: response.data.map(toDelivery), meta: response.meta };
  },
};
