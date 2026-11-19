export interface SolarDateData {
  year: number;
  month: number;
  day: number;
  weekday_iso: number;
  weekday_name: string;
  date?: string;
}

export interface LunarDateData {
  year: number;
  month: number;
  day: number;
  is_leap_month: boolean;
  month_length?: number;
  is_leap_year?: boolean;
  leap_month?: number | null;
  year_can_chi: string;
  day_can_chi?: string;
  month_can_chi?: string;
  solar_term?: string;
}

export interface SolarToLunarData {
  solar_date: string;
  solar: SolarDateData;
  lunar: LunarDateData;
  calendar: {
    system: string;
    timezone: string;
  };
}

export interface LunarToSolarData {
  lunar: LunarDateData;
  solar_date: string;
  solar: SolarDateData;
  calendar: {
    system: string;
    timezone: string;
  };
}

export interface CalendarDayData {
  solar: {
    date: string;
    year: number;
    month: number;
    day: number;
    weekday_iso: number;
    weekday_name: string;
  };
  lunar: LunarDateData;
  calendar_timezone: string;
}

export interface CalendarMonthDay {
  solar_date: string;
  solar_day: number;
  weekday_iso: number;
  weekday_name: string;
  lunar_day: number;
  lunar_month: number;
  lunar_year: number;
  is_leap_month: boolean;
  year_can_chi: string;
  day_can_chi: string;
  solar_term: string;
  is_lunar_first_day: boolean;
  is_full_moon: boolean;
}

export interface CalendarMonthData {
  solar_year: number;
  solar_month: number;
  calendar_timezone: string;
  first_weekday_iso: number;
  days_in_month: number;
  days: CalendarMonthDay[];
}

export interface CalendarWeekDay {
  solar_date: string;
  solar_day: string;
  day_of_week: string;
  weekday_iso: number;
  weekday_name: string;
  lunar_day: number;
  lunar_month: number;
  lunar_year: number;
  is_leap_month: boolean;
  lunar_display: string;
  is_today: boolean;
  is_sunday: boolean;
  year_can_chi: string;
  day_can_chi: string;
  month_can_chi: string;
  solar_term: string;
  tag: string | null;
  tag_type: "today" | "veg" | "first_day" | null;
  auspicious_detail: string;
}

export interface CalendarWeekData {
  today: string;
  selected_date: string;
  selected_index: number;
  calendar_timezone: string;
  days: CalendarWeekDay[];
}

export interface CalendarWeekResponse {
  data: CalendarWeekData;
}

export interface CalendarConfigData {
  system: string;
  calendar_timezone: string;
  timezone_offset: string;
  min_supported_solar_date: string;
  max_supported_solar_date: string;
  min_supported_lunar_year: number;
  max_supported_lunar_year: number;
  algorithm: {
    name: string;
    reference_meridian: string;
    license?: string;
  };
  capabilities: string[];
}

export interface SolarToLunarResponse {
  data: SolarToLunarData;
}

export interface LunarToSolarResponse {
  data: LunarToSolarData;
}

export interface CalendarDayResponse {
  data: CalendarDayData;
}

export interface CalendarMonthResponse {
  data: CalendarMonthData;
}

export interface CalendarConfigResponse {
  data: CalendarConfigData;
}
