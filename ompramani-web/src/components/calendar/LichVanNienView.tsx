'use client';

import React, { useState, useEffect, useRef, useCallback } from 'react';
import { getCalendarMonth, getCalendarDay } from '@/lib/api/calendar';
import { getCalendarMonthEvents, getCalendarDayEvents } from '@/lib/api/calendar-events';
import { getAlmanacMonth, getAlmanacDay } from '@/lib/api/almanac';
import { CalendarMonthData, CalendarDayData } from '@/types/calendar';
import { CalendarMonthEventsData, CalendarDayEventsData } from '@/types/calendar-events';
import { AlmanacMonthData, AlmanacDayData } from '@/types/almanac';
import { CalendarMonthGrid } from './CalendarMonthGrid';
import { SelectedDayDetails } from './SelectedDayDetails';
import {
  getTodayVietnamDate,
  parseDateString,
  isValidDateString,
  isValidMonthString,
} from '@/lib/utils/calendar-date';

interface MonthCacheEntry {
  monthData: CalendarMonthData;
  eventsMonth: CalendarMonthEventsData | null;
  almanacMonth: AlmanacMonthData | null;
}

interface DayCacheEntry {
  dayData: CalendarDayData | null;
  eventsDay: CalendarDayEventsData | null;
  almanacDay: AlmanacDayData | null;
}

interface LichVanNienViewProps {
  initialDate?: string;
  initialMonth?: string;
}

