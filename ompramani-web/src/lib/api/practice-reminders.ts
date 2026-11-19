import { apiClient } from './client';
import {
  PracticeReminder,
  PracticeReminderSettings,
  CreatePracticeReminderInput,
  UpdatePracticeReminderInput,
  UpdatePracticeReminderSettingsInput,
  PracticeReminderListResponse,
  PracticeReminderSingleResponse,
  PracticeReminderSettingsResponse,
} from '@/types/practice-reminder';

/**
 * Fetch all practice reminders for the authenticated user.
 */
export async function getPracticeReminders(): Promise<PracticeReminder[]> {
  const res = await apiClient<PracticeReminderListResponse>(
    '/api/v1/me/practice-reminders',
    {
      cache: 'no-store',
    }
  );
  return res.data;
}

/**
 * Fetch a single practice reminder by ID.
 */
export async function getPracticeReminder(id: number): Promise<PracticeReminder> {
  const res = await apiClient<PracticeReminderSingleResponse>(
    `/api/v1/me/practice-reminders/${id}`,
    {
      cache: 'no-store',
    }
  );
  return res.data;
}

/**
 * Create a new practice reminder.
 */
export async function createPracticeReminder(
  data: CreatePracticeReminderInput
): Promise<PracticeReminder> {
  const res = await apiClient<PracticeReminderSingleResponse>(
    '/api/v1/me/practice-reminders',
    {
      method: 'POST',
      body: JSON.stringify(data),
    }
  );
  return res.data;
}

/**
 * Update an existing practice reminder.
 */
export async function updatePracticeReminder(
  id: number,
  data: UpdatePracticeReminderInput
): Promise<PracticeReminder> {
  const res = await apiClient<PracticeReminderSingleResponse>(
    `/api/v1/me/practice-reminders/${id}`,
    {
      method: 'PATCH',
      body: JSON.stringify(data),
    }
  );
  return res.data;
}

/**
 * Delete a practice reminder.
 */
export async function deletePracticeReminder(id: number): Promise<void> {
  await apiClient<{ message?: string }>(
    `/api/v1/me/practice-reminders/${id}`,
    {
      method: 'DELETE',
    }
  );
}

/**
 * Pause a practice reminder.
 */
export async function pausePracticeReminder(id: number): Promise<PracticeReminder> {
  const res = await apiClient<PracticeReminderSingleResponse>(
    `/api/v1/me/practice-reminders/${id}/pause`,
    {
      method: 'POST',
    }
  );
  return res.data;
}

/**
 * Resume a practice reminder.
 */
export async function resumePracticeReminder(id: number): Promise<PracticeReminder> {
  const res = await apiClient<PracticeReminderSingleResponse>(
    `/api/v1/me/practice-reminders/${id}/resume`,
    {
      method: 'POST',
    }
  );
  return res.data;
}

/**
 * Get user practice reminder settings & quiet hours preferences.
 */
export async function getPracticeReminderSettings(): Promise<PracticeReminderSettings> {
  const res = await apiClient<PracticeReminderSettingsResponse>(
    '/api/v1/me/practice-reminder-settings',
    {
      cache: 'no-store',
    }
  );
  return res.data;
}

/**
 * Update user practice reminder settings & quiet hours preferences.
 */
export async function updatePracticeReminderSettings(
  data: UpdatePracticeReminderSettingsInput
): Promise<PracticeReminderSettings> {
  const res = await apiClient<PracticeReminderSettingsResponse>(
    '/api/v1/me/practice-reminder-settings',
    {
      method: 'PUT',
      body: JSON.stringify(data),
    }
  );
  return res.data;
}
