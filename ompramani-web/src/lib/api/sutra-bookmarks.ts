import { apiClient, ApiError } from "./client";
import { getCsrfCookie } from "./auth";
import {
  SutraBookmark,
  SutraBookmarkCheckResult,
  SutraBookmarkType,
  PaginatedResponse,
} from "@/types/api";

export interface CreateBookmarkPayload {
  type: SutraBookmarkType;
  sutra_id: number;
  sutra_version_id?: number | null;
  sutra_section_id?: number | null;
  note?: string | null;
}

export interface RemoveBookmarkByTargetPayload {
  type: SutraBookmarkType;
  sutra_id: number;
  sutra_section_id?: number | null;
}

/**
 * Get bookmarks for authenticated user (paginated, filterable by type)
 */
export async function getSutraBookmarks(params?: {
  type?: SutraBookmarkType;
  sutra_id?: number;
  page?: number;
  per_page?: number;
}): Promise<PaginatedResponse<SutraBookmark>> {
  try {
    const query = new URLSearchParams();
    if (params?.type) query.set("type", params.type);
    if (params?.sutra_id) query.set("sutra_id", params.sutra_id.toString());
    if (params?.page) query.set("page", params.page.toString());
    if (params?.per_page) query.set("per_page", params.per_page.toString());

    const qs = query.toString();
    const endpoint = `/api/v1/me/sutra-bookmarks${qs ? `?${qs}` : ""}`;

    return await apiClient<PaginatedResponse<SutraBookmark>>(endpoint, {
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
 * Check bookmark status for a sutra (is whole sutra saved, which sections are saved)
 */
export async function checkSutraBookmarks(
  slug: string
): Promise<SutraBookmarkCheckResult> {
  try {
    const res = await apiClient<{ data: SutraBookmarkCheckResult }>(
      `/api/v1/me/sutra-bookmarks/check/${slug}`,
      {
        method: "GET",
        cache: "no-store",
      }
    );
    return res.data;
  } catch (err) {
    if (err instanceof ApiError && err.status === 401) {
      return { is_sutra_saved: false, saved_sections: [] };
    }
    return { is_sutra_saved: false, saved_sections: [] };
  }
}

/**
 * Save a whole sutra or bookmark a section
 */
export async function createSutraBookmark(
  payload: CreateBookmarkPayload
): Promise<SutraBookmark> {
  await getCsrfCookie();

  const res = await apiClient<{ data: SutraBookmark }>(
    "/api/v1/me/sutra-bookmarks",
    {
      method: "POST",
      body: JSON.stringify(payload),
      cache: "no-store",
    }
  );

  return res.data;
}

/**
 * Remove bookmark by target parameters (idempotent removal)
 */
export async function removeSutraBookmarkByTarget(
  payload: RemoveBookmarkByTargetPayload
): Promise<{ message: string }> {
  await getCsrfCookie();

  return await apiClient<{ message: string }>(
    "/api/v1/me/sutra-bookmarks/by-target",
    {
      method: "DELETE",
      body: JSON.stringify(payload),
      cache: "no-store",
    }
  );
}

/**
 * Delete a bookmark by its ID
 */
export async function deleteSutraBookmark(
  bookmarkId: number
): Promise<{ message: string }> {
  await getCsrfCookie();

  return await apiClient<{ message: string }>(
    `/api/v1/me/sutra-bookmarks/${bookmarkId}`,
    {
      method: "DELETE",
      cache: "no-store",
    }
  );
}
