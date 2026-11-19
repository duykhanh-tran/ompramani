export type PrayerRoomMessageType = 'prayer' | 'response';

export type PrayerRoomModerationStatus = 'approved' | 'pending' | 'rejected' | 'revoked';

export interface PrayerRoomReplyTo {
  public_id: string;
  display_name: string;
  body_preview: string;
}

export interface PrayerRoomMessage {
  public_id: string;
  type: PrayerRoomMessageType;
  body: string;
  display_name: string;
  is_own: boolean;
  moderation_status: PrayerRoomModerationStatus | string;
  created_at: string;
  reply_to?: PrayerRoomReplyTo | null;
  can_delete: boolean;
  can_report: boolean;
}

export interface PrayerRoomMessagesMeta {
  has_more_older: boolean;
  has_more_newer: boolean;
  oldest_id: string | null;
  newest_id: string | null;
}

export interface PrayerRoomMessagesResponse {
  data: PrayerRoomMessage[];
  meta: PrayerRoomMessagesMeta;
}

export interface SendPrayerRoomMessagePayload {
  body: string;
  consent_public_sharing: boolean;
  reply_to_public_id?: string | null;
}

export interface ReportPrayerRoomMessagePayload {
  reason: 'spam' | 'harassment' | 'personal_information' | 'fraud_or_solicitation' | 'misinformation' | 'unsafe_content' | 'other';
  details?: string;
}
