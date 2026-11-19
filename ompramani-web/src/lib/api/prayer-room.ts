import { apiClient } from './client';
import {
  PrayerRoomMessagesResponse,
  PrayerRoomMessage,
  SendPrayerRoomMessagePayload,
  ReportPrayerRoomMessagePayload,
} from '@/types/prayer-room';

/**
 * Fetch messages from the unified community prayer chat room.
 */
export async function getPrayerRoomMessages(params?: {
  before_id?: string;
  after_id?: string;
  limit?: number;
}): Promise<PrayerRoomMessagesResponse> {
  const query = new URLSearchParams();
  if (params?.before_id) query.set('before_id', params.before_id);
  if (params?.after_id) query.set('after_id', params.after_id);
  if (params?.limit) query.set('limit', params.limit.toString());

  const qs = query.toString();
  return apiClient<PrayerRoomMessagesResponse>(
    `/api/v1/prayer-room/messages${qs ? `?${qs}` : ''}`,
    {
      cache: 'no-store',
    }
  );
}

/**
 * Send a message or supportive reply to the prayer chat room.
 */
export async function sendPrayerRoomMessage(
  payload: SendPrayerRoomMessagePayload
): Promise<{ data: PrayerRoomMessage; message: string }> {
  return apiClient<{ data: PrayerRoomMessage; message: string }>(
    '/api/v1/prayer-room/messages',
    {
      method: 'POST',
      body: JSON.stringify(payload),
    }
  );
}

/**
 * Delete or withdraw a message from the prayer chat room.
 */
export async function deletePrayerRoomMessage(
  publicId: string
): Promise<{ success: boolean; message: string }> {
  return apiClient<{ success: boolean; message: string }>(
    `/api/v1/prayer-room/messages/${encodeURIComponent(publicId)}`,
    {
      method: 'DELETE',
    }
  );
}

/**
 * Report a message in the prayer chat room for moderation.
 */
export async function reportPrayerRoomMessage(
  publicId: string,
  payload: ReportPrayerRoomMessagePayload
): Promise<{ success: boolean; message: string }> {
  return apiClient<{ success: boolean; message: string }>(
    `/api/v1/prayer-room/messages/${encodeURIComponent(publicId)}/report`,
    {
      method: 'POST',
      body: JSON.stringify(payload),
    }
  );
}
