import { apiClient, type ApiItemResponse } from "./apiClient";
import { toClass, type ClassPayload } from "./classService";
import type { GymClass } from "../types";

export interface ScheduleDay {
  date: string;
  day: string;
  classes: GymClass[];
}

interface DayPayload {
  date: string;
  day: string;
  classes: ClassPayload[];
}

export const scheduleService = {
  async daily(date?: string, signal?: AbortSignal): Promise<ScheduleDay> {
    const response = await apiClient.get<ApiItemResponse<DayPayload>>("/schedule/daily", { params: { date }, signal });
    return { ...response.data, classes: response.data.classes.map(toClass) };
  },

  async weekly(date?: string, signal?: AbortSignal): Promise<{ weekStart: string; weekEnd: string; days: ScheduleDay[] }> {
    const response = await apiClient.get<ApiItemResponse<{ weekStart: string; weekEnd: string; days: DayPayload[] }>>(
      "/schedule/weekly",
      { params: { date }, signal },
    );
    return {
      weekStart: response.data.weekStart,
      weekEnd: response.data.weekEnd,
      days: response.data.days.map((d) => ({ ...d, classes: d.classes.map(toClass) })),
    };
  },

  async monthly(month?: string, signal?: AbortSignal): Promise<{ month: string; days: ScheduleDay[] }> {
    const response = await apiClient.get<ApiItemResponse<{ month: string; days: DayPayload[] }>>("/schedule/monthly", {
      params: { month },
      signal,
    });
    return { month: response.data.month, days: response.data.days.map((d) => ({ ...d, classes: d.classes.map(toClass) })) };
  },
};
