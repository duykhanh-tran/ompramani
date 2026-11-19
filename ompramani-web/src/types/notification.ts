export type NotificationType =
  | 'prayer_intention_approved'
  | 'prayer_intention_rejected'
  | 'prayer_intention_revoked'
  | 'prayer_response_approved'
  | 'prayer_response_rejected'
  | 'prayer_response_revoked'
  | 'prayer_new_approved_response'
  | 'prayer_participation_digest'
  | 'personal_practice_reminder';

export type NotificationCategory = 'moderation' | 'community' | 'practice_reminder';

export interface AppNotification {
  id: string;
  type: NotificationType;
  category: NotificationCategory;
  title: string;
  message: string;
  resource_type: string | null;
  resource_public_id: string | null;
  destination: string;
  read_at: string | null;
  is_read: boolean;
  created_at: string;
}

export interface NotificationPreferences {
  moderation_updates_enabled: boolean;
  new_response_updates_enabled: boolean;
  participation_digest_enabled: boolean;
}

export interface NotificationListResponse {
  data: AppNotification[];
  links?: {
    first?: string;
    last?: string;
    prev?: string | null;
    next?: string | null;
  };
  meta?: {
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
  };
}

export interface UnreadCountResponse {
  data: {
    unread_count: number;
  };
}
