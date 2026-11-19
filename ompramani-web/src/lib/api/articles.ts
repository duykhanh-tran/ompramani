import { apiClient, ApiError } from "./client";
import { Article, ArticleCategory, PaginatedResponse } from "@/types/api";

export interface GetArticlesParams {
  search?: string;
  category?: string;
  tag?: string;
  sort?: "newest" | "oldest";
  page?: number;
  per_page?: number;
}

export async function getArticles(
  params?: number | GetArticlesParams
): Promise<PaginatedResponse<Article>> {
  const query = new URLSearchParams();

  if (typeof params === "number") {
    query.set("per_page", params.toString());
  } else if (params) {
    if (params.search) query.set("search", params.search);
    if (params.category) query.set("category", params.category);
    if (params.tag) query.set("tag", params.tag);
    if (params.sort) query.set("sort", params.sort);
    if (params.page) query.set("page", params.page.toString());
    if (params.per_page) query.set("per_page", params.per_page.toString());
  }

  const queryString = query.toString();
  const endpoint = `/api/v1/articles${queryString ? `?${queryString}` : ""}`;

  return apiClient<PaginatedResponse<Article>>(endpoint);
}

export async function getArticle(slug: string): Promise<Article | null> {
  try {
    const res = await apiClient<{ data: Article }>(
      `/api/v1/articles/${encodeURIComponent(slug)}`
    );
    return res.data || null;
  } catch (err) {
    if (err instanceof ApiError && err.status === 404) {
      return null;
    }
    throw err;
  }
}

export async function getArticleCategories(): Promise<ArticleCategory[]> {
  try {
    const res = await apiClient<{ data: ArticleCategory[] }>(
      "/api/v1/article-categories"
    );
    return res.data || [];
  } catch (err) {
    console.error("Failed to fetch article categories:", err);
    return [];
  }
}
