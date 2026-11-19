export type PracticeType =
  | "sutra_reading"
  | "sutra_recitation"
  | "mantra_recitation"
  | "buddha_name_recitation"
  | "meditation"
  | "mala"
  | "bell_wooden_fish";

export type PracticeSessionStatus =
  | "active"
  | "paused"
  | "completed"
  | "cancelled"
  | "expired";

export interface PracticeSubject {
  type: string;
  id: number;
  title: string;
  slug?: string | null;
}

export interface PracticeProgressData {
  planned_duration_seconds?: number;
  bead_count?: number;
  target_beads?: number;
  repetition_count?: number;
  target_repetitions?: number;
  current_section_id?: number;
  completed_sections_count?: number;
  bell_count?: number;
  wooden_fish_count?: number;
  [key: string]: unknown;
}

export interface DerivedMetrics {
  bead_count?: number;
  completed_rounds?: number;
  remaining_beads?: number;
  bell_count?: number;
  wooden_fish_count?: number;
  repetition_count?: number;
  target_repetitions?: number;
  [key: string]: unknown;
}

export interface PracticeSession {
  id: number;
  practice_type: PracticeType;
  practice_type_label: string;
  status: PracticeSessionStatus;
  status_label: string;
  subject?: PracticeSubject | null;
  started_at: string;
  ended_at?: string | null;
  active_duration_seconds: number;
  last_activity_at?: string | null;
  progress_data?: PracticeProgressData;
  derived_metrics?: DerivedMetrics;
  client_session_key?: string | null;
  sequence: number;
  created_at: string;
  updated_at?: string;
}

export interface StartPracticePayload {
  practice_type: PracticeType;
  client_session_key?: string;
  subject_type?: string;
  subject_id?: number;
  progress_data?: PracticeProgressData;
}

export interface UpdateProgressPayload {
  progress_data: PracticeProgressData;
  sequence?: number;
}

export interface CompletePracticePayload {
  progress_data?: PracticeProgressData;
}

export interface PracticeSessionListResponse {
  data: PracticeSession[];
  links?: {
    first?: string | null;
    last?: string | null;
    prev?: string | null;
    next?: string | null;
  };
  meta?: {
    current_page: number;
    from?: number | null;
    last_page: number;
    per_page: number;
    to?: number | null;
    total: number;
  };
}
