import { apiClient } from './client';
import {
  AppNotification,
  NotificationPreferences,
  NotificationListResponse,
  UnreadCountResponse,
} from '@/types/notification';

/**
 * Fetch paginated in-app notifications for the authenticated user.
 */
export async function getMyNotifications(
  filter: 'all' | 'unread' = 'all',
  page = 1,
  perPage = 15
): Promise<NotificationListResponse> {
  const query = new URLSearchParams({
    filter,
    page: page.toString(),
    per_page: perPage.toString(),
  });

  return apiClient<NotificationListResponse>(
    `/api/v1/me/notifications?${query.toString()}`,
    {
      cache: 'no-store',
    }
  );
}

/**
 * Get the count of unread notifications for the authenticated user.
 */
export async function getUnreadNotificationCount(): Promise<{ unread_count: number }> {
  const res = await apiClient<UnreadCountResponse>(
    '/api/v1/me/notifications/unread-count',
    {
      cache: 'no-store',
    }
  );
  return res.data;
}

/**
 * Mark a single notification as read.
 */
export async function markNotificationRead(id: string): Promise<AppNotification> {
  const res = await apiClient<{ data: AppNotification }>(
    `/api/v1/me/notifications/${encodeURIComponent(id)}/read`,
    {
      method: 'PATCH',
    }
  );
  return res.data;
}

/**
 * Mark all unread notifications of the authenticated user as read.
 */
export async function markAllNotificationsRead(): Promise<{ message: string }> {
  return apiClient<{ message: string }>('/api/v1/me/notifications/read-all', {
    method: 'POST',
  });
}

/**
 * Dismiss/delete a notification from the user's inbox.
 */
export async function dismissNotification(id: string): Promise<{ message: string }> {
  return apiClient<{ message: string }>(
    `/api/v1/me/notifications/${encodeURIComponent(id)}`,
    {
      method: 'DELETE',
    }
  );
}

/**
 * Fetch effective notification preferences for the authenticated user.
 */
export async function getNotificationPreferences(): Promise<NotificationPreferences> {
  const res = await apiClient<{ data: NotificationPreferences }>(
    '/api/v1/me/notification-preferences',
    {
      cache: 'no-store',
    }
  );
  return res.data;
}

/**
 * Update notification preferences for the authenticated user.
 */
export async function updateNotificationPreferences(
  payload: NotificationPreferences
): Promise<NotificationPreferences> {
  const res = await apiClient<{ data: NotificationPreferences }>(
    '/api/v1/me/notification-preferences',
    {
      method: 'PUT',
      body: JSON.stringify(payload),
    }
  );
  return res.data;
}
