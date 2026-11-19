import { PracticeType } from './practice';

export type PracticePlanStatus = 'active' | 'paused' | 'archived';
export type PracticePlanScheduleType = 'once' | 'weekly';
export type PracticeOccurrenceStatus = 'planned' | 'practiced' | 'skipped';

export interface PracticePlan {
  id: number;
  name: string;
  description: string | null;
  timezone: string;
  status: PracticePlanStatus;
  status_label: string;
  starts_on: string | null;
  ends_on: string | null;
  item_count: number;
  items?: PracticePlanItem[];
  created_at: string;
  updated_at: string;
}

export interface PracticePlanItem {
  id: number;
  practice_plan_id: number;
  practice_type: PracticeType;
  practice_label: string;
  title: string | null;
  schedule_type: PracticePlanScheduleType;
  schedule_label: string;
  local_date: string | null;
  local_time: string | null;
  weekdays: number[];
  target_duration_seconds: number | null;
  target_repetitions: number | null;
  starts_on: string | null;
  ends_on: string | null;
  sort_order: number;
  is_enabled: boolean;
  schedule_revision: number;
  practice_reminder_id: number | null;
  has_reminder: boolean;
  schedule_description: string;
  destination_url: string;
  created_at: string;
  updated_at: string;
}

export interface PracticePlanOccurrence {
  occurrence_id: string;
  plan_id: number;
  plan_name: string;
  plan_item_id: number;
  local_date: string;
  local_time: string | null;
  timezone: string;
  practice_type: PracticeType;
  practice_label: string;
  title: string;
  schedule_type: string;
  schedule_revision: number;
  target: {
    duration_seconds: number | null;
    repetitions: number | null;
  };
  actual: {
    session_id: number;
    duration_seconds: number;
    repetition_count: number | null;
    completed_at: string | null;
    status: string;
    status_label: string;
  } | null;
  status: PracticeOccurrenceStatus;
  status_label: string;
  linked_session_id: number | null;
  destination_url: string;
  can_start: boolean;
  can_skip: boolean;
  can_restore: boolean;
  sort_order: number;
}

export interface CreatePracticePlanInput {
  name: string;
  description?: string | null;
  timezone?: string;
  starts_on?: string | null;
  ends_on?: string | null;
}

export interface UpdatePracticePlanInput {
  name?: string;
  description?: string | null;
  timezone?: string;
  starts_on?: string | null;
  ends_on?: string | null;
}

export interface CreatePracticePlanItemInput {
  practice_type: PracticeType;
  title?: string | null;
  schedule_type: PracticePlanScheduleType;
  local_date?: string | null;
  local_time?: string | null;
  weekdays?: number[];
  target_duration_seconds?: number | null;
  target_repetitions?: number | null;
  starts_on?: string | null;
  ends_on?: string | null;
  sort_order?: number;
  create_reminder?: boolean;
}

export interface UpdatePracticePlanItemInput {
  practice_type?: PracticeType;
  title?: string | null;
  schedule_type?: PracticePlanScheduleType;
  local_date?: string | null;
  local_time?: string | null;
  weekdays?: number[];
  target_duration_seconds?: number | null;
  target_repetitions?: number | null;
  starts_on?: string | null;
  ends_on?: string | null;
  sort_order?: number;
  is_enabled?: boolean;
  sync_reminder?: boolean;
}

export interface CandidatePracticeSession {
  id: number;
  practice_type: PracticeType;
  practice_label: string;
  active_duration_seconds: number;
  repetition_count: number | null;
  started_at: string | null;
  ended_at: string | null;
  status: string;
  status_label: string;
}

export interface PracticePlanListResponse {
  data: PracticePlan[];
}

export interface PracticePlanSingleResponse {
  data: PracticePlan;
  message?: string;
}

export interface PracticePlanItemListResponse {
  data: PracticePlanItem[];
}

export interface PracticePlanItemSingleResponse {
  data: PracticePlanItem;
  message?: string;
}

export interface PracticeOccurrenceListResponse {
  data: PracticePlanOccurrence[];
  meta?: {
    from: string;
    to: string;
    total: number;
  };
  today?: string;
  week?: {
    from: string;
    to: string;
    today: string;
  };
  timezone?: string;
}
