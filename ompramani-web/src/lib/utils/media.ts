const API_BASE_URL =
  process.env.NEXT_PUBLIC_API_BASE_URL || "http://127.0.0.1:8000";

/**
 * Checks if a URL or path is hosted on Cloudinary CDN.
 */
export function isCloudinaryUrl(url: string | null | undefined): boolean {
  if (!url || typeof url !== "string") {
    return false;
  }
  return url.includes("res.cloudinary.com") || url.includes("cloudinary.com");
}

/**
 * Resolves any media path or URL to an absolute URL.
 * Preserves authentic Cloudinary delivery URLs exactly as returned (even if ending in .jpg.jpg).
 * Only sanitizes duplicate extensions on relative local storage keys.
 */
export function resolveMediaUrl(path: string | null | undefined): string | null {
  if (!path || typeof path !== "string") {
    return null;
  }

  const trimmed = path.trim();
  if (!trimmed || trimmed === "null" || trimmed === "undefined") {
    return null;
  }

  // 1. Fully-qualified URLs or data URIs: return immediately as-is.
  // CRITICAL: Authentic Cloudinary asset delivery URLs may legitimately end with .jpg.jpg
  // when public ID contains the file extension. Stripping this causes Cloudinary HTTP 404!
  if (
    trimmed.startsWith("http://") ||
    trimmed.startsWith("https://") ||
    trimmed.startsWith("data:")
  ) {
    return trimmed;
  }

  if (trimmed.startsWith("//")) {
    return `https:${trimmed}`;
  }

  // 2. For relative local paths, clean any accidental duplicate file extensions (.jpg.jpg -> .jpg)
  const sanitized = trimmed.replace(/(\.[a-zA-Z0-9]{3,4})\1$/i, "$1");

  if (sanitized.startsWith("/storage/")) {
    return `${API_BASE_URL}${sanitized}`;
  }

  if (sanitized.startsWith("storage/")) {
    return `${API_BASE_URL}/${sanitized}`;
  }

  // Root-relative path in Next.js public directory (e.g. "/images/...")
  if (sanitized.startsWith("/")) {
    return sanitized;
  }

  // Relative storage key (e.g. "temples/covers/chua-mot-cot.jpg")
  return `${API_BASE_URL}/storage/${sanitized.replace(/^\/+/, "")}`;
}
