import { apiClient } from "./client";
import {
  CalendarConfigData,
  CalendarConfigResponse,
  CalendarDayData,
  CalendarDayResponse,
  CalendarMonthData,
  CalendarMonthResponse,
  CalendarWeekData,
  CalendarWeekResponse,
  LunarToSolarData,
  LunarToSolarResponse,
  SolarToLunarData,
  SolarToLunarResponse,
} from "@/types/calendar";

/**
 * Convert Gregorian solar date to Vietnamese Lunisolar date.
 *
 * @param dateOrComponents Either a date string (YYYY-MM-DD) or an object with { year, month, day }
 */
export async function solarToLunar(
  dateOrComponents: string | { year: number; month: number; day: number }
): Promise<SolarToLunarData> {
  const query = new URLSearchParams();

  if (typeof dateOrComponents === "string") {
    query.set("date", dateOrComponents);
  } else {
    query.set("year", dateOrComponents.year.toString());
    query.set("month", dateOrComponents.month.toString());
    query.set("day", dateOrComponents.day.toString());
  }

  const res = await apiClient<SolarToLunarResponse>(
    `/api/v1/calendar/solar-to-lunar?${query.toString()}`,
    {
      method: "GET",
      cache: "force-cache",
    }
  );

  return res.data;
}

/**
 * Convert Vietnamese Lunisolar date to Gregorian solar date.
 */
export async function lunarToSolar(
  year: number,
  month: number,
  day: number,
  isLeapMonth: boolean = false
): Promise<LunarToSolarData> {
  const query = new URLSearchParams({
    year: year.toString(),
    month: month.toString(),
    day: day.toString(),
    is_leap_month: isLeapMonth ? "1" : "0",
  });

  const res = await apiClient<LunarToSolarResponse>(
    `/api/v1/calendar/lunar-to-solar?${query.toString()}`,
    {
      method: "GET",
      cache: "force-cache",
    }
  );

  return res.data;
}

/**
 * Get comprehensive calendar data for a single day (default: today in Asia/Ho_Chi_Minh).
 */
export async function getCalendarDay(date?: string): Promise<CalendarDayData> {
  const query = new URLSearchParams();
  if (date) {
    query.set("date", date);
  }

  const url = query.toString()
    ? `/api/v1/calendar/day?${query.toString()}`
    : `/api/v1/calendar/day`;

  const res = await apiClient<CalendarDayResponse>(url, {
    method: "GET",
    cache: date ? "force-cache" : "no-store",
  });

  return res.data;
}

/**
 * Get 7-day weekly calendar strip data around a reference date (default: today).
 */
export async function getCalendarWeek(
  date?: string
): Promise<CalendarWeekData> {
  const query = new URLSearchParams();
  if (date) {
    query.set("date", date);
  }

  const url = query.toString()
    ? `/api/v1/calendar/week?${query.toString()}`
    : `/api/v1/calendar/week`;

  const res = await apiClient<CalendarWeekResponse>(url, {
    method: "GET",
    cache: date ? "force-cache" : "no-store",
  });

  return res.data;
}

/**
 * Get full grid calendar data for a solar month.
 */
export async function getCalendarMonth(
  year: number,
  month: number
): Promise<CalendarMonthData> {
  const query = new URLSearchParams({
    year: year.toString(),
    month: month.toString(),
  });

  const res = await apiClient<CalendarMonthResponse>(
    `/api/v1/calendar/month?${query.toString()}`,
    {
      method: "GET",
      cache: "force-cache",
    }
  );

  return res.data;
}

/**
 * Get calendar system configuration and capabilities.
 */
export async function getCalendarConfig(): Promise<CalendarConfigData> {
  const res = await apiClient<CalendarConfigResponse>(
    `/api/v1/calendar/config`,
    {
      method: "GET",
      cache: "force-cache",
    }
  );

  return res.data;
}
