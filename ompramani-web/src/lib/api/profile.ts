import { apiClient } from "./client";
import { getCsrfCookie } from "./auth";
import {
  AuthUser,
  ChangePasswordPayload,
  UpdateEmailPayload,
  UpdateProfilePayload,
} from "@/types/auth";

interface ProfileApiResponse {
  data: AuthUser;
  message?: string;
}

interface AvatarApiResponse {
  message: string;
  data: {
    avatar: string | null;
    avatar_url: string | null;
    user: AuthUser;
  };
}

/**
 * Fetch authenticated user full profile.
 */
export async function getProfile(): Promise<AuthUser> {
  const res = await apiClient<ProfileApiResponse>("/api/v1/profile", {
    method: "GET",
    cache: "no-store",
  });
  return res.data;
}

/**
 * Update authenticated user profile fields.
 */
export async function updateProfile(
  payload: UpdateProfilePayload
): Promise<{ user: AuthUser; message: string }> {
  await getCsrfCookie();

  const res = await apiClient<ProfileApiResponse>("/api/v1/profile", {
    method: "PATCH",
    body: JSON.stringify(payload),
    cache: "no-store",
  });

  return {
    user: res.data,
    message: res.message || "Đã cập nhật hồ sơ thành công.",
  };
}

/**
 * Upload a new avatar image.
 */
export async function uploadAvatar(
  file: File
): Promise<{
  avatar: string | null;
  avatar_url: string | null;
  user: AuthUser;
  message: string;
}> {
  await getCsrfCookie();

  const formData = new FormData();
  formData.append("avatar", file);

  const res = await apiClient<AvatarApiResponse>("/api/v1/profile/avatar", {
    method: "POST",
    body: formData,
    cache: "no-store",
  });

  return {
    avatar: res.data.avatar,
    avatar_url: res.data.avatar_url,
    user: res.data.user,
    message: res.message || "Đã cập nhật ảnh đại diện thành công.",
  };
}

/**
 * Delete current avatar image.
 */
export async function deleteAvatar(): Promise<{
  user: AuthUser;
  message: string;
}> {
  await getCsrfCookie();

  const res = await apiClient<AvatarApiResponse>("/api/v1/profile/avatar", {
    method: "DELETE",
    cache: "no-store",
  });

  return {
    user: res.data.user,
    message: res.message || "Đã xóa ảnh đại diện.",
  };
}

/**
 * Update user email address (requires current password).
 */
export async function updateEmail(
  payload: UpdateEmailPayload
): Promise<{ user: AuthUser; message: string }> {
  await getCsrfCookie();

  const res = await apiClient<ProfileApiResponse>("/api/v1/account/email", {
    method: "PATCH",
    body: JSON.stringify(payload),
    cache: "no-store",
  });

  return {
    user: res.data,
    message:
      res.message ||
      "Đã cập nhật email thành công. Vui lòng kiểm tra hòm thư để xác thực email mới.",
  };
}

/**
 * Change account password (requires current password).
 */
export async function changePassword(
  payload: ChangePasswordPayload
): Promise<{ message: string }> {
  await getCsrfCookie();

  return await apiClient<{ message: string }>("/api/v1/account/password", {
    method: "PATCH",
    body: JSON.stringify(payload),
    cache: "no-store",
  });
}
