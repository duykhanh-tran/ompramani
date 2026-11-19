export type PrayerResponseModerationStatus =
  | 'pending'
  | 'approved'
  | 'rejected'
  | 'revoked';

export type PrayerResponseReportReason =
  | 'spam'
  | 'harassment'
  | 'personal_information'
  | 'fraud_or_solicitation'
  | 'misinformation'
  | 'unsafe_content'
  | 'other';

export interface PrayerResponseReportReasonOption {
  key: PrayerResponseReportReason;
  label: string;
}

export const PRAYER_RESPONSE_REPORT_REASONS: PrayerResponseReportReasonOption[] = [
  { key: 'spam', label: 'Spam / Quảng cáo / Trùng lặp' },
  { key: 'harassment', label: 'Ngôn từ công kích / Thiếu hòa nhã' },
  { key: 'personal_information', label: 'Tiết lộ thông tin định danh cá nhân' },
  { key: 'fraud_or_solicitation', label: 'Kêu gọi tiền bạc / Dấu hiệu gian lận' },
  { key: 'misinformation', label: 'Thông tin sai lệch / Gây hiểu lầm' },
  { key: 'unsafe_content', label: 'Nội dung nguy hiểm / Khủng hoảng' },
  { key: 'other', label: 'Lý do khác' },
];

export interface PublicPrayerResponse {
  public_id: string;
  body: string;
  published_at: string;
  display_author_label: string;
}

export interface MyPrayerResponse {
  id: number;
  public_id: string;
  prayer_intention_public_id: string;
  prayer_intention_title: string | null;
  body: string;
  moderation_status: PrayerResponseModerationStatus;
  moderation_status_label: string;
  submission_revision: number;
  rejection_reason_code: string | null;
  user_rejection_note: string | null;
  submitted_at: string | null;
  approved_at: string | null;
  published_at: string | null;
  withdrawn_at: string | null;
  created_at: string;
  updated_at: string;
  can_edit: boolean;
  can_withdraw: boolean;
  can_delete: boolean;
  can_resubmit: boolean;
}

export interface CreatePrayerResponsePayload {
  body: string;
  client_request_id?: string;
}

export interface ReportPrayerResponsePayload {
  reason: PrayerResponseReportReason;
  details?: string;
}
