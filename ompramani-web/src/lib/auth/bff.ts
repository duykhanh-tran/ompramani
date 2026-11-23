import { cookies } from "next/headers.js";

export const AUTH_COOKIE_NAME = "auth_token";

/**
 * Resolve the backend Laravel API base URL.
 * Prefers server-side API_BASE_URL, falls back to NEXT_PUBLIC_API_BASE_URL,
 * then defaults to production Render or local Herd/Artisan.
 */
export function getBackendApiUrl(): string {
  const envUrl =
    process.env.API_BASE_URL ||
    process.env.NEXT_PUBLIC_API_BASE_URL ||
    (process.env.NODE_ENV === "production"
      ? "https://ompramani.onrender.com"
      : "http://localhost:8000");

  return envUrl.replace(/\/+$/, "");
}

/**
 * Verify request Origin / Referer for CSRF prevention on state-changing requests.
 */
export function isOriginAllowed(request: Request): boolean {
  const origin = request.headers.get("origin");
  const referer = request.headers.get("referer");
  const host = request.headers.get("host") || "";

  const checkUrl = (urlStr: string | null): boolean => {
    if (!urlStr) return false;
    try {
      const parsed = new URL(urlStr);
      const incomingHost = parsed.host;
      if (host && incomingHost === host) return true;
      if (
        incomingHost === "ompramani.vercel.app" ||
        incomingHost.endsWith(".vercel.app") ||
        incomingHost.startsWith("localhost:") ||
        incomingHost === "localhost" ||
        incomingHost.startsWith("127.0.0.1:") ||
        incomingHost === "127.0.0.1"
      ) {
        return true;
      }
      return false;
    } catch {
      return false;
    }
  };

  if (origin && checkUrl(origin)) return true;
  if (referer && checkUrl(referer)) return true;

  // In non-browser environments or when headers are omitted in dev, allow if no origin/referer
  if (!origin && !referer && process.env.NODE_ENV !== "production") {
    return true;
  }

  return false;
}

/**
 * Standard cookie configuration for HttpOnly session token.
 */
export function getCookieOptions(remember?: boolean) {
  const isProduction = process.env.NODE_ENV === "production";
  return {
    name: AUTH_COOKIE_NAME,
    httpOnly: true,
    secure: isProduction,
    sameSite: "lax" as const,
    path: "/",
    maxAge: remember ? 30 * 24 * 60 * 60 : 7 * 24 * 60 * 60, // 30 days vs 7 days
  };
}

/**
 * Retrieve the auth token from Next.js HttpOnly cookies.
 */
export async function getAuthTokenFromCookies(): Promise<string | null> {
  const cookieStore = await cookies();
  const token = cookieStore.get(AUTH_COOKIE_NAME)?.value;
  return token || null;
}

/**
 * Set the auth token in an HttpOnly cookie.
 */
export async function setAuthTokenCookie(
  token: string,
  remember?: boolean
): Promise<void> {
  const cookieStore = await cookies();
  const options = getCookieOptions(remember);
  cookieStore.set({
    ...options,
    value: token,
  });
}

/**
 * Clear the auth token HttpOnly cookie.
 */
export async function clearAuthTokenCookie(): Promise<void> {
  const cookieStore = await cookies();
  cookieStore.set({
    name: AUTH_COOKIE_NAME,
    value: "",
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax" as const,
    path: "/",
    maxAge: 0,
  });
}
