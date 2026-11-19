import { apiClient, ApiError } from "./client";
import { BuddhistFigure, PaginatedResponse } from "@/types/api";

export interface GetBuddhistFiguresParams {
  type?: string;
  tradition?: string;
  search?: string;
  page?: number;
  per_page?: number;
}

export async function getBuddhistFigures(
  params?: number | GetBuddhistFiguresParams
): Promise<PaginatedResponse<BuddhistFigure>> {
  const query = new URLSearchParams();

  if (typeof params === "number") {
    query.set("per_page", params.toString());
  } else if (params) {
    if (params.type) query.set("type", params.type);
    if (params.tradition) query.set("tradition", params.tradition);
    if (params.search) query.set("search", params.search);
    if (params.page) query.set("page", params.page.toString());
    if (params.per_page) query.set("per_page", params.per_page.toString());
  }

  const queryString = query.toString();
  const endpoint = `/api/v1/buddhist-figures${queryString ? `?${queryString}` : ""}`;

  return apiClient<PaginatedResponse<BuddhistFigure>>(endpoint);
}

export async function getBuddhistFigure(
  slug: string
): Promise<BuddhistFigure | null> {
  try {
    const res = await apiClient<{ data: BuddhistFigure }>(
      `/api/v1/buddhist-figures/${encodeURIComponent(slug)}`
    );
    return res.data || null;
  } catch (err) {
    if (err instanceof ApiError && err.status === 404) {
      return null;
    }
    throw err;
  }
}
