// Talks to the Phase 22 AI endpoints (/ai/*). Every response here is
// real, tenant-scoped data from the Laravel AI layer — nothing in this
// file, or anything that calls it, ever fabricates a number or answer.
import { apiClient, type ApiItemResponse } from "./apiClient";

export type InsightDomain = "members" | "attendance" | "revenue" | "classes" | "equipment";
export const INSIGHT_DOMAINS: InsightDomain[] = ["members", "attendance", "revenue", "classes", "equipment"];

export type ReportType = "revenue" | "attendance" | "membership_growth" | "expenses";
export const REPORT_TYPES: ReportType[] = ["revenue", "attendance", "membership_growth", "expenses"];

export interface AssistantAnswer {
  question: string;
  answer: string;
  data: {
    activeMembers: number;
    membershipsExpiringThisWeek: { count: number; members: { name: string; expiryDate: string; planName: string | null }[] };
    thisMonthRevenue: number;
    popularClasses: { name: string; category: string | null; booked: number; capacity: number; fillRate: number }[];
    equipmentNeedingMaintenance: { overdueCount: number; upcomingCount: number; equipment: { name: string; status: string; dueDate: string | null }[] };
    lowAttendanceMembers: { name: string; attendanceRate: number }[];
  };
}

export interface Insight {
  domain: InsightDomain;
  metrics: Record<string, unknown>;
  summary: string;
  generatedAt: string;
}

export interface MemberInsight {
  memberId: string;
  data: {
    name: string;
    status: string;
    joinDate: string | null;
    attendanceRate: number;
    attendanceLast30Days: number;
    membership: { planName: string | null; status: string; expiryDate: string } | null;
    trainingProgramsEnrolled: number;
    hasPersonalTraining: boolean;
    balanceDue: number;
  };
  suggestedActions: string;
  generatedAt: string;
}

export interface ReportSummary {
  reportType: ReportType;
  data: Record<string, unknown>;
  summary: string;
  generatedAt: string;
}

export interface AIUsageStats {
  used: number;
  limit: number | null;
  remaining: number | null;
  totalTokensThisPeriod: number;
  byFeature: Record<string, number>;
  periodStart: string;
  periodEnd: string;
}

export const aiService = {
  async ask(question: string, signal?: AbortSignal): Promise<AssistantAnswer> {
    const response = await apiClient.post<ApiItemResponse<AssistantAnswer>>("/ai/assistant", { question }, { signal });
    return response.data;
  },

  async insight(domain: InsightDomain, signal?: AbortSignal): Promise<Insight> {
    const response = await apiClient.get<ApiItemResponse<Insight>>(`/ai/insights/${domain}`, { signal });
    return response.data;
  },

  async memberInsight(memberId: string, signal?: AbortSignal): Promise<MemberInsight> {
    const response = await apiClient.get<ApiItemResponse<MemberInsight>>(`/ai/members/${memberId}/insights`, { signal });
    return response.data;
  },

  async summarizeReport(reportType: ReportType, months?: number, signal?: AbortSignal): Promise<ReportSummary> {
    const response = await apiClient.post<ApiItemResponse<ReportSummary>>(
      "/ai/reports/summarize",
      { report_type: reportType, months },
      { signal },
    );
    return response.data;
  },

  async usage(signal?: AbortSignal): Promise<AIUsageStats> {
    const response = await apiClient.get<ApiItemResponse<AIUsageStats>>("/ai/usage", { signal });
    return response.data;
  },
};
