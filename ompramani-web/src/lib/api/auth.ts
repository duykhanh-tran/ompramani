import { apiClient, ApiError, setAuthToken } from "./client";
import {
  AuthResponse,
  AuthUser,
  ForgotPasswordPayload,
  LoginPayload,
  RegisterPayload,
  ResetPasswordPayload,
} from "@/types/auth";

/**
 * Kept for backwards compatibility.
 * In the Next.js BFF architecture, cross-origin CSRF cookie fetching is obsolete
 * as authentication is handled statelessly via HttpOnly cookies and server-side BFF proxying.
 */
export async function getCsrfCookie(): Promise<void> {
  // No-op in BFF architecture
  return;
}

/**
 * Register a new user account through Next.js BFF.
 * The session token is stored in an HttpOnly cookie and never exposed to JavaScript.
 */
export async function register(payload: RegisterPayload): Promise<AuthUser> {
  const res = await apiClient<AuthResponse>("/api/auth/register", {
    method: "POST",
    body: JSON.stringify(payload),
    cache: "no-store",
  });

  setAuthToken(null); // Clean up any legacy localStorage entry

  const user =
    res.user ||
    (res.data && "user" in res.data ? res.data.user : (res.data as unknown as AuthUser));
  if (!user) {
    throw new ApiError(500, "Không thể lấy thông tin tài khoản sau khi đăng ký.");
  }

  return user;
}

/**
 * Authenticate user with credentials through Next.js BFF.
 * Token is stored securely in an HttpOnly cookie.
 */
export async function login(payload: LoginPayload): Promise<AuthUser> {
  const res = await apiClient<AuthResponse>("/api/auth/login", {
    method: "POST",
    body: JSON.stringify(payload),
    cache: "no-store",
  });

  setAuthToken(null); // Clean up any legacy localStorage entry

  const user =
    res.user ||
    (res.data && "user" in res.data ? res.data.user : (res.data as unknown as AuthUser));
  if (!user) {
    throw new ApiError(500, "Không thể lấy thông tin tài khoản sau khi đăng nhập.");
  }

  return user;
}

/**
 * Log out current user and clear HttpOnly cookie session.
 */
export async function logout(): Promise<{ message: string }> {
  try {
    return await apiClient<{ message: string }>("/api/auth/logout", {
      method: "POST",
      cache: "no-store",
    });
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      return { message: "Đã đăng xuất." };
    }
    throw error;
  } finally {
    setAuthToken(null);
  }
}

/**
 * Retrieve current authenticated user session from Next.js BFF.
 * Returns null if unauthenticated (401).
 */
export async function getCurrentUser(): Promise<AuthUser | null> {
  try {
    const res = await apiClient<AuthResponse>("/api/auth/me", {
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
    return null;
  }
}

/**
 * Request password reset instructions through Next.js BFF.
 */
export async function forgotPassword(
  payload: ForgotPasswordPayload
): Promise<{ message: string }> {
  return await apiClient<{ message: string }>("/api/auth/forgot-password", {
    method: "POST",
    body: JSON.stringify(payload),
    cache: "no-store",
  });
}

/**
 * Reset password using secret token through Next.js BFF.
 */
export async function resetPassword(
  payload: ResetPasswordPayload
): Promise<{ message: string }> {
  return await apiClient<{ message: string }>("/api/auth/reset-password", {
    method: "POST",
    body: JSON.stringify(payload),
    cache: "no-store",
  });
}

/**
 * Resend email verification notification through Next.js BFF.
 */
export async function resendVerificationEmail(): Promise<{ message: string }> {
  return await apiClient<{ message: string }>("/api/auth/email/resend", {
    method: "POST",
    cache: "no-store",
  });
}

/**
 * Get email verification status through Next.js BFF.
 */
export async function getEmailVerificationStatus(): Promise<{
  email: string;
  email_verified: boolean;
}> {
  const res = await apiClient<{
    data: { email: string; email_verified: boolean };
  }>("/api/auth/email/status", {
    method: "GET",
    cache: "no-store",
  });

  return res.data;
}
