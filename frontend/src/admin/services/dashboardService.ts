import { apiClient, type ApiItemResponse } from "./apiClient";
import type { GymClass, Member, Membership, Payment } from "../types";

export interface DashboardStats {
  totalMembers: number;
  activeMembers: number;
  newMembers: number;
  expiredMemberships: number;
  todayAttendance: number;
  monthlyRevenue: number;
  pendingPayments: number;
  activeTrainers: number;
  expiringMemberships: number;
}

interface RawMember extends Omit<Member, "id" | "planId" | "trainerId" | "notes"> {
  id: number;
  planId: number | null;
  trainerId: number | null;
  notes: unknown[];
}
interface RawPayment extends Omit<Payment, "id" | "invoiceId" | "memberId"> {
  id: number;
  invoiceId: number | null;
  memberId: number;
}
interface RawClass extends Omit<GymClass, "id" | "trainerId"> {
  id: number;
  trainerId: number;
}
interface RawMembership extends Omit<Membership, "id" | "memberId" | "planId"> {
  id: number;
  memberId: number;
  planId: number;
}

export interface DashboardSummary {
  stats: DashboardStats;
  recentMembers: Member[];
  recentPayments: Payment[];
  upcomingClasses: GymClass[];
  expiringMemberships: Membership[];
}

export interface ActivityEventPayload {
  id: number;
  type: string;
  title: string;
  description: string;
  memberId: number | null;
  date: string;
}

export const dashboardService = {
  async summary(signal?: AbortSignal): Promise<DashboardSummary> {
    const response = await apiClient.get<
      ApiItemResponse<{
        stats: DashboardStats;
        recentMembers: RawMember[];
        recentPayments: RawPayment[];
        upcomingClasses: RawClass[];
        expiringMemberships: RawMembership[];
      }>
    >("/dashboard/summary", { signal });

    const { stats, recentMembers, recentPayments, upcomingClasses, expiringMemberships } = response.data;

    return {
      stats,
      recentMembers: recentMembers.map((m) => ({
        ...m,
        id: String(m.id),
        planId: m.planId !== null ? String(m.planId) : "",
        trainerId: m.trainerId !== null ? String(m.trainerId) : undefined,
        notes: [],
      })),
      recentPayments: recentPayments.map((p) => ({ ...p, id: String(p.id), invoiceId: p.invoiceId !== null ? String(p.invoiceId) : "", memberId: String(p.memberId) })),
      upcomingClasses: upcomingClasses.map((c) => ({ ...c, id: String(c.id), trainerId: String(c.trainerId) })),
      expiringMemberships: expiringMemberships.map((m) => ({ ...m, id: String(m.id), memberId: String(m.memberId), planId: String(m.planId) })),
    };
  },

  async activity(limit = 10, signal?: AbortSignal): Promise<ActivityEventPayload[]> {
    const response = await apiClient.get<ApiItemResponse<ActivityEventPayload[]>>("/dashboard/activity", {
      params: { limit },
      signal,
    });
    return response.data;
  },
};
