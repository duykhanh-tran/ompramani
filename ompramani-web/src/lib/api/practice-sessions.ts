import { apiClient, ApiError } from "./client";
import { getCsrfCookie } from "./auth";
import {
  CompletePracticePayload,
  PracticeSession,
  PracticeSessionListResponse,
  StartPracticePayload,
  UpdateProgressPayload,
} from "@/types/practice";

interface SingleSessionResponse {
  data: PracticeSession;
}

interface ActiveSessionResponse {
  data: PracticeSession | null;
}

/**
 * Start a new practice session for the authenticated user.
 */
export async function startPracticeSession(
  payload: StartPracticePayload
): Promise<PracticeSession> {
  await getCsrfCookie();

  const res = await apiClient<SingleSessionResponse>(
    "/api/v1/me/practice-sessions",
    {
      method: "POST",
      body: JSON.stringify(payload),
      cache: "no-store",
    }
  );

  return res.data;
}

/**
 * Get current active or paused practice session.
 * Returns null if no session is active or user is unauthenticated (401).
 */
export async function getActivePracticeSession(): Promise<PracticeSession | null> {
  try {
    const res = await apiClient<ActiveSessionResponse>(
      "/api/v1/me/practice-sessions/active",
      {
        method: "GET",
        cache: "no-store",
      }
    );

    return res.data;
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      return null;
    }
    throw error;
  }
}

/**
 * Get paginated practice sessions list for the authenticated user.
 */
export async function getPracticeSessions(params?: {
  practice_type?: string;
  status?: string;
  from?: string;
  to?: string;
  page?: number;
  per_page?: number;
}): Promise<PracticeSessionListResponse> {
  const query = new URLSearchParams();
  if (params?.practice_type) query.set("practice_type", params.practice_type);
  if (params?.status) query.set("status", params.status);
  if (params?.from) query.set("from", params.from);
  if (params?.to) query.set("to", params.to);
  if (params?.page) query.set("page", String(params.page));
  if (params?.per_page) query.set("per_page", String(params.per_page));

  const qs = query.toString();
  const endpoint = `/api/v1/me/practice-sessions${qs ? `?${qs}` : ""}`;

  return await apiClient<PracticeSessionListResponse>(endpoint, {
    method: "GET",
    cache: "no-store",
  });
}

/**
 * Get details of a specific practice session.
 */
export async function getPracticeSession(id: number): Promise<PracticeSession> {
  const res = await apiClient<SingleSessionResponse>(
    `/api/v1/me/practice-sessions/${id}`,
    {
      method: "GET",
      cache: "no-store",
    }
  );

  return res.data;
}

/**
 * Pause an active practice session.
 */
export async function pausePracticeSession(id: number): Promise<PracticeSession> {
  await getCsrfCookie();

  const res = await apiClient<SingleSessionResponse>(
    `/api/v1/me/practice-sessions/${id}/pause`,
    {
      method: "POST",
      cache: "no-store",
    }
  );

  return res.data;
}

/**
 * Resume a paused practice session.
 */
export async function resumePracticeSession(id: number): Promise<PracticeSession> {
  await getCsrfCookie();

  const res = await apiClient<SingleSessionResponse>(
    `/api/v1/me/practice-sessions/${id}/resume`,
    {
      method: "POST",
      cache: "no-store",
    }
  );

  return res.data;
}

/**
 * Send heartbeat to credit active elapsed duration.
 */
export async function heartbeatPracticeSession(id: number): Promise<PracticeSession> {
  await getCsrfCookie();

  const res = await apiClient<SingleSessionResponse>(
    `/api/v1/me/practice-sessions/${id}/heartbeat`,
    {
      method: "POST",
      cache: "no-store",
    }
  );

  return res.data;
}

/**
 * Update session progress data safely.
 */
export async function updatePracticeProgress(
  id: number,
  payload: UpdateProgressPayload
): Promise<PracticeSession> {
  await getCsrfCookie();

  const res = await apiClient<SingleSessionResponse>(
    `/api/v1/me/practice-sessions/${id}/progress`,
    {
      method: "PATCH",
      body: JSON.stringify(payload),
      cache: "no-store",
    }
  );

  return res.data;
}

/**
 * Complete a practice session.
 */
export async function completePracticeSession(
  id: number,
  payload?: CompletePracticePayload
): Promise<PracticeSession> {
  await getCsrfCookie();

  const res = await apiClient<SingleSessionResponse>(
    `/api/v1/me/practice-sessions/${id}/complete`,
    {
      method: "POST",
      body: JSON.stringify(payload ?? {}),
      cache: "no-store",
    }
  );

  return res.data;
}

/**
 * Cancel an ongoing practice session.
 */
export async function cancelPracticeSession(id: number): Promise<PracticeSession> {
  await getCsrfCookie();

  const res = await apiClient<SingleSessionResponse>(
    `/api/v1/me/practice-sessions/${id}/cancel`,
    {
      method: "POST",
      cache: "no-store",
    }
  );

  return res.data;
}
