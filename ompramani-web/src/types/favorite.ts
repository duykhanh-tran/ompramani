export type FavoriteContentType = "all" | "sutra" | "article" | "video";

export interface UserFavoriteItem {
  id: number;
  type: "sutra" | "article" | "video";
  content_public_id: string;
  title: string;
  thumbnail: string | null;
  short_description: string | null;
  content_url: string;
  saved_at: string;
  is_available: boolean;
}

export interface FavoritesListResponse {
  data: UserFavoriteItem[];
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  links?: {
    first?: string;
    last?: string;
    prev?: string | null;
    next?: string | null;
  };
}

export interface FavoriteStatusResponse {
  data: {
    type: string;
    is_favorited?: boolean;
    content_public_id?: string;
    statuses?: Record<string, boolean>;
  };
}

export interface FavoriteToggleResponse {
  data: {
    is_favorited: boolean;
    type: string;
    content_public_id: string;
    message: string;
  };
}
