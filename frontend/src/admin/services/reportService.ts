// Wraps GET /reports/* — each endpoint returns a filtered, paginated table
// plus a summary and a chart derived from the *same* filtered rows, so this
// service reuses the response-mapping functions already defined by the
// per-resource services instead of re-implementing them.
import { apiClient, type ApiItemResponse, type ApiMeta, type QueryParams } from "./apiClient";
import { toMember, type MemberPayload } from "./memberService";
import { toMembership, type MembershipPayload } from "./membershipService";
import { toRecord as toAttendanceRecord, type AttendancePayload } from "./attendanceService";
import { toPayment, type PaymentPayload } from "./paymentService";
import { toExpense, type ExpensePayload } from "./expenseService";
import { toTrainer, type TrainerPayload } from "./trainerService";
import { toClass, type ClassPayload } from "./classService";
import { toEquipment, type EquipmentPayload } from "./equipmentService";
import type { Member, Membership, AttendanceRecord, Payment, Expense, Trainer, GymClass, EquipmentItem } from "../types";

export interface ReportChartPoint {
  period: string;
  label: string;
  value: number;
}

export interface ReportDateRangeParams {
  from?: string;
  to?: string;
  group?: "daily" | "weekly" | "monthly" | "yearly";
  search?: string;
  page?: number;
  perPage?: number;
}

interface ReportEnvelope<TSummary, TRow> {
  summary: TSummary;
  chart: ReportChartPoint[];
  meta: ApiMeta;
  data: TRow[];
}

function toQuery(params: ReportDateRangeParams, extra?: QueryParams): QueryParams {
  return {
    from: params.from,
    to: params.to,
    group: params.group,
    search: params.search,
    page: params.page,
    per_page: params.perPage,
    ...extra,
  };
}

export interface MembersReportSummary {
  total: number;
  active: number;
  inactive: number;
  suspended: number;
  expired: number;
  averageAttendanceRate: number;
  totalBalanceDue: number;
}

export interface MembershipsReportSummary {
  total: number;
  active: number;
  expiringSoon: number;
  expired: number;
  suspended: number;
  totalValue: number;
}

export interface AttendanceReportSummary {
  totalVisits: number;
  uniqueMembers: number;
  checkedIn: number;
  checkedOut: number;
}

export interface RevenueReportSummary {
  total: number;
  count: number;
  average: number;
  byMethod: { method: string; amount: number }[];
}

export interface ExpensesReportSummary {
  total: number;
  count: number;
  average: number;
  byCategory: { category: string; amount: number }[];
}

export interface TrainersReportSummary {
  total: number;
  active: number;
  onLeave: number;
  inactive: number;
  averageRating: number;
  totalSessionsCompleted: number;
}

export interface ClassesReportSummary {
  total: number;
  scheduled: number;
  full: number;
  cancelled: number;
  completed: number;
  totalBookings: number;
  averageFillRate: number;
}

export interface EquipmentReportSummary {
  total: number;
  byStatus: Record<string, number>;
  byCondition: Record<string, number>;
}

export const reportService = {
  async members(params: ReportDateRangeParams & { status?: string; planId?: string; trainerId?: string } = {}, signal?: AbortSignal) {
    const response = await apiClient.get<ApiItemResponse<ReportEnvelope<MembersReportSummary, MemberPayload>>>("/reports/members", {
      params: toQuery(params, { status: params.status, plan_id: params.planId, trainer_id: params.trainerId }),
      signal,
    });
    return { ...response.data, data: response.data.data.map(toMember) } as ReportEnvelope<MembersReportSummary, Member>;
  },

  async memberships(params: ReportDateRangeParams & { status?: string; planId?: string } = {}, signal?: AbortSignal) {
    const response = await apiClient.get<ApiItemResponse<ReportEnvelope<MembershipsReportSummary, MembershipPayload>>>("/reports/memberships", {
      params: toQuery(params, { status: params.status, plan_id: params.planId }),
      signal,
    });
    return { ...response.data, data: response.data.data.map(toMembership) } as ReportEnvelope<MembershipsReportSummary, Membership>;
  },

  async attendance(params: ReportDateRangeParams & { memberId?: string; method?: string } = {}, signal?: AbortSignal) {
    const response = await apiClient.get<ApiItemResponse<ReportEnvelope<AttendanceReportSummary, AttendancePayload>>>("/reports/attendance", {
      params: toQuery(params, { member_id: params.memberId, method: params.method }),
      signal,
    });
    return { ...response.data, data: response.data.data.map(toAttendanceRecord) } as ReportEnvelope<AttendanceReportSummary, AttendanceRecord>;
  },

  async revenue(params: ReportDateRangeParams & { status?: string; method?: string } = {}, signal?: AbortSignal) {
    const response = await apiClient.get<ApiItemResponse<ReportEnvelope<RevenueReportSummary, PaymentPayload>>>("/reports/revenue", {
      params: toQuery(params, { status: params.status, method: params.method }),
      signal,
    });
    return { ...response.data, data: response.data.data.map(toPayment) } as ReportEnvelope<RevenueReportSummary, Payment>;
  },

  async expenses(params: ReportDateRangeParams & { category?: string } = {}, signal?: AbortSignal) {
    const response = await apiClient.get<ApiItemResponse<ReportEnvelope<ExpensesReportSummary, ExpensePayload>>>("/reports/expenses", {
      params: toQuery(params, { category: params.category }),
      signal,
    });
    return { ...response.data, data: response.data.data.map(toExpense) } as ReportEnvelope<ExpensesReportSummary, Expense>;
  },

  async trainers(params: Pick<ReportDateRangeParams, "search" | "page" | "perPage"> & { status?: string; specialty?: string } = {}, signal?: AbortSignal) {
    const response = await apiClient.get<ApiItemResponse<ReportEnvelope<TrainersReportSummary, TrainerPayload>>>("/reports/trainers", {
      params: toQuery(params, { status: params.status, specialty: params.specialty }),
      signal,
    });
    return { ...response.data, data: response.data.data.map(toTrainer) } as ReportEnvelope<TrainersReportSummary, Trainer>;
  },

  async classes(params: ReportDateRangeParams & { status?: string; category?: string; trainerId?: string } = {}, signal?: AbortSignal) {
    const response = await apiClient.get<ApiItemResponse<ReportEnvelope<ClassesReportSummary, ClassPayload>>>("/reports/classes", {
      params: toQuery(params, { status: params.status, category: params.category, trainer_id: params.trainerId }),
      signal,
    });
    return { ...response.data, data: response.data.data.map(toClass) } as ReportEnvelope<ClassesReportSummary, GymClass>;
  },

  async equipment(params: Pick<ReportDateRangeParams, "search" | "page" | "perPage"> & { status?: string; condition?: string; category?: string } = {}, signal?: AbortSignal) {
    const response = await apiClient.get<ApiItemResponse<ReportEnvelope<EquipmentReportSummary, EquipmentPayload>>>("/reports/equipment", {
      params: toQuery(params, { status: params.status, condition: params.condition, category: params.category }),
      signal,
    });
    return { ...response.data, data: response.data.data.map(toEquipment) } as ReportEnvelope<EquipmentReportSummary, EquipmentItem>;
  },
};
