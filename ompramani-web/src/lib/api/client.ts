export function getApiBaseUrl(): string {
  const envUrl = process.env.NEXT_PUBLIC_API_BASE_URL || "http://localhost:8000";

  if (typeof window !== "undefined") {
    const currentHost = window.location.hostname;
    // Align host: if browser is on localhost:3000 -> use localhost:8000; if on 127.0.0.1:3000 -> use 127.0.0.1:8000
    // This guarantees that cookies are shared between frontend and backend on the same hostname.
    if (currentHost === "localhost" || currentHost === "127.0.0.1") {
      try {
        const url = new URL(envUrl);
        url.hostname = currentHost;
        return url.origin;
      } catch {
        // fallback
      }
    }
  }

  return envUrl;
}

export const API_BASE_URL =
  process.env.NEXT_PUBLIC_API_BASE_URL || "http://localhost:8000";

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

export function getXsrfToken(): string | null {
  if (typeof document === "undefined") return null;
  const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
  return match ? decodeURIComponent(match[1]) : null;
}

export function getAuthToken(): string | null {
  if (typeof window === "undefined") return null;
  return localStorage.getItem("auth_token");
}

export function setAuthToken(token: string | null): void {
  if (typeof window === "undefined") return;
  if (token) {
    localStorage.setItem("auth_token", token);
  } else {
    localStorage.removeItem("auth_token");
  }
}

export async function apiClient<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const baseUrl = getApiBaseUrl();
  const url = `${baseUrl}${endpoint.startsWith("/") ? endpoint : `/${endpoint}`}`;

  const headers: Record<string, string> = {
    Accept: "application/json",
    ...(options.headers as Record<string, string>),
  };

  if (!(options.body instanceof FormData) && !headers["Content-Type"]) {
    headers["Content-Type"] = "application/json";
  }

  // Cross-domain SPA authorization: attach Bearer token if stored in browser
  const authToken = getAuthToken();
  if (authToken && !headers["Authorization"]) {
    headers["Authorization"] = `Bearer ${authToken}`;
  }

  const xsrfToken = getXsrfToken();
  if (xsrfToken && options.method && options.method.toUpperCase() !== "GET") {
    headers["X-XSRF-TOKEN"] = xsrfToken;
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
    const response = await fetch(url, fetchOptions);

    if (!response.ok) {
      if (response.status === 401 && endpoint.includes("/auth/me")) {
        setAuthToken(null);
      }

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
