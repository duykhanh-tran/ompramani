const API_BASE_URL =
  process.env.NEXT_PUBLIC_API_BASE_URL || "http://127.0.0.1:8000";

export function resolveMediaUrl(path: string | null | undefined): string | null {
  if (!path || typeof path !== "string") {
    return null;
  }

  const trimmed = path.trim();
  if (!trimmed || trimmed === "null" || trimmed === "undefined") {
    return null;
  }

  // Sanitize duplicate extension if present (e.g. .jpg.jpg -> .jpg)
  const sanitized = trimmed.replace(/(\.[a-zA-Z0-9]{3,4})\1$/i, "$1");

  if (sanitized.startsWith("http://") || sanitized.startsWith("https://")) {
    return sanitized;
  }

  if (sanitized.startsWith("//")) {
    return `https:${sanitized}`;
  }

  if (sanitized.startsWith("data:")) {
    return sanitized;
  }

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

  // If path is a relative storage key like "temple_media/abc.jpg" or "thumbnails/abc.jpg"
  return `${API_BASE_URL}/storage/${sanitized.replace(/^\/+/, "")}`;
}
