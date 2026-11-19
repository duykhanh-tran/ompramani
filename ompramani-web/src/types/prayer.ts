export type PrayerVisibility = 'private' | 'public';

export type PrayerModerationStatus =
  | 'not_required'
  | 'pending'
  | 'approved'
  | 'rejected';

export type PrayerCategoryKey =
  | 'binh_an'
  | 'gia_dinh'
  | 'tri_an'
  | 'hoi_huong'
  | 'thien_lanh'
  | 'khac';

export interface PrayerCategoryOption {
  key: PrayerCategoryKey;
  label: string;
}

export interface PublicPrayerIntention {
  public_id: string;
  title: string | null;
  body: string;
  category: PrayerCategoryKey | null;
  category_label: string | null;
  published_at: string;
  display_author_label: string;
  participation_count: number;
  approved_response_count?: number;
}

export interface MyPrayerParticipation {
  id: number;
  participated_at: string;
  prayer_intention: PublicPrayerIntention | null;
}

export interface ParticipationMutationResponse {
  data: {
    public_id: string;
    is_participating: boolean;
    participation_count: number;
  };
}

export interface BatchParticipationStatusResponse {
  data: {
    statuses: Record<string, boolean>;
  };
}

export interface MyPrayerIntention {
  id: number;
  public_id: string;
  title: string | null;
  body: string;
  category: PrayerCategoryKey | null;
  category_label: string | null;
  visibility: PrayerVisibility;
  visibility_label: string;
  moderation_status: PrayerModerationStatus;
  moderation_status_label: string;
  rejection_reason_code: string | null;
  user_rejection_note: string | null;
  submitted_at: string | null;
  approved_at: string | null;
  published_at: string | null;
  withdrawn_at: string | null;
  created_at: string;
  updated_at: string;
  public_url: string | null;
  can_submit: boolean;
  can_withdraw: boolean;
  can_edit: boolean;
  can_delete: boolean;
}

export interface CreatePrayerIntentionPayload {
  title?: string;
  body: string;
  category?: PrayerCategoryKey;
  visibility?: PrayerVisibility;
  consent_public_sharing?: boolean;
}

export interface UpdatePrayerIntentionPayload {
  title?: string;
  body?: string;
  category?: PrayerCategoryKey;
  visibility?: PrayerVisibility;
  consent_public_sharing?: boolean;
}

export interface PaginatedResponse<T> {
  data: T[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: {
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
  };
}
