import { apiClient, type ApiItemResponse } from "./apiClient";

export interface GymSettings {
  id: number;
  name: string;
  phone: string;
  email: string;
  address: string;
  website: string;
  logoUrl: string;
  workingDays: string[];
  openTime: string;
  closeTime: string;
  accentColor: string;
  currency: string;
  timezone: string;
  language: string;
  notifyEmail: boolean;
  notifyPush: boolean;
  notifySms: boolean;
  notifyWhatsapp: boolean;
}

interface GymSettingsPayload {
  id: number;
  name: string;
  phone: string | null;
  email: string | null;
  address: string | null;
  website: string | null;
  logoUrl: string | null;
  workingDays: string[];
  openTime: string | null;
  closeTime: string | null;
  accentColor: string | null;
  currency: string;
  timezone: string;
  language: string;
  notifyEmail: boolean;
  notifyPush: boolean;
  notifySms: boolean;
  notifyWhatsapp: boolean;
}

function toSettings(s: GymSettingsPayload): GymSettings {
  return {
    id: s.id,
    name: s.name,
    phone: s.phone ?? "",
    email: s.email ?? "",
    address: s.address ?? "",
    website: s.website ?? "",
    logoUrl: s.logoUrl ?? "",
    workingDays: s.workingDays ?? [],
    openTime: s.openTime ?? "",
    closeTime: s.closeTime ?? "",
    accentColor: s.accentColor ?? "",
    currency: s.currency,
    timezone: s.timezone,
    language: s.language,
    notifyEmail: s.notifyEmail,
    notifyPush: s.notifyPush,
    notifySms: s.notifySms,
    notifyWhatsapp: s.notifyWhatsapp,
  };
}

export interface GymSettingsInput {
  name?: string;
  phone?: string;
  email?: string;
  address?: string;
  website?: string;
  workingDays?: string[];
  openTime?: string;
  closeTime?: string;
  accentColor?: string;
  currency?: string;
  timezone?: string;
  language?: string;
  notifyEmail?: boolean;
  notifyPush?: boolean;
  notifySms?: boolean;
  notifyWhatsapp?: boolean;
}

function toRequestBody(input: GymSettingsInput): Record<string, unknown> {
  return {
    name: input.name,
    phone: input.phone,
    email: input.email,
    address: input.address,
    website: input.website,
    working_days: input.workingDays,
    open_time: input.openTime,
    close_time: input.closeTime,
    accent_color: input.accentColor,
    currency: input.currency,
    timezone: input.timezone,
    language: input.language,
    notify_email: input.notifyEmail,
    notify_push: input.notifyPush,
    notify_sms: input.notifySms,
    notify_whatsapp: input.notifyWhatsapp,
  };
}

export const settingsService = {
  async get(signal?: AbortSignal): Promise<GymSettings> {
    const response = await apiClient.get<ApiItemResponse<GymSettingsPayload>>("/settings", { signal });
    return toSettings(response.data);
  },

  async update(input: GymSettingsInput): Promise<GymSettings> {
    const response = await apiClient.put<ApiItemResponse<GymSettingsPayload>>("/settings", toRequestBody(input));
    return toSettings(response.data);
  },

  async uploadLogo(file: File): Promise<string> {
    const formData = new FormData();
    formData.append("logo", file);
    const response = await apiClient.post<ApiItemResponse<GymSettingsPayload>>("/settings/logo", formData);
    return response.data.logoUrl ?? "";
  },
};
