import { apiClient } from './client';
import {
  CalendarEventDetail,
  CalendarEventDetailResponse,
  CalendarEventsQueryResponse,
  CalendarEventTypeMeta,
  CalendarEventTypesResponse,
  CalendarDayEventsData,
  CalendarDayEventsResponse,
  CalendarMonthEventsData,
  CalendarMonthEventsResponse,
} from '@/types/calendar-events';

/**
 * Query Buddhist calendar events and observances within a date range [from, to].
 *
 * @param params { from: 'YYYY-MM-DD', to: 'YYYY-MM-DD', tradition?: string, event_type?: string }
 */
export async function getCalendarEvents(params: {
  from: string;
  to: string;
  tradition?: string;
  event_type?: string;
}): Promise<CalendarEventsQueryResponse> {
  const query = new URLSearchParams({
    from: params.from,
    to: params.to,
  });

  if (params.tradition) {
    query.set('tradition', params.tradition);
  }

  if (params.event_type) {
    query.set('event_type', params.event_type);
  }

  return apiClient<CalendarEventsQueryResponse>(
    `/api/v1/calendar/events?${query.toString()}`,
    {
      method: 'GET',
      cache: 'force-cache',
    }
  );
}

/**
 * Get events and lunisolar context for a specific day (default: today).
 */
export async function getCalendarDayEvents(params?: {
  date?: string;
  tradition?: string;
  event_type?: string;
}): Promise<CalendarDayEventsData> {
  const query = new URLSearchParams();

  if (params?.date) {
    query.set('date', params.date);
  }
  if (params?.tradition) {
    query.set('tradition', params.tradition);
  }
  if (params?.event_type) {
    query.set('event_type', params.event_type);
  }

  const url = query.toString()
    ? `/api/v1/calendar/events/day?${query.toString()}`
    : `/api/v1/calendar/events/day`;

  const res = await apiClient<CalendarDayEventsResponse>(url, {
    method: 'GET',
    cache: params?.date ? 'force-cache' : 'no-store',
  });

  return res.data;
}

/**
 * Get events for an entire solar month with grouped_by_date index.
 */
export async function getCalendarMonthEvents(params: {
  year: number;
  month: number;
  tradition?: string;
  event_type?: string;
}): Promise<CalendarMonthEventsData> {
  const query = new URLSearchParams({
    year: params.year.toString(),
    month: params.month.toString(),
  });

  if (params.tradition) {
    query.set('tradition', params.tradition);
  }
  if (params.event_type) {
    query.set('event_type', params.event_type);
  }

  const res = await apiClient<CalendarMonthEventsResponse>(
    `/api/v1/calendar/events/month?${query.toString()}`,
    {
      method: 'GET',
      cache: 'force-cache',
    }
  );

  return res.data;
}

/**
 * Get details of a published Buddhist event by its slug.
 */
export async function getCalendarEventBySlug(slug: string): Promise<CalendarEventDetail> {
  const res = await apiClient<CalendarEventDetailResponse>(
    `/api/v1/calendar/events/${encodeURIComponent(slug)}`,
    {
      method: 'GET',
      cache: 'force-cache',
    }
  );

  return res.data;
}

/**
 * Get list of Buddhist calendar event types and metadata.
 */
export async function getCalendarEventTypes(): Promise<CalendarEventTypeMeta[]> {
  const res = await apiClient<CalendarEventTypesResponse>(
    `/api/v1/calendar/event-types`,
    {
      method: 'GET',
      cache: 'force-cache',
    }
  );

  return res.data;
}
