import { apiClient, type ApiItemResponse, type ApiListResponse, type ApiMeta, type QueryParams } from "./apiClient";
import type { AttendanceRecord, Member, Note, Payment } from "../types";

interface AttendancePayload {
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

function toAttendance(a: AttendancePayload): AttendanceRecord {
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

interface PaymentPayload {
  id: number;
  invoiceId: number | null;
  memberId: number;
  memberName: string | null;
  memberAvatar: string | null;
  amount: number;
  method: Payment["method"];
  date: string;
  status: Payment["status"];
}

function toPayment(p: PaymentPayload): Payment {
  return {
    id: String(p.id),
    invoiceId: p.invoiceId !== null ? String(p.invoiceId) : "",
    memberId: String(p.memberId),
    memberName: p.memberName ?? "",
    memberAvatar: p.memberAvatar ?? "",
    amount: p.amount,
    method: p.method,
    date: p.date,
    status: p.status,
  };
}

interface NotePayload {
  id: number;
  author: string;
  date: string;
  text: string;
}

export interface MemberPayload {
  id: number;
  memberId: string;
  name: string;
  avatar: string | null;
  gender: Member["gender"];
  phone: string;
  email: string;
  address: string | null;
  dob: string | null;
  joinDate: string;
  planId: number | null;
  planName: string | null;
  startDate: string | null;
  expiryDate: string | null;
  status: Member["status"];
  attendanceRate: number;
  trainerId: number | null;
  trainerName: string | null;
  balanceDue: number;
  notes: NotePayload[];
  emergencyContact: string | null;
}

function toNote(n: NotePayload): Note {
  return { id: String(n.id), author: n.author, date: n.date, text: n.text };
}

export function toMember(m: MemberPayload): Member {
  return {
    id: String(m.id),
    memberId: m.memberId,
    name: m.name,
    avatar: m.avatar ?? "",
    gender: m.gender,
    phone: m.phone,
    email: m.email,
    address: m.address ?? "",
    dob: m.dob ?? "",
    joinDate: m.joinDate,
    planId: m.planId !== null ? String(m.planId) : "",
    planName: m.planName ?? "",
    startDate: m.startDate ?? "",
    expiryDate: m.expiryDate ?? "",
    status: m.status,
    attendanceRate: m.attendanceRate,
    trainerId: m.trainerId !== null ? String(m.trainerId) : undefined,
    trainerName: m.trainerName ?? undefined,
    balanceDue: m.balanceDue,
    notes: (m.notes ?? []).map(toNote),
    emergencyContact: m.emergencyContact ?? "",
  };
}

export interface MemberListParams {
  search?: string;
  status?: string;
  planId?: string;
  trainerId?: string;
  sortBy?: string;
  sortDir?: "asc" | "desc";
  page?: number;
  perPage?: number;
}

function toQuery(params: MemberListParams): QueryParams {
  return {
    search: params.search,
    status: params.status,
    plan_id: params.planId,
    trainer_id: params.trainerId,
    sort_by: params.sortBy,
    sort_dir: params.sortDir,
    page: params.page,
    per_page: params.perPage,
  };
}

export interface MemberInput {
  name: string;
  gender: Member["gender"];
  phone: string;
  email: string;
  address?: string;
  dob?: string;
  planId?: string;
  trainerId?: string;
  startDate?: string;
  expiryDate?: string;
  status?: Member["status"];
  emergencyContact?: string;
}

function toRequestBody(input: MemberInput): Record<string, unknown> {
  return {
    name: input.name,
    gender: input.gender,
    phone: input.phone,
    email: input.email,
    address: input.address || undefined,
    dob: input.dob || undefined,
    plan_id: input.planId ? Number(input.planId) : undefined,
    trainer_id: input.trainerId ? Number(input.trainerId) : undefined,
    start_date: input.startDate || undefined,
    expiry_date: input.expiryDate || undefined,
    status: input.status,
    emergency_contact: input.emergencyContact || undefined,
  };
}

export const memberService = {
  async list(params: MemberListParams = {}, signal?: AbortSignal): Promise<{ data: Member[]; meta: ApiMeta }> {
    const response = await apiClient.get<ApiListResponse<MemberPayload>>("/members", { params: toQuery(params), signal });
    return { data: response.data.map(toMember), meta: response.meta };
  },

  async getById(id: string, signal?: AbortSignal): Promise<Member> {
    const response = await apiClient.get<ApiItemResponse<MemberPayload>>(`/members/${id}`, { signal });
    return toMember(response.data);
  },

  async create(input: MemberInput): Promise<Member> {
    const response = await apiClient.post<ApiItemResponse<MemberPayload>>("/members", toRequestBody(input));
    return toMember(response.data);
  },

  async update(id: string, input: MemberInput): Promise<Member> {
    const response = await apiClient.put<ApiItemResponse<MemberPayload>>(`/members/${id}`, toRequestBody(input));
    return toMember(response.data);
  },

  async updateStatus(id: string, status: Member["status"]): Promise<Member> {
    const response = await apiClient.patch<ApiItemResponse<MemberPayload>>(`/members/${id}/status`, { status });
    return toMember(response.data);
  },

  async remove(id: string): Promise<void> {
    await apiClient.delete(`/members/${id}`);
  },

  async listAttendance(id: string, perPage = 10, signal?: AbortSignal): Promise<AttendanceRecord[]> {
    const response = await apiClient.get<ApiListResponse<AttendancePayload>>(`/members/${id}/attendance`, {
      params: { per_page: perPage },
      signal,
    });
    return response.data.map(toAttendance);
  },

  async listPayments(id: string, perPage = 10, signal?: AbortSignal): Promise<Payment[]> {
    const response = await apiClient.get<ApiListResponse<PaymentPayload>>(`/members/${id}/payments`, {
      params: { per_page: perPage },
      signal,
    });
    return response.data.map(toPayment);
  },

  async addNote(id: string, text: string): Promise<Member> {
    const response = await apiClient.post<ApiItemResponse<MemberPayload>>(`/members/${id}/notes`, { text });
    return toMember(response.data);
  },
};
