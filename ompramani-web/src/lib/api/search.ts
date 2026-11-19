import { apiClient, ApiError } from "./client";
import {
  RawSearchResultItem,
  SearchParams,
  SearchResponse,
  SearchSuggestionItem,
} from "@/types/api";
import { normalizeSearchResult } from "@/lib/utils/search";

/**
 * Perform full global search against /api/v1/search.
 */
export async function searchContent(
  params: SearchParams,
  signal?: AbortSignal
): Promise<SearchResponse> {
  const query = params.q ? params.q.trim() : "";

  // Guard: Backend requires min 2 chars
  if (query.length < 2) {
    return {
      data: [],
      meta: {
        query,
        total: 0,
        page: params.page || 1,
        per_page: params.per_page || 12,
      },
    };
  }

  const searchParams = new URLSearchParams();
  searchParams.set("q", query);

  if (params.type && params.type !== "all") {
    searchParams.set("type", params.type);
  }

  if (params.tradition) {
    searchParams.set("tradition", params.tradition);
  }

  if (params.page && params.page > 1) {
    searchParams.set("page", params.page.toString());
  }

  if (params.per_page) {
    searchParams.set("per_page", params.per_page.toString());
  }

  if (params.sort) {
    searchParams.set("sort", params.sort);
  }

  try {
    const rawRes = await apiClient<{
      data: RawSearchResultItem[];
      meta: {
        query: string;
        total: number;
        page: number;
        per_page: number;
      };
    }>(`/api/v1/search?${searchParams.toString()}`, {
      signal,
    });

    const normalizedData = (rawRes.data || []).map(normalizeSearchResult);

    return {
      data: normalizedData,
      meta: rawRes.meta || {
        query,
        total: normalizedData.length,
        page: params.page || 1,
        per_page: params.per_page || 12,
      },
    };
  } catch (err) {
    if (
      (err instanceof Error && (err.name === "AbortError" || err.message.toLowerCase().includes("abort"))) ||
      (err instanceof ApiError && err.message.toLowerCase().includes("abort"))
    ) {
      return {
        data: [],
        meta: {
          query,
          total: 0,
          page: params.page || 1,
          per_page: params.per_page || 12,
        },
      };
    }
    if (err instanceof ApiError && err.status === 422) {
      // Validation error from backend (e.g. query constraints)
      return {
        data: [],
        meta: {
          query,
          total: 0,
          page: params.page || 1,
          per_page: params.per_page || 12,
        },
      };
    }
    throw err;
  }
}

/**
 * Fast search suggestions autocomplete with abortable signal support.
 */
export async function getSearchSuggestions(
  query: string,
  signal?: AbortSignal,
  limit = 8
): Promise<SearchSuggestionItem[]> {
  const trimmed = query ? query.trim() : "";
  if (trimmed.length < 2) {
    return [];
  }

  try {
    const response = await apiClient<{ data: SearchSuggestionItem[] }>(
      `/api/v1/search/suggestions?q=${encodeURIComponent(trimmed)}&limit=${limit}`,
      { signal }
    );
    return response.data || [];
  } catch (err) {
    if (
      (err instanceof Error && (err.name === "AbortError" || err.message.toLowerCase().includes("abort"))) ||
      (err instanceof ApiError && err.message.toLowerCase().includes("abort"))
    ) {
      return [];
    }
    console.error("Lỗi tìm kiếm gợi ý:", err);
    return [];
  }
}
