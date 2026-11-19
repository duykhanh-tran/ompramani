import { PracticeType } from './practice';

export type PracticeReminderScheduleType = 'once' | 'daily' | 'weekly';

export interface PracticeReminder {
  id: number;
  practice_type: PracticeType;
  practice_label: string;
  title: string | null;
  schedule_type: PracticeReminderScheduleType;
  schedule_label: string;
  local_time: string; // HH:MM
  timezone: string;
  local_date: string | null; // YYYY-MM-DD
  weekdays: number[]; // 1 = Monday ... 7 = Sunday
  is_enabled: boolean;
  schedule_revision: number;
  next_due_at: string | null;
  next_due_local: string | null;
  schedule_description: string;
  destination_url: string;
  created_at: string;
  updated_at: string;
}

export interface PracticeReminderSettings {
  practice_reminders_enabled: boolean;
  quiet_hours_enabled: boolean;
  quiet_start_local_time: string;
  quiet_end_local_time: string;
  quiet_hours_timezone: string;
}

export interface CreatePracticeReminderInput {
  practice_type: PracticeType;
  title?: string | null;
  schedule_type: PracticeReminderScheduleType;
  local_time: string;
  timezone: string;
  local_date?: string | null;
  weekdays?: number[];
}

export interface UpdatePracticeReminderInput {
  practice_type?: PracticeType;
  title?: string | null;
  schedule_type?: PracticeReminderScheduleType;
  local_time?: string;
  timezone?: string;
  local_date?: string | null;
  weekdays?: number[];
  is_enabled?: boolean;
}

export interface UpdatePracticeReminderSettingsInput {
  practice_reminders_enabled?: boolean;
  quiet_hours_enabled?: boolean;
  quiet_start_local_time?: string;
  quiet_end_local_time?: string;
  quiet_hours_timezone?: string;
}

export interface PracticeReminderListResponse {
  data: PracticeReminder[];
}

export interface PracticeReminderSingleResponse {
  data: PracticeReminder;
  message?: string;
}

export interface PracticeReminderSettingsResponse {
  data: PracticeReminderSettings;
  message?: string;
}
