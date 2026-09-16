import { apiClient, type ApiItemResponse } from "../../admin/services/apiClient";

export interface ProviderStatus {
  driver?: string;
  provider?: string;
  configured: boolean;
}

export interface CommunicationStatus {
  email: ProviderStatus;
  whatsapp: ProviderStatus;
  push: ProviderStatus;
}

export const platformCommunicationService = {
  async status(signal?: AbortSignal): Promise<CommunicationStatus> {
    const response = await apiClient.get<ApiItemResponse<CommunicationStatus>>("/platform/communication/status", { signal });
    return response.data;
  },
};
