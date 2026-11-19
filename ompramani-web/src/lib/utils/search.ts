import {
  NormalizedSearchResult,
  RawSearchResultItem,
} from "@/types/api";

/**
 * Normalize heterogeneous raw search results from backend to unified frontend model.
 */
export function normalizeSearchResult(
  raw: RawSearchResultItem
): NormalizedSearchResult {
  const type = raw.type || "sutra";
  const id = raw.id;
  const title = raw.title || raw.name || "Nội dung";
  const slug = raw.slug || "";
  const description = raw.description || "";
  const thumbnail = raw.thumbnail || raw.cover_image || null;
  const url = raw.url || null;
  const matched_in = Array.isArray(raw.matched_in) ? raw.matched_in : [];

  return {
    type,
    id,
    title,
    slug,
    description,
    thumbnail,
    url,
    tradition: raw.tradition || null,
    matched_in,
    metadata: {
      original_title: raw.original_title || null,
      original_name: raw.original_name || null,
      figure_type: raw.figure_type || null,
      figure_name: raw.figure?.name || null,
      figure_slug: raw.figure?.slug || null,
      category_name: raw.category?.name || null,
      category_slug: raw.category?.slug || null,
    },
  };
}

/**
 * Universal destination routing for search results and suggestions.
 */
export function getSearchResultHref(item: {
  type: string;
  slug?: string | null;
  url?: string | null;
}): string {
  if (item.url && item.url.trim()) {
    return item.url.trim();
  }

  const slug = item.slug || "";

  switch (item.type) {
    case "sutra":
      return `/kinh/${slug}`;
    case "mantra":
      return `/chu/${slug}`;
    case "figure":
      return `/phat-bo-tat/${slug}`;
    case "article":
      return `/bai-viet/${slug}`;
    case "feature":
      return slug ? `/${slug}` : "/practice";
    default:
      return `/kinh/${slug}`;
  }
}

/**
 * UI Badges for search result types complying with Buddhist Design System.
 */
export function getSearchTypeBadge(type: string): {
  label: string;
  badgeClass: string;
  icon: string;
} {
  switch (type) {
    case "sutra":
      return {
        label: "Kinh Điển",
        badgeClass: "bg-amber-100/90 text-amber-800 border-amber-200/80",
        icon: "menu_book",
      };
    case "mantra":
      return {
        label: "Thần Chú",
        badgeClass: "bg-indigo-100/90 text-indigo-800 border-indigo-200/80",
        icon: "graphic_eq",
      };
    case "figure":
      return {
        label: "Phật · Bồ Tát",
        badgeClass: "bg-emerald-100/90 text-emerald-800 border-emerald-200/80",
        icon: "self_improvement",
      };
    case "article":
      return {
        label: "Bài Viết",
        badgeClass: "bg-rose-100/90 text-rose-800 border-rose-200/80",
        icon: "article",
      };
    case "feature":
      return {
        label: "Pháp Khí · Tiện Ích",
        badgeClass: "bg-sky-100/90 text-sky-800 border-sky-200/80",
        icon: "spa",
      };
    default:
      return {
        label: "Phật Pháp",
        badgeClass: "bg-stone-100/90 text-stone-800 border-stone-200/80",
        icon: "auto_stories",
      };
  }
}

/**
 * Safe search match highlighter producing structured segments without innerHTML mutation.
 */
export function highlightMatches(
  text: string,
  query: string
): Array<{ text: string; isMatch: boolean }> {
  if (!text) return [];
  const trimmed = query ? query.trim() : "";
  if (trimmed.length < 2) {
    return [{ text, isMatch: false }];
  }

  // Escape regex special chars and build word boundary or direct match regex
  const terms = trimmed
    .split(/\s+/)
    .filter((w) => w.length >= 2)
    .map((w) => w.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"));

  if (terms.length === 0) {
    return [{ text, isMatch: false }];
  }

  const regex = new RegExp(`(${terms.join("|")})`, "gi");
  const parts = text.split(regex);

  return parts
    .filter((part) => part.length > 0)
    .map((part) => ({
      text: part,
      isMatch: regex.test(part),
    }));
}
