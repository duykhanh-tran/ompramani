import { apiClient, ApiError } from "./client";
import {
  Sutra,
  Tradition,
  SutraVersion,
  SutraSectionTreeNode,
  SutraSectionDetail,
  PaginatedResponse,
} from "@/types/api";

export async function getTraditions(): Promise<Tradition[]> {
  try {
    const res = await apiClient<{ data: Tradition[] }>("/api/v1/traditions");
    return res.data || [];
  } catch (err) {
    console.error("Lỗi khi tải danh sách hệ phái:", err);
    return [];
  }
}

export async function getSutras(params?: {
  tradition?: string;
  search?: string;
  page?: number;
  per_page?: number;
}): Promise<PaginatedResponse<Sutra>> {
  const query = new URLSearchParams();
  if (params?.tradition) query.set("tradition", params.tradition);
  if (params?.search) query.set("search", params.search);
  if (params?.page) query.set("page", params.page.toString());
  if (params?.per_page) query.set("per_page", params.per_page.toString());

  const queryString = query.toString();
  const endpoint = `/api/v1/sutras${queryString ? `?${queryString}` : ""}`;

  return apiClient<PaginatedResponse<Sutra>>(endpoint);
}

export async function getSutra(slug: string): Promise<Sutra | null> {
  try {
    const res = await apiClient<{ data: Sutra }>(`/api/v1/sutras/${slug}`);
    return res.data || null;
  } catch (err) {
    if (err instanceof ApiError && err.status === 404) {
      return null;
    }
    throw err;
  }
}

export async function getSutraVersions(slug: string): Promise<SutraVersion[]> {
  try {
    const res = await apiClient<{ data: SutraVersion[] }>(
      `/api/v1/sutras/${slug}/versions`
    );
    return res.data || [];
  } catch (err) {
    console.error(`Lỗi khi tải phiên bản của kinh ${slug}:`, err);
    return [];
  }
}

export async function getSutraSections(
  versionOrSlug: number | string
): Promise<SutraSectionTreeNode[]> {
  try {
    const endpoint =
      typeof versionOrSlug === "string"
        ? `/api/v1/sutras/${versionOrSlug}/sections`
        : `/api/v1/sutra-versions/${versionOrSlug}/sections`;
    const res = await apiClient<{ data: SutraSectionTreeNode[] }>(endpoint);
    return res.data || [];
  } catch (err) {
    console.error(`Lỗi khi tải mục lục:`, err);
    return [];
  }
}

export async function getSutraSectionsBySlug(
  slug: string
): Promise<SutraSectionTreeNode[]> {
  return getSutraSections(slug);
}

export async function getSutraSection(
  sectionId: number
): Promise<SutraSectionDetail | null> {
  try {
    const res = await apiClient<{ data: SutraSectionDetail }>(
      `/api/v1/sutra-sections/${sectionId}`
    );
    return res.data || null;
  } catch (err) {
    if (err instanceof ApiError && err.status === 404) {
      return null;
    }
    throw err;
  }
}
