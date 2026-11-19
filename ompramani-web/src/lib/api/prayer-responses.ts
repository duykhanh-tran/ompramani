import { apiClient } from './client';
import {
  PublicPrayerResponse,
  MyPrayerResponse,
  CreatePrayerResponsePayload,
  ReportPrayerResponsePayload,
} from '@/types/prayer-response';
import { PaginatedResponse } from '@/types/prayer';

/**
 * Fetch approved responses for a public prayer intention.
 */
export async function getPublicPrayerResponses(
  publicId: string,
  page = 1,
  perPage = 15
): Promise<PaginatedResponse<PublicPrayerResponse>> {
  const query = new URLSearchParams({
    page: page.toString(),
    per_page: perPage.toString(),
  });

  return apiClient<PaginatedResponse<PublicPrayerResponse>>(
    `/api/v1/prayer-intentions/${encodeURIComponent(publicId)}/responses?${query.toString()}`,
    {
      cache: 'no-store',
    }
  );
}

/**
 * Submit a supportive response to a public prayer intention (Authenticated).
 */
export async function createPrayerResponse(
  publicId: string,
  payload: CreatePrayerResponsePayload
): Promise<{ data: MyPrayerResponse }> {
  return apiClient<{ data: MyPrayerResponse }>(
    `/api/v1/prayer-intentions/${encodeURIComponent(publicId)}/responses`,
    {
      method: 'POST',
      body: JSON.stringify(payload),
    }
  );
}

/**
 * Report a public prayer response for content moderation (Authenticated).
 */
export async function reportPrayerResponse(
  publicId: string,
  payload: ReportPrayerResponsePayload
): Promise<{ message: string; data: { id: number; status: string } }> {
  return apiClient<{ message: string; data: { id: number; status: string } }>(
    `/api/v1/prayer-responses/${encodeURIComponent(publicId)}/reports`,
    {
      method: 'POST',
      body: JSON.stringify(payload),
    }
  );
}

/**
 * List the current user's submitted responses.
 */
export async function getMyPrayerResponses(
  page = 1,
  perPage = 15
): Promise<PaginatedResponse<MyPrayerResponse>> {
  const query = new URLSearchParams({
    page: page.toString(),
    per_page: perPage.toString(),
  });

  return apiClient<PaginatedResponse<MyPrayerResponse>>(
    `/api/v1/me/prayer-responses?${query.toString()}`,
    {
      cache: 'no-store',
    }
  );
}

/**
 * Update an existing personal response (resets approval to pending if previously approved).
 */
export async function updatePrayerResponse(
  id: number,
  body: string
): Promise<{ data: MyPrayerResponse }> {
  return apiClient<{ data: MyPrayerResponse }>(`/api/v1/me/prayer-responses/${id}`, {
    method: 'PATCH',
    body: JSON.stringify({ body }),
  });
}

/**
 * Withdraw a personal response from public view.
 */
export async function withdrawPrayerResponse(
  id: number
): Promise<{ data: MyPrayerResponse }> {
  return apiClient<{ data: MyPrayerResponse }>(
    `/api/v1/me/prayer-responses/${id}/withdraw`,
    {
      method: 'POST',
    }
  );
}

/**
 * Delete a personal response.
 */
export async function deletePrayerResponse(
  id: number
): Promise<{ message: string }> {
  return apiClient<{ message: string }>(`/api/v1/me/prayer-responses/${id}`, {
    method: 'DELETE',
  });
}

/**
 * Resubmit a previously rejected response with updated text.
 */
export async function resubmitPrayerResponse(
  id: number,
  body: string
): Promise<{ data: MyPrayerResponse }> {
  return apiClient<{ data: MyPrayerResponse }>(
    `/api/v1/me/prayer-responses/${id}/resubmit`,
    {
      method: 'POST',
      body: JSON.stringify({ body }),
    }
  );
}
