import { BuddhistFigure, SearchSuggestionItem, Tradition } from "@/types/api";

export const FIGURE_TYPE_OPTIONS: { value: string; label: string }[] = [
  { value: "", label: "Tất cả" },
  { value: "buddha", label: "Phật" },
  { value: "bodhisattva", label: "Bồ Tát" },
  { value: "arahant", label: "A La Hán" },
  { value: "teacher", label: "Tổ Sư" },
  { value: "other", label: "Thánh Chúng" },
];

export function getFigureTypeLabel(figureType?: string | null): string {
  switch (figureType) {
    case "buddha":
      return "Chư Phật";
    case "bodhisattva":
      return "Bồ Tát";
    case "arahant":
      return "A La Hán";
    case "teacher":
      return "Tổ Sư · Đạo Sư";
    case "other":
      return "Hộ Pháp · Thánh Chúng";
    default:
      return figureType || "Tôn Tượng";
  }
}

/**
 * Extract unique traditions associated with a Buddhist Figure from backend response
 * without inventing or hard-coding any tradition if none is returned.
 */
export function getFigureTraditions(figure: BuddhistFigure): Tradition[] {
  const map = new Map<string, Tradition>();

  if (figure.tradition && figure.tradition.slug) {
    map.set(figure.tradition.slug, figure.tradition);
  }

  if (Array.isArray(figure.traditions)) {
    for (const t of figure.traditions) {
      if (t && t.slug && !map.has(t.slug)) {
        map.set(t.slug, t);
      }
    }
  }

  return Array.from(map.values());
}

/**
 * Map a SearchSuggestionItem to its canonical frontend route.
 */
export function resolveSuggestionHref(item: SearchSuggestionItem): string {
  if (item.url) {
    return item.url;
  }

  if (item.slug) {
    switch (item.type) {
      case "sutra":
        return `/kinh/${item.slug}`;
      case "figure":
        return `/phat-bo-tat/${item.slug}`;
      case "mantra":
        return `/chu/${item.slug}`;
      case "article":
        return `/bai-viet/${item.slug}`;
      case "feature":
        if (item.slug === "meditation" || item.slug === "thien") {
          return "/thien";
        }
        if (item.slug === "mala" || item.slug === "trang-hat") {
          return "/trang-hat";
        }
        if (
          item.slug === "bell-bowl" ||
          item.slug === "bell" ||
          item.slug === "chuong-mo"
        ) {
          return "/chuong-mo";
        }
        return `/${item.slug}`;
      default:
        break;
    }
  }

  return `/kinh?search=${encodeURIComponent(item.title)}`;
}

export function getSuggestionTypeLabel(type: string): string {
  switch (type) {
    case "sutra":
      return "Kinh";
    case "figure":
      return "Phật · Bồ Tát";
    case "mantra":
      return "Thần Chú";
    case "article":
      return "Bài viết";
    case "feature":
      return "Pháp khí";
    default:
      return type;
  }
}

/**
 * Check if a string contains HTML markup from Filament RichEditor and sanitize dangerous tags/attributes,
 * or split plain text into paragraphs while preserving all Unicode characters verbatim.
 */
export function parseSafeContent(rawContent?: string | null): {
  isHtml: boolean;
  sanitizedHtml: string;
  paragraphs: string[];
} {
  if (!rawContent || !rawContent.trim()) {
    return { isHtml: false, sanitizedHtml: "", paragraphs: [] };
  }

  const hasHtmlTags = /<\/?[a-z][\s\S]*>/i.test(rawContent);

  if (hasHtmlTags) {
    const sanitized = rawContent
      // 1. Remove dangerous executable tags and contents
      .replace(/<(?:script|object|embed|style|meta|link|base|form)\b[^<]*(?:(?!<\/(?:script|object|embed|style|meta|link|base|form)>)<[^<]*)*<\/(?:script|object|embed|style|meta|link|base|form)>/gi, "")
      .replace(/<(?:script|object|embed|style|meta|link|base|form)\b[^>]*\/?>/gi, "")
      // 2. Remove all inline event handlers (onerror, onclick, onload, etc.), both quoted and unquoted
      .replace(/\s+on[a-zA-Z0-9_-]+\s*=\s*(?:"[^"]*"|'[^']*'|[^\s>]+)/gi, "")
      // 3. Block malicious pseudoprotocol URLs (javascript:, vbscript:, data:text/html)
      .replace(/\s+(?:href|src|action)\s*=\s*(?:"\s*(?:javascript|vbscript|data:(?!image\/))[^"]*"|'\s*(?:javascript|vbscript|data:(?!image\/))[^']*'|(?:javascript|vbscript|data:(?!image\/))[^\s>]*)/gi, "");

    return {
      isHtml: true,
      sanitizedHtml: sanitized,
      paragraphs: [],
    };
  }

  const paragraphs = rawContent
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter((line) => line.length > 0);

  return {
    isHtml: false,
    sanitizedHtml: "",
    paragraphs,
  };
}

/**
 * Format ISO or SQL date string to Vietnamese localized format (e.g., "15 tháng 10, 2026").
 */
export function formatArticleDate(dateStr?: string | null): string {
  if (!dateStr) return "";
  try {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString("vi-VN", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  } catch {
    return dateStr;
  }
}

/**
 * Estimate reading time in minutes for article body or summary.
 */
export function estimateReadingTime(contentOrExcerpt?: string | null): number {
  if (!contentOrExcerpt || !contentOrExcerpt.trim()) return 3;
  const plain = contentOrExcerpt.replace(/<[^>]+>/g, " ").trim();
  const words = plain.split(/\s+/).filter(Boolean).length;
  // Vietnamese average reading speed: ~200-240 wpm
  const minutes = Math.ceil(words / 220);
  return Math.max(1, minutes);
}

/**
 * Validate and sanitize an internal redirect URL to prevent open redirect vulnerabilities.
 */
export function sanitizeNextUrl(
  next: string | null | undefined,
  fallback = "/tai-khoan"
): string {
  if (!next || typeof next !== "string") {
    return fallback;
  }
  const trimmed = next.trim();
  // Must start with '/' but not '//' or 'http:' or 'https:' or 'javascript:'
  if (
    !trimmed.startsWith("/") ||
    trimmed.startsWith("//") ||
    trimmed.includes("://") ||
    trimmed.toLowerCase().startsWith("/\\")
  ) {
    return fallback;
  }
  return trimmed;
}

/**
 * Format active duration seconds into serene Vietnamese human-readable string.
 */
export function formatActiveDuration(seconds: number): string {
  if (!seconds || seconds <= 0) return "0 phút";
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  const s = seconds % 60;

  if (h > 0) {
    if (m > 0) return `${h} giờ ${m} phút`;
    return `${h} giờ`;
  }
  if (m > 0) {
    if (s > 0 && m < 5) return `${m} phút ${s} giây`;
    return `${m} phút`;
  }
  return `${s} giây`;
}

/**
 * Format numbers using standard Vietnamese locale grouping (e.g., 1080 -> "1.080").
 */
export function formatNumberVi(num?: number | null): string {
  if (num === null || num === undefined || isNaN(num)) return "0";
  return new Intl.NumberFormat("vi-VN").format(num);
}


