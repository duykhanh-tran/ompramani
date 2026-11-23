import { apiClient, ApiError, getApiBaseUrl, setAuthToken } from "./client";
import {
  AuthResponse,
  AuthUser,
  ForgotPasswordPayload,
  LoginPayload,
  RegisterPayload,
  ResetPasswordPayload,
} from "@/types/auth";

/**
 * Request CSRF cookie initialization from Laravel Sanctum.
 */
export async function getCsrfCookie(): Promise<void> {
  if (typeof window === "undefined") return;

  try {
    const baseUrl = getApiBaseUrl();
    await fetch(`${baseUrl}/sanctum/csrf-cookie`, {
      method: "GET",
      credentials: "include",
      headers: {
        Accept: "application/json",
      },
    });
  } catch (err) {
    console.warn("Could not retrieve Sanctum CSRF cookie:", err);
  }
}

/**
 * Register a new user account.
 */
export async function register(payload: RegisterPayload): Promise<AuthUser> {
  await getCsrfCookie();

  const res = await apiClient<AuthResponse>("/api/v1/auth/register", {
    method: "POST",
    body: JSON.stringify(payload),
    cache: "no-store",
  });

  const token = res.data?.token || res.token;
  if (token) {
    setAuthToken(token);
  }

  const user =
    res.user ||
    (res.data && "user" in res.data ? res.data.user : (res.data as unknown as AuthUser));
  if (!user) {
    throw new ApiError(500, "Không thể lấy thông tin tài khoản sau khi đăng ký.");
  }

  return user;
}

/**
 * Authenticate user with credentials.
 */
export async function login(payload: LoginPayload): Promise<AuthUser> {
  await getCsrfCookie();

  const res = await apiClient<AuthResponse>("/api/v1/auth/login", {
    method: "POST",
    body: JSON.stringify(payload),
    cache: "no-store",
  });

  const token = res.data?.token || res.token;
  if (token) {
    setAuthToken(token);
  }

  const user =
    res.user ||
    (res.data && "user" in res.data ? res.data.user : (res.data as unknown as AuthUser));
  if (!user) {
    throw new ApiError(500, "Không thể lấy thông tin tài khoản sau khi đăng nhập.");
  }

  return user;
}

/**
 * Log out current user and clear server session.
 */
export async function logout(): Promise<{ message: string }> {
  try {
    await getCsrfCookie();
    return await apiClient<{ message: string }>("/api/v1/auth/logout", {
      method: "POST",
      cache: "no-store",
    });
  } catch (error) {
    // Even if server returns error or is already logged out, consider client logged out
    if (error instanceof ApiError && error.status === 401) {
      return { message: "Đã đăng xuất." };
    }
    throw error;
  } finally {
    setAuthToken(null);
  }
}

/**
 * Retrieve current authenticated user session.
 * Returns null if unauthenticated (401).
 */
export async function getCurrentUser(): Promise<AuthUser | null> {
  try {
    const res = await apiClient<AuthResponse>("/api/v1/auth/me", {
      method: "GET",
      cache: "no-store",
    });

    const user =
      res.user ||
      (res.data && "user" in res.data ? res.data.user : (res.data as unknown as AuthUser)) ||
      null;

    return user;
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      setAuthToken(null);
      return null;
    }
    // Network or other unexpected error
    return null;
  }
}

/**
 * Request password reset instructions.
 */
export async function forgotPassword(
  payload: ForgotPasswordPayload
): Promise<{ message: string }> {
  await getCsrfCookie();

  return await apiClient<{ message: string }>("/api/v1/auth/forgot-password", {
    method: "POST",
    body: JSON.stringify(payload),
    cache: "no-store",
  });
}

/**
 * Reset password using secret token.
 */
export async function resetPassword(
  payload: ResetPasswordPayload
): Promise<{ message: string }> {
  await getCsrfCookie();

  return await apiClient<{ message: string }>("/api/v1/auth/reset-password", {
    method: "POST",
    body: JSON.stringify(payload),
    cache: "no-store",
  });
}

/**
 * Resend email verification notification.
 */
export async function resendVerificationEmail(): Promise<{ message: string }> {
  await getCsrfCookie();

  return await apiClient<{ message: string }>("/api/v1/auth/email/resend", {
    method: "POST",
    cache: "no-store",
  });
}

/**
 * Get email verification status.
 */
export async function getEmailVerificationStatus(): Promise<{
  email: string;
  email_verified: boolean;
}> {
  const res = await apiClient<{
    data: { email: string; email_verified: boolean };
  }>("/api/v1/auth/email/status", {
    method: "GET",
    cache: "no-store",
  });

  return res.data;
}
