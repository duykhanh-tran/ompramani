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

  if (trimmed.startsWith("http://") || trimmed.startsWith("https://")) {
    return trimmed;
  }

  if (trimmed.startsWith("//")) {
    return `https:${trimmed}`;
  }

  if (trimmed.startsWith("data:")) {
    return trimmed;
  }

  if (trimmed.startsWith("/storage/")) {
    return `${API_BASE_URL}${trimmed}`;
  }

  if (trimmed.startsWith("storage/")) {
    return `${API_BASE_URL}/${trimmed}`;
  }

  // Root-relative path in Next.js public directory (e.g. "/images/...")
  if (trimmed.startsWith("/")) {
    return trimmed;
  }

  // If path is a relative storage key like "temple_media/abc.jpg" or "thumbnails/abc.jpg"
  return `${API_BASE_URL}/storage/${trimmed.replace(/^\/+/, "")}`;
}
