import { apiClient, type ApiItemResponse, type ApiListResponse, type ApiMeta, type QueryParams } from "./apiClient";

export interface PersonalTrainingSession {
  id: string;
  memberId: string;
  memberName: string;
  memberAvatar: string;
  trainerId: string;
  trainerName: string;
  goal: string;
  sessionsPerWeek: number;
}

interface PersonalTrainingSessionPayload {
  id: number;
  memberId: number;
  memberName: string | null;
  memberAvatar: string | null;
  trainerId: number;
  trainerName: string | null;
  goal: string;
  sessionsPerWeek: number;
}

function toSession(p: PersonalTrainingSessionPayload): PersonalTrainingSession {
  return {
    id: String(p.id),
    memberId: String(p.memberId),
    memberName: p.memberName ?? "",
    memberAvatar: p.memberAvatar ?? "",
    trainerId: String(p.trainerId),
    trainerName: p.trainerName ?? "",
    goal: p.goal,
    sessionsPerWeek: p.sessionsPerWeek,
  };
}

export interface PersonalTrainingListParams {
  search?: string;
  trainerId?: string;
  page?: number;
  perPage?: number;
}

function toQuery(params: PersonalTrainingListParams): QueryParams {
  return {
    search: params.search,
    trainer_id: params.trainerId,
    page: params.page,
    per_page: params.perPage,
  };
}

export interface PersonalTrainingInput {
  memberId: string;
  trainerId: string;
  goal: string;
  sessionsPerWeek?: number;
}

function toRequestBody(input: PersonalTrainingInput): Record<string, unknown> {
  return {
    member_id: Number(input.memberId),
    trainer_id: Number(input.trainerId),
    goal: input.goal,
    sessions_per_week: input.sessionsPerWeek,
  };
}

export const personalTrainingService = {
  async list(params: PersonalTrainingListParams = {}, signal?: AbortSignal): Promise<{ data: PersonalTrainingSession[]; meta: ApiMeta }> {
    const response = await apiClient.get<ApiListResponse<PersonalTrainingSessionPayload>>("/personal-training", {
      params: toQuery(params),
      signal,
    });
    return { data: response.data.map(toSession), meta: response.meta };
  },

  async create(input: PersonalTrainingInput): Promise<PersonalTrainingSession> {
    const response = await apiClient.post<ApiItemResponse<PersonalTrainingSessionPayload>>("/personal-training", toRequestBody(input));
    return toSession(response.data);
  },

  async update(id: string, input: Partial<PersonalTrainingInput>): Promise<PersonalTrainingSession> {
    const response = await apiClient.put<ApiItemResponse<PersonalTrainingSessionPayload>>(`/personal-training/${id}`, toRequestBody(input as PersonalTrainingInput));
    return toSession(response.data);
  },

  async remove(id: string): Promise<void> {
    await apiClient.delete(`/personal-training/${id}`);
  },
};
