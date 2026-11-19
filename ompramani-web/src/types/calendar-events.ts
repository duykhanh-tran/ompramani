export type CalendarEventType =
  | 'buddhist_holiday'
  | 'buddha_bodhisattva_observance'
  | 'vegetarian_day'
  | 'uposatha_or_observance'
  | 'practice_day'
  | 'other_religious_event';

export type CalendarSystem = 'lunar' | 'solar';

export type CalendarEventRecurrence =
  | 'annual_lunar'
  | 'monthly_lunar'
  | 'one_time_lunar'
  | 'annual_solar'
  | 'one_time_solar';

export type CalendarLeapPolicy =
  | 'regular_only'
  | 'leap_only'
  | 'both'
  | 'manual_review';

export type CalendarMissingDayPolicy =
  | 'skip'
  | 'last_day'
  | 'manual_review';

export type CalendarEventStatus =
  | 'draft'
  | 'in_review'
  | 'published'
  | 'archived';

export interface CalendarEventTradition {
  id: number;
  name: string;
  slug: string;
}

export interface CalendarEventBuddhistFigure {
  id: number;
  name: string;
  slug: string;
  thumbnail?: string | null;
}

export interface CalendarEventOccurrence {
  occurrence_id: string;
  event_id: number;
  title: string;
  slug: string;
  event_type: CalendarEventType;
  event_type_label: string;
  event_type_color: string;
  event_type_priority: number;
  solar_date: string;
  solar_day: number;
  solar_month: number;
  solar_year: number;
  lunar_date: string;
  lunar_day: number;
  lunar_month: number;
  lunar_year: number;
  lunar_is_leap_month: boolean;
  is_general: boolean;
  traditions: CalendarEventTradition[];
  buddhist_figures: CalendarEventBuddhistFigure[];
  short_description: string | null;
  description?: string | null;
  is_exception_adjusted: boolean;
  exception_reason?: string | null;
  missing_day_adjusted?: boolean;
  missing_day_original_day?: number;
}

export interface CalendarEventDetail {
  id: number;
  slug: string;
  title: string;
  short_description: string | null;
  description: string | null;
  event_type: CalendarEventType;
  event_type_label: string;
  event_type_color: string;
  calendar_system: CalendarSystem;
  recurrence_type: CalendarEventRecurrence;
  rule_summary: string;
  is_general: boolean;
  traditions: CalendarEventTradition[];
  buddhist_figures: CalendarEventBuddhistFigure[];
  source: {
    title: string | null;
    url: string | null;
    note: string | null;
    last_verified_at: string | null;
  };
  upcoming_occurrences: CalendarEventOccurrence[];
}

export interface CalendarEventTypeMeta {
  key: CalendarEventType;
  label: string;
  description: string;
  color: string;
  priority: number;
}

export interface CalendarEventsQueryMeta {
  from: string;
  to: string;
  count: number;
  tradition: string | null;
  event_type: string | null;
}

export interface CalendarEventsQueryResponse {
  data: CalendarEventOccurrence[];
  meta: CalendarEventsQueryMeta;
}

export interface CalendarDayEventsData {
  date: string;
  solar?: Record<string, unknown> | null;
  lunar?: Record<string, unknown> | null;
  can_chi?: Record<string, unknown> | string | null;
  tiet_khi?: Record<string, unknown> | string | null;
  events: CalendarEventOccurrence[];
}

export interface CalendarDayEventsResponse {
  data: CalendarDayEventsData;
  meta: {
    date: string;
    count: number;
    tradition: string | null;
    event_type: string | null;
  };
}

export interface CalendarMonthEventsData {
  year: number;
  month: number;
  occurrences: CalendarEventOccurrence[];
  grouped_by_date: Record<string, CalendarEventOccurrence[]>;
}

export interface CalendarMonthEventsResponse {
  data: CalendarMonthEventsData;
  meta: {
    year: number;
    month: number;
    count: number;
    tradition: string | null;
    event_type: string | null;
  };
}

export interface CalendarEventDetailResponse {
  data: CalendarEventDetail;
}

export interface CalendarEventTypesResponse {
  data: CalendarEventTypeMeta[];
}
