import { apiClient } from './client';
import {
  PracticePlan,
  PracticePlanItem,
  PracticePlanOccurrence,
  CreatePracticePlanInput,
  UpdatePracticePlanInput,
  CreatePracticePlanItemInput,
  UpdatePracticePlanItemInput,
  CandidatePracticeSession,
  PracticePlanListResponse,
  PracticePlanSingleResponse,
  PracticePlanItemListResponse,
  PracticePlanItemSingleResponse,
  PracticeOccurrenceListResponse,
} from '@/types/practice-plan';

/**
 * Fetch all practice plans for the authenticated user.
 */
export async function getPracticePlans(): Promise<PracticePlan[]> {
  const res = await apiClient<PracticePlanListResponse>(
    '/api/v1/me/practice-plans',
    {
      cache: 'no-store',
    }
  );
  return res.data;
}

/**
 * Fetch a single practice plan with its items.
 */
export async function getPracticePlan(id: number): Promise<PracticePlan> {
  const res = await apiClient<PracticePlanSingleResponse>(
    `/api/v1/me/practice-plans/${id}`,
    {
      cache: 'no-store',
    }
  );
  return res.data;
}

/**
 * Create a new practice plan.
 */
export async function createPracticePlan(
  data: CreatePracticePlanInput
): Promise<PracticePlan> {
  const res = await apiClient<PracticePlanSingleResponse>(
    '/api/v1/me/practice-plans',
    {
      method: 'POST',
      body: JSON.stringify(data),
    }
  );
  return res.data;
}

/**
 * Update an existing practice plan.
 */
export async function updatePracticePlan(
  id: number,
  data: UpdatePracticePlanInput
): Promise<PracticePlan> {
  const res = await apiClient<PracticePlanSingleResponse>(
    `/api/v1/me/practice-plans/${id}`,
    {
      method: 'PATCH',
      body: JSON.stringify(data),
    }
  );
  return res.data;
}

/**
 * Delete a practice plan.
 */
export async function deletePracticePlan(id: number): Promise<void> {
  await apiClient<{ message?: string }>(
    `/api/v1/me/practice-plans/${id}`,
    {
      method: 'DELETE',
    }
  );
}

/**
 * Pause a practice plan.
 */
export async function pausePracticePlan(id: number): Promise<PracticePlan> {
  const res = await apiClient<PracticePlanSingleResponse>(
    `/api/v1/me/practice-plans/${id}/pause`,
    {
      method: 'POST',
    }
  );
  return res.data;
}

/**
 * Resume a practice plan.
 */
export async function resumePracticePlan(id: number): Promise<PracticePlan> {
  const res = await apiClient<PracticePlanSingleResponse>(
    `/api/v1/me/practice-plans/${id}/resume`,
    {
      method: 'POST',
    }
  );
  return res.data;
}

/**
 * Archive a practice plan.
 */
export async function archivePracticePlan(id: number): Promise<PracticePlan> {
  const res = await apiClient<PracticePlanSingleResponse>(
    `/api/v1/me/practice-plans/${id}/archive`,
    {
      method: 'POST',
    }
  );
  return res.data;
}

/**
 * Fetch items for a practice plan.
 */
export async function getPracticePlanItems(planId: number): Promise<PracticePlanItem[]> {
  const res = await apiClient<PracticePlanItemListResponse>(
    `/api/v1/me/practice-plans/${planId}/items`,
    {
      cache: 'no-store',
    }
  );
  return res.data;
}

/**
 * Create a new practice plan item.
 */
export async function createPracticePlanItem(
  planId: number,
  data: CreatePracticePlanItemInput
): Promise<PracticePlanItem> {
  const res = await apiClient<PracticePlanItemSingleResponse>(
    `/api/v1/me/practice-plans/${planId}/items`,
    {
      method: 'POST',
      body: JSON.stringify(data),
    }
  );
  return res.data;
}

/**
 * Update a practice plan item.
 */
export async function updatePracticePlanItem(
  itemId: number,
  data: UpdatePracticePlanItemInput
): Promise<PracticePlanItem> {
  const res = await apiClient<PracticePlanItemSingleResponse>(
    `/api/v1/me/practice-plan-items/${itemId}`,
    {
      method: 'PATCH',
      body: JSON.stringify(data),
    }
  );
  return res.data;
}

/**
 * Delete a practice plan item.
 */
