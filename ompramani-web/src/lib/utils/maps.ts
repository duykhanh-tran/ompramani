/**
 * Safe Google Maps URL validation utility for OmPraMani.
 * Ensures external links open legitimate Google Maps endpoints and prevents arbitrary URI protocols/XSS.
 */
export function isValidGoogleMapsUrl(url?: string | null): boolean {
  if (!url || typeof url !== "string") {
    return false;
  }

  const trimmed = url.trim();
  if (!trimmed) {
    return false;
  }

  try {
    const parsed = new URL(trimmed);

    // 1. HTTPS scheme only
    if (parsed.protocol.toLowerCase() !== "https:") {
      return false;
    }

    // 2. Reject credentials / userinfo
    if (parsed.username || parsed.password) {
      return false;
    }

    const host = parsed.hostname.toLowerCase();
    const path = parsed.pathname;

    // 3. Official mobile share link
    if (host === "maps.app.goo.gl") {
      return true;
    }

    // 4. Legacy goo.gl share link
    if (host === "goo.gl" && path.startsWith("/maps")) {
      return true;
    }

    // 5. Official Google domains
    const isGoogleHost =
      host === "google.com" ||
      host === "google.com.vn" ||
      host.endsWith(".google.com") ||
      host.endsWith(".google.com.vn");

    if (!isGoogleHost) {
      return false;
    }

    // Subdomains dedicated to maps
    if (host.startsWith("maps.google.")) {
      return true;
    }

    // Main Google domains with /maps path
    if (path.startsWith("/maps")) {
      return true;
    }

    return false;
  } catch {
    return false;
  }
}
