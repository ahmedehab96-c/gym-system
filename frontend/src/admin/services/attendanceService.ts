import { apiClient, type ApiItemResponse, type ApiListResponse, type ApiMeta, type QueryParams } from "./apiClient";
import { toMember, type MemberPayload } from "./memberService";
import type { AttendanceRecord, Member } from "../types";

export interface AttendancePayload {
  id: number;
  memberId: number;
  memberName: string | null;
  memberAvatar: string | null;
  date: string;
  checkIn: string;
  checkOut: string | null;
  duration: string | null;
  method: AttendanceRecord["method"];
}

export function toRecord(a: AttendancePayload): AttendanceRecord {
  return {
    id: String(a.id),
    memberId: String(a.memberId),
    memberName: a.memberName ?? "",
    memberAvatar: a.memberAvatar ?? "",
    date: a.date,
    checkIn: a.checkIn,
    checkOut: a.checkOut,
    duration: a.duration,
    method: a.method,
  };
}

export interface AttendanceListParams {
  search?: string;
  date?: string;
  status?: "Checked In" | "Checked Out";
  memberId?: string;
  page?: number;
  perPage?: number;
}

function toQuery(params: AttendanceListParams): QueryParams {
  return {
    search: params.search,
    date: params.date,
    status: params.status,
    member_id: params.memberId,
    page: params.page,
    per_page: params.perPage,
  };
}

export interface AttendanceStats {
  today: { presentToday: number; checkedIn: number; checkedOut: number; averageAttendanceRate: number };
  daily: { date: string; day: string; visits: number }[];
  weekly: { date: string; day: string; visits: number }[];
  monthly: { month: string; visits: number }[];
  averageSessionMinutes: number;
}

export const attendanceService = {
  async list(params: AttendanceListParams = {}, signal?: AbortSignal): Promise<{ data: AttendanceRecord[]; meta: ApiMeta }> {
    const response = await apiClient.get<ApiListResponse<AttendancePayload>>("/attendance", { params: toQuery(params), signal });
    return { data: response.data.map(toRecord), meta: response.meta };
  },

  async today(signal?: AbortSignal): Promise<AttendanceRecord[]> {
    const response = await apiClient.get<ApiItemResponse<AttendancePayload[]>>("/attendance/today", { signal });
    return response.data.map(toRecord);
  },

  async stats(signal?: AbortSignal): Promise<AttendanceStats> {
    const response = await apiClient.get<ApiItemResponse<AttendanceStats>>("/attendance/stats", { signal });
    return response.data;
  },

  async checkIn(memberId: string, method?: AttendanceRecord["method"]): Promise<AttendanceRecord> {
    const response = await apiClient.post<ApiItemResponse<AttendancePayload>>("/attendance/check-in", {
      member_id: Number(memberId),
      method,
    });
    return toRecord(response.data);
  },

  async checkOut(id: string): Promise<AttendanceRecord> {
    const response = await apiClient.post<ApiItemResponse<AttendancePayload>>(`/attendance/${id}/check-out`);
    return toRecord(response.data);
  },

  async qrCheckIn(token: string): Promise<QrScanResult> {
    const response = await apiClient.post<ApiItemResponse<QrScanPayload>>("/qr/check-in", { token });
    return { member: toMember(response.data.member), attendance: toRecord(response.data.attendance), duplicate: response.data.duplicate };
  },

  async qrCheckOut(token: string): Promise<Omit<QrScanResult, "duplicate">> {
    const response = await apiClient.post<ApiItemResponse<Omit<QrScanPayload, "duplicate">>>("/qr/check-out", { token });
    return { member: toMember(response.data.member), attendance: toRecord(response.data.attendance) };
  },
};

interface QrScanPayload {
  member: MemberPayload;
  attendance: AttendancePayload;
  duplicate: boolean;
}

export interface QrScanResult {
  member: Member;
  attendance: AttendanceRecord;
  duplicate: boolean;
}
