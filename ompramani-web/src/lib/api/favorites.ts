import { apiClient } from "./client";
import {
  FavoriteContentType,
  FavoritesListResponse,
  FavoriteStatusResponse,
  FavoriteToggleResponse,
} from "@/types/favorite";

/**
 * Fetch paginated favorites for authenticated user with optional content type filter.
 */
export async function getFavorites(params?: {
  type?: FavoriteContentType;
  page?: number;
  per_page?: number;
}): Promise<FavoritesListResponse> {
  const query = new URLSearchParams();

  if (params?.type && params.type !== "all") {
    query.set("type", params.type);
  }
  if (params?.page && params.page > 1) {
    query.set("page", params.page.toString());
  }
  if (params?.per_page) {
    query.set("per_page", params.per_page.toString());
  }

  const qs = query.toString();
  return await apiClient<FavoritesListResponse>(
    `/api/v1/me/favorites${qs ? `?${qs}` : ""}`,
    {
      method: "GET",
      cache: "no-store",
    }
  );
}

/**
 * Add content to user's favorites (idempotent PUT).
 */
export async function addFavorite(
  type: string,
  publicId: string
): Promise<FavoriteToggleResponse> {
  return await apiClient<FavoriteToggleResponse>(
    `/api/v1/me/favorites/${type}/${encodeURIComponent(publicId)}`,
    {
      method: "PUT",
    }
  );
}

/**
 * Remove content from user's favorites (idempotent DELETE).
 */
export async function removeFavorite(
  type: string,
  publicId: string
): Promise<FavoriteToggleResponse> {
  return await apiClient<FavoriteToggleResponse>(
    `/api/v1/me/favorites/${type}/${encodeURIComponent(publicId)}`,
    {
      method: "DELETE",
    }
  );
}

/**
 * Get favorite status for a single item.
 */
export async function getFavoriteStatus(
  type: string,
  publicId: string
): Promise<boolean> {
  try {
    const res = await apiClient<FavoriteStatusResponse>(
      `/api/v1/me/favorites/status?type=${type}&id=${encodeURIComponent(publicId)}`,
      {
        method: "GET",
        cache: "no-store",
      }
    );
    return Boolean(res.data?.is_favorited);
  } catch {
    return false;
  }
}

/**
 * Get favorite statuses for multiple items in batch (up to 50 items).
 */
export async function getFavoriteBatchStatus(
  type: string,
  publicIds: string[]
): Promise<Record<string, boolean>> {
  if (!publicIds.length) {
    return {};
  }

  try {
    const query = new URLSearchParams();
    query.set("type", type);
    publicIds.slice(0, 50).forEach((id) => {
      query.append("ids[]", id);
    });

    const res = await apiClient<FavoriteStatusResponse>(
      `/api/v1/me/favorites/status?${query.toString()}`,
      {
        method: "GET",
        cache: "no-store",
      }
    );

    return res.data?.statuses || {};
  } catch {
    return {};
  }
}
