/**
 * Practice Streak TypeScript Types
 * Task 26 — Practice Streak System
 */

export type PracticeStreakStatus = "active_today" | "awaiting_today" | "inactive";

export interface PracticeStreakCalendarDay {
  date: string; // YYYY-MM-DD
  day_of_week: number; // 1 (Mon) to 7 (Sun)
  label: string; // dd/mm
  is_today: boolean;
  qualified: boolean;
  completed_sessions_count: number;
}

export interface PracticeStreakData {
  timezone: string;
  local_today: string;
  current_streak_days: number;
  longest_streak_days: number;
  total_qualifying_days: number;
  last_qualified_date: string | null;
  has_qualified_today: boolean;
  awaiting_today: boolean;
  status: PracticeStreakStatus;
  calendar_days: PracticeStreakCalendarDay[];
}

export interface PracticeStreakResponse {
  data: PracticeStreakData;
}
