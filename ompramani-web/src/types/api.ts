export interface PaginatedResponse<T> {
  data: T[];
  links?: {
    first?: string | null;
    last?: string | null;
    prev?: string | null;
    next?: string | null;
  };
  meta?: {
    current_page: number;
    from?: number | null;
    last_page: number;
    per_page: number;
    to?: number | null;
    total: number;
  };
}

export interface Tradition {
  id: number;
  name: string;
  slug: string;
  description?: string | null;
  is_active?: boolean;
}

export type FigureType =
  | "buddha"
  | "bodhisattva"
  | "arahant"
  | "teacher"
  | "other"
  | string;

export interface RecitationAudio {
  id: number;
  title: string;
  chanter?: string | null;
  audio_url: string;
  duration_seconds?: number | null;
  sort_order: number;
}

export interface MantraVersion {
  id: number;
  mantra_id: number;
  name: string;
  language?: string | null;
  script?: string | null;
  content: string;
  transliteration?: string | null;
  translation?: string | null;
  source?: string | null;
  notes?: string | null;
  is_default: boolean;
  status?: string;
  mantra?: Mantra | null;
  audios?: RecitationAudio[];
}

export interface Mantra {
  id: number;
  tradition_id?: number | null;
  buddhist_figure_id?: number | null;
  title: string;
  slug: string;
  original_title?: string | null;
  short_description?: string | null;
  original_text?: string | null;
  vietnamese_transcription?: string | null;
  practice_guidance?: string | null;
  audio_path?: string | null;
  audio_url?: string | null;
  thumbnail?: string | null;
  image_url?: string | null;
  status?: string;
  published_at?: string | null;
  tradition?: Tradition | null;
  figure?: BuddhistFigure | null;
  buddhist_figure?: BuddhistFigure | null;
  default_version?: MantraVersion | null;
  versions?: MantraVersion[];
}

export interface BuddhistFigureVow {
  id: number;
  buddhist_figure_id: number;
  title: string;
  content: string;
  sort_order: number;
}

export interface BuddhistFigure {
  id: number;
  tradition_id?: number | null;
  name: string;
  slug: string;
  original_name?: string | null;
  figure_type: FigureType;
  short_description?: string | null;
  biography?: string | null;
  vows_introduction?: string | null;
  great_vows?: string | null;
  vows?: BuddhistFigureVow[];
  iconography?: string | null;
  practice_guidance?: string | null;
  audio_path?: string | null;
  audio_url?: string | null;
  thumbnail?: string | null;
  image_url?: string | null;
  status?: string;
  published_at?: string | null;
  tradition?: Tradition | null;
  traditions?: Tradition[];
  sutras?: Sutra[];
  mantras?: Mantra[];
  invocation_text?: string | null;
  audios?: RecitationAudio[];
}

export interface ArticleCategory {
  id: number;
  name: string;
  slug: string;
  description?: string | null;
  position?: number;
  published_articles_count?: number;
}

export interface ArticleTag {
  id: number;
  name: string;
  slug: string;
}

export interface ArticleAuthor {
  id: number;
  name: string;
}

export interface RelatedBuddhistContent {
  type: "sutra" | "figure" | "mantra" | string;
  id: number;
  title: string;
  slug: string;
  thumbnail?: string | null;
}

export interface RelatedArticleItem {
  id: number;
  title: string;
  slug: string;
  excerpt?: string | null;
  cover_image?: string | null;
  published_at?: string | null;
}

export interface Article {
  id: number;
  title: string;
  slug: string;
  excerpt?: string | null;
  summary?: string | null;
  content?: string | null;
  cover_image?: string | null;
  reading_time_minutes?: number | null;
  shares_count?: number | null;
  views_count?: number | null;
  status?: string;
  published_at?: string | null;
  category?: ArticleCategory | null;
  author?: ArticleAuthor | null;
  tags?: ArticleTag[];
  meta_title?: string | null;
  meta_description?: string | null;
  related_content?: RelatedBuddhistContent[];
  related_articles?: RelatedArticleItem[];
}

export interface SutraVersion {
  id: number;
  name: string;
  translator?: string | null;
  language?: string | null;
  publisher?: string | null;
  publication_year?: number | string | null;
  source?: string | null;
  notes?: string | null;
  is_default: boolean;
  status: string;
}