export async function deletePracticePlanItem(itemId: number): Promise<void> {
  await apiClient<{ message?: string }>(
    `/api/v1/me/practice-plan-items/${itemId}`,
    {
      method: 'DELETE',
    }
  );
}

/**
 * Get planned occurrences for a date range.
 */
export async function getPracticePlanOccurrences(
  from: string,
  to: string,
  planId?: number
): Promise<PracticePlanOccurrence[]> {
  const query = new URLSearchParams({ from, to });
  if (planId) {
    query.set('plan_id', planId.toString());
  }

  const res = await apiClient<PracticeOccurrenceListResponse>(
    `/api/v1/me/practice-plan-occurrences?${query.toString()}`,
    {
      cache: 'no-store',
    }
  );
  return res.data;
}

/**
 * Get today's planned occurrences.
 */
export async function getTodayOccurrences(
  planId?: number
): Promise<{ data: PracticePlanOccurrence[]; today: string; timezone: string }> {
  const query = new URLSearchParams();
  if (planId) {
    query.set('plan_id', planId.toString());
  }

  return apiClient<{ data: PracticePlanOccurrence[]; today: string; timezone: string }>(
    `/api/v1/me/practice-planner/today?${query.toString()}`,
    {
      cache: 'no-store',
    }
  );
}

/**
 * Get current week's planned occurrences (Monday to Sunday).
 */
export async function getWeekOccurrences(
  date?: string,
  planId?: number
): Promise<{
  data: PracticePlanOccurrence[];
  week: { from: string; to: string; today: string };
  timezone: string;
}> {
  const query = new URLSearchParams();
  if (date) query.set('date', date);
  if (planId) query.set('plan_id', planId.toString());

  return apiClient<{
    data: PracticePlanOccurrence[];
    week: { from: string; to: string; today: string };
    timezone: string;
  }>(
    `/api/v1/me/practice-planner/week?${query.toString()}`,
    {
      cache: 'no-store',
    }
  );
}

/**
 * Link an authoritative completed PracticeSession to a planned occurrence.
 */
export async function linkPracticeSession(
  planItemId: number,
  localDate: string,
  practiceSessionId: number
): Promise<void> {
  await apiClient<{ message: string }>(
    '/api/v1/me/practice-plan-occurrences/link-session',
    {
      method: 'POST',
      body: JSON.stringify({
        plan_item_id: planItemId,
        local_date: localDate,
        practice_session_id: practiceSessionId,
      }),
    }
  );
}

/**
 * Unlink a PracticeSession from an occurrence.
 */
export async function unlinkPracticeSession(
  planItemId: number,
  localDate: string
): Promise<void> {
  await apiClient<{ message: string }>(
    '/api/v1/me/practice-plan-occurrences/unlink-session',
    {
      method: 'DELETE',
      body: JSON.stringify({
        plan_item_id: planItemId,
        local_date: localDate,
      }),
    }
  );
}

/**
 * Get candidate completed PracticeSessions for linking.
 */
export async function getCandidateSessions(
  planItemId: number,
  localDate: string
): Promise<CandidatePracticeSession[]> {
  const query = new URLSearchParams({
    plan_item_id: planItemId.toString(),
    local_date: localDate,
  });

  const res = await apiClient<{ data: CandidatePracticeSession[] }>(
    `/api/v1/me/practice-plan-occurrences/candidate-sessions?${query.toString()}`,
    {
      cache: 'no-store',
    }
  );
  return res.data;
}

/**
 * Skip a planned occurrence for this date.
 */
export async function skipPracticeOccurrence(
  planItemId: number,
  localDate: string,
  reason?: string
): Promise<void> {
  await apiClient<{ message: string }>(
    '/api/v1/me/practice-plan-occurrences/skip',
    {
      method: 'POST',
      body: JSON.stringify({
        plan_item_id: planItemId,
        local_date: localDate,
        reason,
      }),
    }
  );
}

/**
 * Restore a skipped occurrence.
 */
export async function restorePracticeOccurrence(
  planItemId: number,
  localDate: string
): Promise<void> {
  await apiClient<{ message: string }>(
    '/api/v1/me/practice-plan-occurrences/restore',
    {
      method: 'POST',
      body: JSON.stringify({
        plan_item_id: planItemId,
        local_date: localDate,
      }),
    }
  );
}
