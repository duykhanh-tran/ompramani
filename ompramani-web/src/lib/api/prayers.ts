import { apiClient } from './client';
import {
  PublicPrayerIntention,
  MyPrayerIntention,
  MyPrayerParticipation,
  PrayerCategoryOption,
  CreatePrayerIntentionPayload,
  UpdatePrayerIntentionPayload,
  ParticipationMutationResponse,
  BatchParticipationStatusResponse,
  PaginatedResponse,
} from '@/types/prayer';

/**
 * Fetch approved community prayer intentions (public feed).
 */
export async function getPublicPrayerIntentions(params?: {
  page?: number;
  per_page?: number;
  category?: string;
}): Promise<PaginatedResponse<PublicPrayerIntention>> {
  const query = new URLSearchParams();
  if (params?.page) query.set('page', params.page.toString());
  if (params?.per_page) query.set('per_page', params.per_page.toString());
  if (params?.category) query.set('category', params.category);

  const qs = query.toString();
  return apiClient<PaginatedResponse<PublicPrayerIntention>>(
    `/api/v1/prayer-intentions${qs ? `?${qs}` : ''}`,
    {
      cache: 'no-store',
    }
  );
}

/**
 * Fetch a single public approved prayer intention by its public ULID.
 */
export async function getPublicPrayerIntention(
  publicId: string
): Promise<{ data: PublicPrayerIntention }> {
  return apiClient<{ data: PublicPrayerIntention }>(
    `/api/v1/prayer-intentions/${encodeURIComponent(publicId)}`,
    {
      cache: 'no-store',
    }
  );
}

/**
 * Fetch list of valid categories.
 */
export async function getPrayerCategories(): Promise<{ data: PrayerCategoryOption[] }> {
  return apiClient<{ data: PrayerCategoryOption[] }>(
    '/api/v1/prayer-intentions/categories',
    {
      cache: 'default',
    }
  );
}

/**
 * Fetch authenticated user's personal intentions.
 */
export async function getMyPrayerIntentions(params?: {
  page?: number;
  per_page?: number;
  tab?: string;
  category?: string;
}): Promise<PaginatedResponse<MyPrayerIntention>> {
  const query = new URLSearchParams();
  if (params?.page) query.set('page', params.page.toString());
  if (params?.per_page) query.set('per_page', params.per_page.toString());
  if (params?.tab) query.set('tab', params.tab);
  if (params?.category) query.set('category', params.category);

  const qs = query.toString();
  return apiClient<PaginatedResponse<MyPrayerIntention>>(
    `/api/v1/me/prayer-intentions${qs ? `?${qs}` : ''}`,
    {
      cache: 'no-store',
    }
  );
}

/**
 * Fetch a single personal intention.
 */
export async function getMyPrayerIntention(
  id: number
): Promise<{ data: MyPrayerIntention }> {
  return apiClient<{ data: MyPrayerIntention }>(
    `/api/v1/me/prayer-intentions/${id}`,
    {
      cache: 'no-store',
    }
  );
}

/**
 * Create a new intention (default private unless public is selected & confirmed).
 */
export async function createPrayerIntention(
  payload: CreatePrayerIntentionPayload
): Promise<{ data: MyPrayerIntention }> {
  return apiClient<{ data: MyPrayerIntention }>('/api/v1/me/prayer-intentions', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

/**
 * Update an existing personal intention.
 */
export async function updatePrayerIntention(
  id: number,
  payload: UpdatePrayerIntentionPayload
): Promise<{ data: MyPrayerIntention }> {
  return apiClient<{ data: MyPrayerIntention }>(`/api/v1/me/prayer-intentions/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(payload),
  });
}

/**
 * Delete a personal intention.
 */
export async function deletePrayerIntention(
  id: number
): Promise<{ message: string }> {
  return apiClient<{ message: string }>(`/api/v1/me/prayer-intentions/${id}`, {
    method: 'DELETE',
  });
}

/**
 * Explicitly submit an existing intention for public moderation.
 */
export async function submitPrayerIntentionForReview(
  id: number,
  consent: boolean
): Promise<{ data: MyPrayerIntention }> {
  return apiClient<{ data: MyPrayerIntention }>(
    `/api/v1/me/prayer-intentions/${id}/submit`,
    {
      method: 'POST',
      body: JSON.stringify({ consent_public_sharing: consent }),
    }
  );
}

/**
 * Stop sharing / withdraw an intention immediately.
 */
export async function withdrawPrayerIntention(
  id: number
): Promise<{ data: MyPrayerIntention }> {
  return apiClient<{ data: MyPrayerIntention }>(
    `/api/v1/me/prayer-intentions/${id}/withdraw`,
    {
      method: 'POST',
    }
  );
}

/**
 * Express participation in a public prayer intention (PUT /api/v1/me/prayer-participations/{publicId})
 */
export async function participatePrayer(
  publicId: string
): Promise<ParticipationMutationResponse> {
  return apiClient<ParticipationMutationResponse>(
    `/api/v1/me/prayer-participations/${encodeURIComponent(publicId)}`,
    {
      method: 'PUT',
    }
  );
}

/**
 * Withdraw participation from a prayer intention (DELETE /api/v1/me/prayer-participations/{publicId})
 */
export async function withdrawPrayerParticipation(
  publicId: string
): Promise<ParticipationMutationResponse> {
  return apiClient<ParticipationMutationResponse>(
    `/api/v1/me/prayer-participations/${encodeURIComponent(publicId)}`,
    {
      method: 'DELETE',
    }
  );
}

/**
 * Batch lookup of viewer participation statuses (GET /api/v1/me/prayer-participations/status?public_ids=...)
 */
export async function getPrayerParticipationStatuses(
  publicIds: string[]
): Promise<BatchParticipationStatusResponse> {
  if (!publicIds.length) {
    return { data: { statuses: {} } };
  }
  const clean = publicIds.slice(0, 50).join(',');
  return apiClient<BatchParticipationStatusResponse>(
    `/api/v1/me/prayer-participations/status?public_ids=${encodeURIComponent(clean)}`,
    {
      cache: 'no-store',
    }
  );
}

/**
 * Get personal participation history (GET /api/v1/me/prayer-participations)
 */
export async function getMyPrayerParticipations(params?: {
  page?: number;
  per_page?: number;
}): Promise<PaginatedResponse<MyPrayerParticipation>> {
  const query = new URLSearchParams();
  if (params?.page) query.set('page', params.page.toString());
  if (params?.per_page) query.set('per_page', params.per_page.toString());

  const qs = query.toString();
  return apiClient<PaginatedResponse<MyPrayerParticipation>>(
    `/api/v1/me/prayer-participations${qs ? `?${qs}` : ''}`,
    {
      cache: 'no-store',
    }
  );
}