export function LichVanNienView({ initialDate, initialMonth }: LichVanNienViewProps) {

  // 1. Resolve initial date & visible month
  const todayVietnam = getTodayVietnamDate();

  const resolveInitialState = useCallback(() => {
    // Check if initialDate is valid
    if (initialDate && isValidDateString(initialDate)) {
      const parts = parseDateString(initialDate)!;
      return {
        selectedDate: initialDate,
        year: parts.year,
        month: parts.month,
      };
    }

    // Check if initialMonth is valid
    if (initialMonth && isValidMonthString(initialMonth)) {
      const [y, m] = initialMonth.split('-').map((v) => parseInt(v, 10));
      return {
        selectedDate: `${initialMonth}-01`,
        year: y,
        month: m,
      };
    }

    // Default to today
    const todayParts = parseDateString(todayVietnam)!;
    return {
      selectedDate: todayVietnam,
      year: todayParts.year,
      month: todayParts.month,
    };
  }, [initialDate, initialMonth, todayVietnam]);

  const initialState = resolveInitialState();
  const [selectedDate, setSelectedDate] = useState<string>(initialState.selectedDate);
  const [year, setYear] = useState<number>(initialState.year);
  const [month, setMonth] = useState<number>(initialState.month);

  // Month-level data states
  const [monthData, setMonthData] = useState<CalendarMonthData | null>(null);
  const [eventsMonth, setEventsMonth] = useState<CalendarMonthEventsData | null>(null);
  const [almanacMonth, setAlmanacMonth] = useState<AlmanacMonthData | null>(null);
  const [isMonthLoading, setIsMonthLoading] = useState<boolean>(true);
  const [monthError, setMonthError] = useState<string | null>(null);

  // Day-level data states
  const [dayData, setDayData] = useState<CalendarDayData | null>(null);
  const [eventsDay, setEventsDay] = useState<CalendarDayEventsData | null>(null);
  const [almanacDay, setAlmanacDay] = useState<AlmanacDayData | null>(null);
  const [isDayLoading, setIsDayLoading] = useState<boolean>(true);

  // Race condition controllers
  const monthAbortRef = useRef<AbortController | null>(null);
  const dayAbortRef = useRef<AbortController | null>(null);

  // In-memory caches to prevent redundant network hits and rate-limiting
  const monthCacheRef = useRef<Map<string, MonthCacheEntry>>(new Map());
  const dayCacheRef = useRef<Map<string, DayCacheEntry>>(new Map());

  // 2. Fetch Month data (Calendar, Events, Almanac)
  const fetchMonthData = useCallback(async (targetYear: number, targetMonth: number) => {
    const cacheKey = `${targetYear}-${targetMonth}`;
    const cached = monthCacheRef.current.get(cacheKey);

    if (cached) {
      setMonthData(cached.monthData);
      setEventsMonth(cached.eventsMonth);
      setAlmanacMonth(cached.almanacMonth);
      setIsMonthLoading(false);
      setMonthError(null);
      return;
    }

    if (monthAbortRef.current) {
      monthAbortRef.current.abort();
    }
    const controller = new AbortController();
    monthAbortRef.current = controller;

    setIsMonthLoading(true);
    setMonthError(null);

    try {
      const [calRes, evRes, almRes] = await Promise.allSettled([
        getCalendarMonth(targetYear, targetMonth),
        getCalendarMonthEvents({ year: targetYear, month: targetMonth }),
        getAlmanacMonth({ year: targetYear, month: targetMonth }),
      ]);

      if (controller.signal.aborted) return;

      if (calRes.status === 'fulfilled') {
        const eventsVal = evRes.status === 'fulfilled' ? evRes.value : null;
        const almanacVal = almRes.status === 'fulfilled' ? almRes.value : null;

        setMonthData(calRes.value);
        setEventsMonth(eventsVal);
        setAlmanacMonth(almanacVal);

        // Store in cache
        monthCacheRef.current.set(cacheKey, {
          monthData: calRes.value,
          eventsMonth: eventsVal,
          almanacMonth: almanacVal,
        });
      } else {
        const is429 = (calRes.reason as { status?: number })?.status === 429;
        if (is429) {
          console.warn('Calendar month rate-limited (429):', calRes.reason);
          setMonthError('Hệ thống đang tiếp nhận nhiều yêu cầu. Dữ liệu đang được tải lại, quý Phật tử vui lòng đợi giây lát.');
        } else {
          console.error('Failed to load calendar month:', calRes.reason);
          setMonthError('Không thể tải lịch âm dương cho tháng này.');
        }
      }

      if (evRes.status === 'rejected') {
        console.warn('Events month data unavailable:', evRes.reason);
      }
      if (almRes.status === 'rejected') {
        console.warn('Almanac month data unavailable:', almRes.reason);
      }
    } catch (err) {
      if (!controller.signal.aborted) {
        console.error('Unexpected error loading month data:', err);
        setMonthError('Đã xảy ra lỗi khi tải dữ liệu lịch.');
      }
    } finally {
      if (!controller.signal.aborted) {
        setIsMonthLoading(false);
      }
    }
  }, []);

  // 3. Fetch Day details (Day Calendar, Day Events, Day Almanac)
  const fetchDayData = useCallback(async (targetDate: string) => {
    const cacheKey = targetDate;
    const cached = dayCacheRef.current.get(cacheKey);

    if (cached) {
      setDayData(cached.dayData);
      setEventsDay(cached.eventsDay);
      setAlmanacDay(cached.almanacDay);
      setIsDayLoading(false);
      return;
    }

    if (dayAbortRef.current) {
      dayAbortRef.current.abort();
    }
    const controller = new AbortController();
    dayAbortRef.current = controller;

    setIsDayLoading(true);

    try {
      const [dayRes, evDayRes, almDayRes] = await Promise.allSettled([
        getCalendarDay(targetDate),
        getCalendarDayEvents({ date: targetDate }),
        getAlmanacDay({ date: targetDate }),
      ]);

      if (controller.signal.aborted) return;

      const dayVal = dayRes.status === 'fulfilled' ? dayRes.value : null;
      const evVal = evDayRes.status === 'fulfilled' ? evDayRes.value : null;
      const almVal = almDayRes.status === 'fulfilled' ? almDayRes.value : null;

      setDayData(dayVal);
      setEventsDay(evVal);
      setAlmanacDay(almVal);

      // Store in cache
      dayCacheRef.current.set(cacheKey, {
        dayData: dayVal,
        eventsDay: evVal,
        almanacDay: almVal,
      });
    } catch (err) {
      if (!controller.signal.aborted) {
        console.error('Unexpected error loading day data:', err);
      }
    } finally {
      if (!controller.signal.aborted) {
        setIsDayLoading(false);
      }
    }
  }, []);

  // Trigger month fetch when year / month changes
  useEffect(() => {
    fetchMonthData(year, month);
  }, [year, month, fetchMonthData]);

  // Trigger day fetch when selectedDate changes
  useEffect(() => {
    fetchDayData(selectedDate);
  }, [selectedDate, fetchDayData]);

  // Handle user selecting a date
  const handleSelectDate = (newDate: string) => {
    setSelectedDate(newDate);

    // Keep visible month in sync if user selects adjacent date
    const parts = parseDateString(newDate);
    if (parts && (parts.year !== year || parts.month !== month)) {
      setYear(parts.year);
      setMonth(parts.month);
    }

    // Update URL shallowly without triggering server roundtrip
    if (typeof window !== 'undefined') {
      const url = new URL(window.location.href);
      url.searchParams.set('date', newDate);
      url.searchParams.delete('month');
      window.history.replaceState(null, '', url.pathname + url.search);
    }
  };

  // Handle user changing month
  const handleChangeMonth = (newYear: number, newMonth: number) => {
    setYear(newYear);
    setMonth(newMonth);

    // If current selected date is in the newly selected month, keep it.
    // Otherwise, select the 1st of that month or today if in that month.
    const todayParts = parseDateString(todayVietnam);
    if (todayParts && todayParts.year === newYear && todayParts.month === newMonth) {
      setSelectedDate(todayVietnam);
      if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.set('date', todayVietnam);
        url.searchParams.delete('month');
        window.history.replaceState(null, '', url.pathname + url.search);
      }
    } else {
      const monthPrefix = `${newYear}-${String(newMonth).padStart(2, '0')}`;
      if (!selectedDate.startsWith(monthPrefix)) {
        const defaultDate = `${monthPrefix}-01`;
        setSelectedDate(defaultDate);
        if (typeof window !== 'undefined') {
          const url = new URL(window.location.href);
          url.searchParams.delete('date');
          url.searchParams.set('month', monthPrefix);
          window.history.replaceState(null, '', url.pathname + url.search);
        }
      }
    }
  };

  return (
    <div className="max-w-[1440px] mx-auto px-3 sm:px-6 py-4 sm:py-5">
      {/* Compact Page Header Bar */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
        <div>
          <div className="flex items-center gap-2.5 flex-wrap">
            <h1 className="font-headline-md text-xl sm:text-2xl font-bold text-on-surface tracking-tight">
              Lịch Vạn Niên &amp; Phật Lịch
            </h1>
            <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-primary/10 border border-primary/20 text-xs font-label-md text-primary font-medium">
              <span className="w-1.5 h-1.5 rounded-full bg-primary animate-pulse" />
              Âm Dương Việt Nam • Giờ Hà Nội (UTC+07:00)
            </span>
          </div>
          <p className="font-body-md text-xs sm:text-sm text-outline mt-0.5">
            Tra cứu lịch âm dương chính xác, ngày vía Phật &amp; Bồ Tát, ngày trai giới ăn chay và kệ pháp mỗi ngày.
          </p>
        </div>
      </div>

      {/* Main 2-Column Responsive Layout */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        {/* Left Column: Calendar Month Grid (7 cols on lg) */}
        <div className="lg:col-span-7 flex flex-col">
          <CalendarMonthGrid
            year={year}
            month={month}
            selectedDate={selectedDate}
            onSelectDate={handleSelectDate}
            onChangeMonth={handleChangeMonth}
            monthData={monthData}
            eventsMonth={eventsMonth}
            almanacMonth={almanacMonth}
            isLoading={isMonthLoading}
            onRetry={() => fetchMonthData(year, month)}
          />
        </div>

        {/* Right Column: Selected Day Details (5 cols on lg) */}
        <div className="lg:col-span-5 flex flex-col">
          <SelectedDayDetails
            selectedDate={selectedDate}
            dayData={dayData}
            eventsData={eventsDay}
            almanacData={almanacDay}
            isLoading={isDayLoading}
            onGoToday={() => handleSelectDate(todayVietnam)}
          />
        </div>
      </div>
    </div>
  );
}