export interface Sutra {
  id: number;
  tradition_id?: number | null;
  title: string;
  slug: string;
  original_title?: string | null;
  translator?: string | null;
  language?: string | null;
  publisher?: string | null;
  publication_year?: number | string | null;
  source?: string | null;
  notes?: string | null;
  subtitle?: string | null;
  short_description?: string | null;
  thumbnail?: string | null;
  status?: string;
  tradition?: Tradition | null;
  versions?: SutraVersion[];
}

export interface SutraSectionTreeNode {
  id: number;
  title: string;
  slug: string;
  position: number;
  parent_id: number | null;
  source_page_start?: number | null;
  source_page_end?: number | null;
  children: SutraSectionTreeNode[];
}

export interface SutraSectionDetail {
  id: number;
  sutra_id?: number | null;
  title: string;
  slug: string;
  content: string | null;
  parent_id: number | null;
  position: number;
  source_page_start?: number | null;
  source_page_end?: number | null;
  version?: {
    id: number;
    name: string;
    translator?: string | null;
    language?: string | null;
  } | null;
  sutra: {
    id: number;
    title: string;
    slug: string;
  };
}

export interface SearchSuggestionItem {
  title: string;
  type: string;
  slug?: string | null;
  url?: string | null;
  subtitle?: string | null;
  tradition?: string | null;
}

export type SearchTypeOption =
  | "all"
  | "sutra"
  | "mantra"
  | "figure"
  | "article"
  | "feature";

export interface SearchParams {
  q: string;
  type?: SearchTypeOption | string;
  tradition?: string;
  page?: number;
  per_page?: number;
  sort?: "relevance" | "newest";
}

export interface SearchMeta {
  query: string;
  total: number;
  page: number;
  per_page: number;
}

export interface RawSearchResultItem {
  type: string;
  id: number | string;
  title: string;
  slug?: string | null;
  name?: string | null;
  original_title?: string | null;
  original_name?: string | null;
  figure_type?: string | null;
  description?: string | null;
  thumbnail?: string | null;
  cover_image?: string | null;
  url?: string | null;
  tradition?: {
    id?: number;
    name: string;
    slug: string;
  } | null;
  figure?: {
    id?: number;
    name: string;
    slug: string;
  } | null;
  category?: {
    id?: number;
    name: string;
    slug: string;
  } | null;
  matched_in?: string[];
}

export interface NormalizedSearchResult {
  type: "sutra" | "mantra" | "figure" | "article" | "feature" | string;
  id: number | string;
  title: string;
  slug: string;
  description: string;
  thumbnail: string | null;
  url?: string | null;
  tradition?: {
    id?: number;
    name: string;
    slug: string;
  } | null;
  matched_in: string[];
  metadata?: {
    original_title?: string | null;
    original_name?: string | null;
    figure_type?: string | null;
    figure_name?: string | null;
    figure_slug?: string | null;
    category_name?: string | null;
    category_slug?: string | null;
  };
}

export interface SearchResponse {
  data: NormalizedSearchResult[];
  meta: SearchMeta;
}

export interface SutraReadingProgress {
  id: number;
  sutra: {
    id: number;
    title: string;
    slug: string;
    thumbnail?: string | null;
    translator?: string | null;
    tradition?: Tradition | null;
  };
  version?: {
    id: number;
    name: string;
    translator?: string | null;
    language?: string | null;
  } | null;
  section: {
    id: number;
    title: string;
    slug: string;
    position: number;
  };
  continue_url: string;
  scroll_percentage: number;
  revision: number;
  last_read_at: string;
  updated_at?: string;
}

export type SutraBookmarkType = "sutra" | "section";

export interface SutraBookmark {
  id: number;
  type: SutraBookmarkType;
  sutra: {
    id: number;
    title: string;
    slug: string;
    thumbnail?: string | null;
    short_description?: string | null;
    tradition?: Tradition | null;
  };
  version?: {
    id: number;
    name: string;
    translator?: string | null;
  } | null;
  section?: {
    id: number;
    title: string;
    slug: string;
    position?: number;
  } | null;
  target_url: string;
  note?: string | null;
  created_at: string;
  updated_at?: string;
}

export interface SutraBookmarkCheckResult {
  is_sutra_saved: boolean;
  saved_sections: number[];
}

export * from "./auth";


