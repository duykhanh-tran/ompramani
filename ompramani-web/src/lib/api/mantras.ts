import { apiClient, ApiError } from "./client";
import { Mantra, MantraVersion, PaginatedResponse } from "@/types/api";

export interface GetMantrasParams {
  tradition?: string;
  figure?: string;
  search?: string;
  page?: number;
  per_page?: number;
}

export async function getMantras(
  params?: GetMantrasParams
): Promise<PaginatedResponse<Mantra>> {
  const query = new URLSearchParams();

  if (params?.tradition) query.set("tradition", params.tradition);
  if (params?.figure) query.set("figure", params.figure);
  if (params?.search) query.set("search", params.search);
  if (params?.page) query.set("page", params.page.toString());
  if (params?.per_page) query.set("per_page", params.per_page.toString());

  const queryString = query.toString();
  const endpoint = `/api/v1/mantras${queryString ? `?${queryString}` : ""}`;

  return apiClient<PaginatedResponse<Mantra>>(endpoint);
}

export async function getMantra(slug: string): Promise<Mantra | null> {
  try {
    const res = await apiClient<{ data: Mantra }>(
      `/api/v1/mantras/${encodeURIComponent(slug)}`
    );
    return res.data || null;
  } catch (err) {
    if (err instanceof ApiError && err.status === 404) {
      return null;
    }
    throw err;
  }
}

export async function getMantraVersions(
  slug: string
): Promise<MantraVersion[]> {
  try {
    const res = await apiClient<{ data: MantraVersion[] }>(
      `/api/v1/mantras/${encodeURIComponent(slug)}/versions`
    );
    return res.data || [];
  } catch (err) {
    console.error(`Lỗi khi tải phiên bản của thần chú ${slug}:`, err);
    return [];
  }
}

export async function getMantraVersion(
  id: number
): Promise<MantraVersion | null> {
  try {
    const res = await apiClient<{ data: MantraVersion }>(
      `/api/v1/mantra-versions/${id}`
    );
    return res.data || null;
  } catch (err) {
    if (err instanceof ApiError && err.status === 404) {
      return null;
    }
    throw err;
  }
}
