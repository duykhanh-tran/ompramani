export type VideoCategory =
  | "dharma_talk"
  | "buddhist_teaching"
  | "meditation_guidance"
  | "sutra_study"
  | "buddhist_culture"
  | "buddhist_history"
  | "other";

export type VideoProvider = "youtube" | "vimeo" | "external";

export type VideoPlaybackMode =
  | "approved_embed"
  | "external_link_only"
  | "unavailable";

export type VideoCopyrightStatus =
  | "pending_review"
  | "verified_embeddable"
  | "licensed_hosted"
  | "restricted"
  | "not_permitted";

export interface VideoCategoryItem {
  key: VideoCategory;
  label: string;
}

export interface VideoTopicItem {
  id: number;
  name: string;
  slug: string;
  description?: string | null;
}

export interface VideoSummary {
  public_id: string;
  slug: string;
  title: string;
  short_description?: string | null;
  category: VideoCategory;
  category_label: string;
  provider: VideoProvider;
  provider_label: string;
  speaker_name?: string | null;
  duration_seconds?: number | null;
  duration_formatted?: string | null;
  thumbnail_url?: string | null;
  playback_mode: VideoPlaybackMode;
  topics?: VideoTopicItem[];
  published_at?: string | null;
}

export interface VideoDetail extends VideoSummary {
  description?: string | null;
  canonical_source_url: string;
  source_title?: string | null;
  source_channel_name?: string | null;
  language_code?: string | null;
  embed_url?: string | null;
  copyright_status: VideoCopyrightStatus;
  copyright_status_label: string;
}

export interface VideoSearchParams {
  q?: string;
  category?: VideoCategory;
  topic?: string;
  speaker?: string;
  sort?: "newest" | "oldest" | "duration_desc" | "duration_asc" | "relevance";
  page?: number;
  per_page?: number;
}
