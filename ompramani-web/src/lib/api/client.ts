import { getAuthTokenFromCookies, getBackendApiUrl } from "@/lib/auth/bff";

export function getApiBaseUrl(): string {
  if (typeof window !== "undefined") {
    return window.location.origin;
  }
  return getBackendApiUrl();
}

export const API_BASE_URL =
  process.env.NEXT_PUBLIC_API_BASE_URL ||
  (process.env.NODE_ENV === "production"
    ? "https://ompramani.onrender.com"
    : "http://localhost:8000");

export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
    public data?: unknown
  ) {
    super(message);
    this.name = "ApiError";
  }

  get errors(): Record<string, string[]> | undefined {
    if (this.data && typeof this.data === "object" && "errors" in this.data) {
      return (this.data as { errors?: Record<string, string[]> }).errors;
    }
    return undefined;
  }
}

/**
 * Kept for backwards compatibility; returns null under Next.js BFF architecture
 * where CSRF is verified server-side via Origin/Referer headers.
 */
export function getXsrfToken(): string | null {
  return null;
}

/**
 * Deprecated: auth token is stored in HttpOnly cookie on Next.js domain.
 * Kept for interface compatibility, returns null to avoid localStorage token leaks.
 */
export function getAuthToken(): string | null {
  return null;
}

/**
 * Deprecated: token management is handled automatically by Next.js BFF HttpOnly cookies.
 * Cleans up any legacy token stored in browser localStorage.
 */
// eslint-disable-next-line @typescript-eslint/no-unused-vars
export function setAuthToken(_token?: string | null): void {
  if (typeof window === "undefined") return;
  try {
    localStorage.removeItem("auth_token");
  } catch {
    // Ignore storage quota / security exceptions
  }
}

export async function apiClient<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const isBrowser = typeof window !== "undefined";
  let targetUrl: string;

  if (isBrowser) {
    const origin = window.location.origin;
    const cleanEndpoint = endpoint.startsWith("/") ? endpoint : `/${endpoint}`;

    if (cleanEndpoint.startsWith("/api/v1/")) {
      targetUrl = `${origin}/api/proxy${cleanEndpoint}`;
    } else if (cleanEndpoint.startsWith("/api/")) {
      targetUrl = `${origin}${cleanEndpoint}`;
    } else {
      targetUrl = `${origin}/api/proxy/${cleanEndpoint.replace(/^\/+/, "")}`;
    }
  } else {
    // Server-side (SSR / RSC)
    const backendBase = getBackendApiUrl();
    let cleanEndpoint = endpoint.startsWith("/") ? endpoint : `/${endpoint}`;

    if (cleanEndpoint.startsWith("/api/proxy/")) {
      cleanEndpoint = cleanEndpoint.replace("/api/proxy", "");
    } else if (cleanEndpoint.startsWith("/api/auth/")) {
      cleanEndpoint = cleanEndpoint.replace("/api/auth/", "/api/v1/auth/");
    }

    targetUrl = `${backendBase}${cleanEndpoint}`;
  }

  const headers: Record<string, string> = {
    Accept: "application/json",
    ...(options.headers as Record<string, string>),
  };

  if (!(options.body instanceof FormData) && !headers["Content-Type"]) {
    headers["Content-Type"] = "application/json";
  }

  // On the server, forward auth token from cookies if available
  if (!isBrowser && !headers["Authorization"]) {
    try {
      const serverToken = await getAuthTokenFromCookies();
      if (serverToken) {
        headers["Authorization"] = `Bearer ${serverToken}`;
      }
    } catch {
      // Cookies not accessible outside request context
    }
  }

  const fetchOptions: RequestInit = {
    credentials: options.credentials ?? "include",
    ...options,
    headers,
  };

  // Private & Financial endpoints MUST NEVER use shared public cache or ISR
  const isPrivateOrFinancial =
    endpoint.includes("/auth") ||
    endpoint.includes("/profile") ||
    endpoint.includes("/account") ||
    endpoint.includes("/cart") ||
    endpoint.includes("/checkout") ||
    endpoint.includes("/orders") ||
    endpoint.includes("/payment") ||
    endpoint.includes("/practice") ||
    endpoint.includes("/reading-progress") ||
    endpoint.includes("/sutra-bookmarks") ||
    endpoint.includes("/favorites") ||
    endpoint.includes("/notification") ||
    endpoint.includes("/my-");

  // Only apply ISR revalidation if cache is not explicitly disabled, method is GET, and endpoint is public
  if (
    !isPrivateOrFinancial &&
    options.cache !== "no-store" &&
    !options.next &&
    (!options.method || options.method.toUpperCase() === "GET")
  ) {
    fetchOptions.next = { revalidate: 60 };
  } else if (isPrivateOrFinancial && !options.cache) {
    fetchOptions.cache = "no-store";
  }

  try {
    const response = await fetch(targetUrl, fetchOptions);

    if (!response.ok) {
      let errorData;
      try {
        errorData = await response.json();
      } catch {
        // ignore json parse error
      }
      throw new ApiError(
        response.status,
        errorData?.message || `API error ${response.status}: ${response.statusText}`,
        errorData
      );
    }

    return (await response.json()) as T;
  } catch (error) {
    if (error instanceof ApiError) {
      throw error;
    }
    if (
      (error instanceof Error && error.name === "AbortError") ||
      (error instanceof Error && error.message.toLowerCase().includes("abort"))
    ) {
      throw error;
    }
    throw new ApiError(
      500,
      error instanceof Error ? error.message : "Network error"
    );
  }
}
