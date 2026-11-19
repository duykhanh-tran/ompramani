import { apiClient, ApiError } from "./client";
import { getCsrfCookie } from "./auth";
import { SutraReadingProgress, PaginatedResponse } from "@/types/api";

export interface UpsertReadingProgressPayload {
  sutra_version_id?: number;
  sutra_section_id: number;
  scroll_percentage?: number;
  revision?: number;
}

/**
 * Get reading progresses for authenticated user (paginated, ordered by last_read_at desc)
 */
export async function getReadingProgresses(params?: {
  page?: number;
  per_page?: number;
}): Promise<PaginatedResponse<SutraReadingProgress>> {
  try {
    const query = new URLSearchParams();
    if (params?.page) query.set("page", params.page.toString());
    if (params?.per_page) query.set("per_page", params.per_page.toString());

    const qs = query.toString();
    const endpoint = `/api/v1/me/sutra-reading-progress${qs ? `?${qs}` : ""}`;

    return await apiClient<PaginatedResponse<SutraReadingProgress>>(endpoint, {
      method: "GET",
      cache: "no-store",
    });
  } catch (err) {
    if (err instanceof ApiError && err.status === 401) {
      return { data: [] };
    }
    throw err;
  }
}

/**
 * Get reading progress for a specific sutra (and optional version)
 */
export async function getSutraReadingProgress(
  slug: string,
  versionId?: number
): Promise<SutraReadingProgress | null> {
  try {
    const query = new URLSearchParams();
    if (versionId) query.set("version_id", versionId.toString());
    const qs = query.toString();
    const endpoint = `/api/v1/me/sutras/${slug}/reading-progress${qs ? `?${qs}` : ""}`;

    const res = await apiClient<{ data: SutraReadingProgress | null }>(endpoint, {
      method: "GET",
      cache: "no-store",
    });

    return res.data;
  } catch (err) {
    if (err instanceof ApiError && (err.status === 401 || err.status === 404)) {
      return null;
    }
    throw err;
  }
}

/**
 * Save or update reading progress for a sutra
 */
export async function saveReadingProgress(
  slug: string,
  payload: UpsertReadingProgressPayload
): Promise<SutraReadingProgress> {
  await getCsrfCookie();

  const res = await apiClient<{ data: SutraReadingProgress }>(
    `/api/v1/me/sutras/${slug}/reading-progress`,
    {
      method: "PUT",
      body: JSON.stringify(payload),
      cache: "no-store",
    }
  );

  return res.data;
}

/**
 * Delete reading progress for a sutra (and optional version)
 */
export async function deleteReadingProgress(
  slug: string,
  versionId?: number
): Promise<{ message: string }> {
  await getCsrfCookie();

  const query = new URLSearchParams();
  if (versionId) query.set("version_id", versionId.toString());
  const qs = query.toString();
  const endpoint = `/api/v1/me/sutras/${slug}/reading-progress${qs ? `?${qs}` : ""}`;

  return await apiClient<{ message: string }>(endpoint, {
    method: "DELETE",
    cache: "no-store",
  });
}
