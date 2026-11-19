import { apiClient, ApiError } from "./client";
import { PaginatedResponse } from "@/types/api";
import {
  VideoCategoryItem,
  VideoDetail,
  VideoSearchParams,
  VideoSummary,
  VideoTopicItem,
} from "@/types/video";

export async function getVideos(
  params?: VideoSearchParams
): Promise<PaginatedResponse<VideoSummary>> {
  const query = new URLSearchParams();

  if (params) {
    if (params.page && params.page > 1) query.set("page", params.page.toString());
    if (params.per_page) query.set("per_page", params.per_page.toString());
    if (params.q && params.q.trim()) query.set("q", params.q.trim());
    if (params.category) query.set("category", params.category);
    if (params.topic) query.set("topic", params.topic);
    if (params.speaker) query.set("speaker", params.speaker);
    if (params.sort) query.set("sort", params.sort);
  }

  const queryString = query.toString();
  const endpoint = `/api/v1/videos${queryString ? `?${queryString}` : ""}`;

  return apiClient<PaginatedResponse<VideoSummary>>(endpoint);
}

export async function getVideoBySlug(slug: string): Promise<VideoDetail | null> {
  try {
    const res = await apiClient<{ data: VideoDetail }>(
      `/api/v1/videos/${encodeURIComponent(slug)}`
    );
    return res.data || null;
  } catch (err) {
    if (err instanceof ApiError && err.status === 404) {
      return null;
    }
    throw err;
  }
}

export async function getVideoCategories(): Promise<VideoCategoryItem[]> {
  try {
    const res = await apiClient<{ data: VideoCategoryItem[] }>(
      "/api/v1/videos/categories"
    );
    return res.data || [];
  } catch (err) {
    console.error("Failed to fetch video categories:", err);
    return [];
  }
}

export async function getVideoTopics(): Promise<VideoTopicItem[]> {
  try {
    const res = await apiClient<{ data: VideoTopicItem[] }>(
      "/api/v1/videos/topics"
    );
    return res.data || [];
  } catch (err) {
    console.error("Failed to fetch video topics:", err);
    return [];
  }
}
