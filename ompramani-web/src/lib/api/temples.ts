import { apiClient, ApiError } from "./client";
import { PaginatedResponse } from "@/types/api";
import {
  TempleDetail,
  TempleFilterFacets,
  TempleSearchParams,
  TempleSummary,
  TempleTypeRegistryItem,
} from "@/types/temple";

export async function getTemples(
  params?: TempleSearchParams
): Promise<PaginatedResponse<TempleSummary>> {
  const query = new URLSearchParams();

  if (params) {
    if (params.page && params.page > 1) query.set("page", params.page.toString());
    if (params.per_page) query.set("per_page", params.per_page.toString());
    if (params.q && params.q.trim()) query.set("q", params.q.trim());
    if (params.type) query.set("type", params.type);
    if (params.place_type && !params.type) query.set("place_type", params.place_type);
    if (params.country) query.set("country", params.country);
    if (params.province) query.set("province", params.province);
    if (params.tradition) query.set("tradition", params.tradition.toString());
    if (params.sort && params.sort !== "relevance") query.set("sort", params.sort);
  }

  const queryString = query.toString();
  const endpoint = `/api/v1/temples${queryString ? `?${queryString}` : ""}`;

  return apiClient<PaginatedResponse<TempleSummary>>(endpoint);
}

export async function getTempleBySlug(slug: string): Promise<TempleDetail | null> {
  try {
    const res = await apiClient<{ data: TempleDetail }>(
      `/api/v1/temples/${encodeURIComponent(slug)}`
    );
    return res.data || null;
  } catch (err) {
    if (err instanceof ApiError && err.status === 404) {
      return null;
    }
    throw err;
  }
}

export async function getTempleTypes(): Promise<TempleTypeRegistryItem[]> {
  try {
    const res = await apiClient<{ data: TempleTypeRegistryItem[] }>(
      "/api/v1/temples/types"
    );
    return res.data || [];
  } catch (err) {
    console.error("Failed to fetch temple types:", err);
    return [];
  }
}

export async function getTempleFilters(): Promise<TempleFilterFacets | null> {
  try {
    const res = await apiClient<{ data: TempleFilterFacets }>(
      "/api/v1/temples/filters"
    );
    return res.data || null;
  } catch (err) {
    console.error("Failed to fetch temple filters:", err);
    return null;
  }
}
