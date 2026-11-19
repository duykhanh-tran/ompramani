'use client';

import React, { useState } from 'react';
import { CalendarMonthData } from '@/types/calendar';
import { CalendarMonthEventsData, CalendarEventOccurrence } from '@/types/calendar-events';
import { AlmanacMonthData, AlmanacMonthDaySummary } from '@/types/almanac';
import {
  addMonths,
  formatDateString,
  getTodayVietnamDate,
  MIN_SUPPORTED_YEAR,
  MAX_SUPPORTED_YEAR,
  VIETNAMESE_MONTH_NAMES,
} from '@/lib/utils/calendar-date';

interface CalendarMonthGridProps {
  year: number;
  month: number;
  selectedDate: string;
  onSelectDate: (date: string) => void;
  onChangeMonth: (year: number, month: number) => void;
  monthData: CalendarMonthData | null;
  eventsMonth: CalendarMonthEventsData | null;
  almanacMonth: AlmanacMonthData | null;
  isLoading: boolean;
  onRetry?: () => void;
}

export function CalendarMonthGrid({
  year,
  month,
  selectedDate,
  onSelectDate,
  onChangeMonth,
  monthData,
  eventsMonth,
  almanacMonth,
  isLoading,
  onRetry,
}: CalendarMonthGridProps) {
  const [showPicker, setShowPicker] = useState(false);
  const todayDateStr = getTodayVietnamDate();

  const handlePrevMonth = () => {
    const prev = addMonths(year, month, -1);
    onChangeMonth(prev.year, prev.month);
  };

  const handleNextMonth = () => {
    const next = addMonths(year, month, 1);
    onChangeMonth(next.year, next.month);
  };

  const handleGoToday = () => {
    const [y, m] = todayDateStr.split('-').map((v) => parseInt(v, 10));
    onChangeMonth(y, m);
    onSelectDate(todayDateStr);
  };

  // Build lookups for events and almanac by solar_date
  const eventsByDate = React.useMemo(() => {
    const map = new Map<string, CalendarEventOccurrence[]>();
    const list = eventsMonth?.occurrences || [];
    for (const ev of list) {
      const arr = map.get(ev.solar_date) || [];
      arr.push(ev);
      map.set(ev.solar_date, arr);
    }
    return map;
  }, [eventsMonth]);

  const almanacByDate = React.useMemo(() => {
    const map = new Map<string, AlmanacMonthDaySummary>();
    if (almanacMonth?.days) {
      for (const d of almanacMonth.days) {
        map.set(d.solar_date, d);
      }
    }
    return map;
  }, [almanacMonth]);

  // Weekday headings Monday to Sunday
  const weekdayHeadings = [
    { label: 'Thứ 2', short: 'T2', isSunday: false },
    { label: 'Thứ 3', short: 'T3', isSunday: false },
    { label: 'Thứ 4', short: 'T4', isSunday: false },
    { label: 'Thứ 5', short: 'T5', isSunday: false },
    { label: 'Thứ 6', short: 'T6', isSunday: false },
    { label: 'Thứ 7', short: 'T7', isSunday: false },
    { label: 'Chủ Nhật', short: 'CN', isSunday: true },
  ];

  // Calculate grid padding for first weekday
  const firstWeekdayIso = monthData?.first_weekday_iso ?? 1; // 1 = Monday, 7 = Sunday
  const leadingEmptyCells = Math.max(0, firstWeekdayIso - 1);
  const daysInMonth = monthData?.days?.length ?? 0;
  // Always pad to 42 cells (6 rows x 7 cols = 42 cells) so month frame never jumps regardless of day count
  const trailingEmptyCells = Math.max(0, 42 - (leadingEmptyCells + daysInMonth));

  return (
    <div className="bg-surface-container-lowest border border-outline-variant/50 rounded-3xl p-4 sm:p-5 shadow-sm w-full flex flex-col justify-between min-h-[580px] lg:min-h-[640px]">
      {/* Month Header & Controls */}
      <div className="flex flex-wrap items-center justify-between gap-3 mb-4 pb-3 border-b border-outline-variant/30">
        <div className="flex items-center gap-2">
          <div className="relative">
            <button
              type="button"
              onClick={() => setShowPicker(!showPicker)}
              className="flex items-center gap-2 px-3 py-1.5 rounded-xl hover:bg-surface-container-low transition-colors group"
              aria-label="Chọn tháng và năm"
            >
              <h2 className="font-headline-md text-xl sm:text-2xl font-semibold text-on-surface tracking-tight group-hover:text-primary transition-colors">
                {VIETNAMESE_MONTH_NAMES[month]}, {year}
              </h2>
              <span className="material-symbols-outlined text-[20px] text-outline group-hover:text-primary transition-transform">
                {showPicker ? 'expand_less' : 'expand_more'}
              </span>
            </button>

            {/* Quick Month & Year Picker Dropdown */}
            {showPicker && (
              <div className="absolute top-full left-0 mt-2 p-4 bg-surface-container-lowest border border-outline-variant/60 rounded-2xl shadow-xl z-30 w-72 animate-in fade-in zoom-in-95 duration-150">
                <div className="flex items-center justify-between mb-3 pb-2 border-b border-outline-variant/20">
                  <span className="font-label-md font-semibold text-xs text-outline uppercase tracking-wider">
                    Chọn nhanh tháng &amp; năm
                  </span>
                  <button
                    type="button"
                    onClick={() => setShowPicker(false)}
                    className="text-outline hover:text-on-surface p-1 rounded-lg"
                  >
                    <span className="material-symbols-outlined text-[18px]">close</span>
                  </button>
                </div>

                <div className="space-y-3">
                  <div>
                    <label className="block text-xs font-label-md text-outline mb-1">Năm</label>
                    <select
                      value={year}
                      onChange={(e) => {
                        const newYear = parseInt(e.target.value, 10);
                        onChangeMonth(newYear, month);
                      }}
                      className="w-full px-3 py-2 bg-surface-container-low border border-outline-variant/50 rounded-xl text-sm font-body-md text-on-surface focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                      {Array.from(
                        { length: MAX_SUPPORTED_YEAR - MIN_SUPPORTED_YEAR + 1 },
                        (_, i) => MIN_SUPPORTED_YEAR + i
                      ).map((y) => (
                        <option key={y} value={y}>
                          Năm {y}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="block text-xs font-label-md text-outline mb-1">Tháng</label>
                    <div className="grid grid-cols-3 gap-1.5">
                      {Array.from({ length: 12 }, (_, i) => i + 1).map((m) => (
                        <button
                          key={m}
                          type="button"
                          onClick={() => {
                            onChangeMonth(year, m);
                            setShowPicker(false);
                          }}
                          className={`py-1.5 px-2 rounded-lg text-xs font-label-md transition-colors ${
                            m === month
                              ? 'bg-primary text-on-primary font-semibold'
                              : 'bg-surface-container-low hover:bg-surface-container text-on-surface'
                          }`}
                        >
                          T{m}
                        </button>
                      ))}
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>

          {isLoading && (
            <span className="w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin shrink-0"></span>
          )}
        </div>

        {/* Navigation Action Buttons */}
        <div className="flex items-center gap-1.5 sm:gap-2">
          <button
            type="button"
            onClick={handleGoToday}
            className="px-3 py-1.5 rounded-full border border-outline-variant/60 text-xs font-label-md font-medium text-primary hover:bg-surface-container-low hover:border-primary/50 transition-colors shadow-2xs"
            title="Quay lại ngày hôm nay"
          >
            Hôm nay
          </button>

          <button
            type="button"
            onClick={handlePrevMonth}
            disabled={year <= MIN_SUPPORTED_YEAR && month <= 1}
            className="w-9 h-9 rounded-full border border-outline-variant/60 flex items-center justify-center text-on-surface hover:text-primary hover:border-primary/50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
            title="Tháng trước"
            aria-label="Tháng trước"
          >
            <span className="material-symbols-outlined text-[20px]">chevron_left</span>
          </button>

          <button
            type="button"
            onClick={handleNextMonth}
            disabled={year >= MAX_SUPPORTED_YEAR && month >= 12}
            className="w-9 h-9 rounded-full border border-outline-variant/60 flex items-center justify-center text-on-surface hover:text-primary hover:border-primary/50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
            title="Tháng sau"
            aria-label="Tháng sau"
          >
            <span className="material-symbols-outlined text-[20px]">chevron_right</span>
          </button>
        </div>
      </div>

      {/* Weekday Labels Header */}
      <div className="grid grid-cols-7 gap-1 sm:gap-2 mb-2 text-center">
        {weekdayHeadings.map((w, idx) => (
          <div
            key={idx}
            className={`py-2 text-xs sm:text-sm font-label-md font-semibold tracking-wider uppercase ${
              w.isSunday ? 'text-error/85' : 'text-outline'
            }`}
          >
            <span className="hidden sm:inline">{w.label}</span>
            <span className="sm:hidden">{w.short}</span>
          </div>
        ))}
      </div>

      {/* Grid Content / Days */}
      {!monthData && !isLoading ? (
        <div className="py-16 text-center text-on-surface-variant space-y-3">
          <span className="material-symbols-outlined text-4xl text-outline/60">calendar_month</span>
          <p className="text-sm font-body-md">Không thể tải dữ liệu lịch cho tháng này.</p>
          {onRetry && (
            <button
              type="button"
              onClick={onRetry}
              className="px-4 py-2 rounded-full bg-primary text-on-primary text-xs font-label-md font-medium hover:bg-primary/90 transition-colors"
            >
              Thử lại
            </button>
          )}
        </div>
      ) : (
        <div className="grid grid-cols-7 gap-1 sm:gap-2">
          {/* Leading empty cells before 1st of month */}
          {Array.from({ length: leadingEmptyCells }).map((_, idx) => (
            <div
              key={`empty-${idx}`}
              className="min-h-[50px] sm:min-h-[64px] lg:min-h-[58px] xl:min-h-[62px] rounded-xl sm:rounded-2xl bg-surface-container-lowest/30 border border-transparent pointer-events-none"
              aria-hidden="true"
            />
          ))}

          {/* Actual Month Days */}
          {monthData?.days.map((day) => {
            const dateStr = day.solar_date;
            const isSelected = selectedDate === dateStr;
            const isToday = todayDateStr === dateStr;
            const isSunday = day.weekday_iso === 7;

            // Events for this day
            const dayEvents = eventsByDate.get(dateStr) || [];
            const hasVegDay = dayEvents.some((e) => e.event_type === 'vegetarian_day');
            const hasReligiousFeast = dayEvents.some((e) => e.event_type === 'buddhist_holiday' || e.event_type === 'buddha_bodhisattva_observance');
            const hasAnyEvent = dayEvents.length > 0;

            // Almanac for this day
            const dayAlmanac = almanacByDate.get(dateStr);
            const isHoangDao = dayAlmanac?.day_classification === 'hoang_dao';

            // Lunar display text in cell:
            // If day 1 of lunar month -> show month e.g. "1/9" or "1/6 N"
            let lunarCellText = `${day.lunar_day}`;
            if (day.lunar_day === 1) {
              lunarCellText = `1/${day.lunar_month}${day.is_leap_month ? 'N' : ''}`;
            }

            const accessibleLabel = `${day.weekday_name}, ngày ${day.solar_day} tháng ${month} năm ${year}. Âm lịch ngày ${day.lunar_day} tháng ${day.lunar_month}${day.is_leap_month ? ' nhuận' : ''} năm ${day.year_can_chi}.${hasAnyEvent ? ` Có ${dayEvents.length} sự kiện Phật giáo.` : ''}${isHoangDao ? ' Ngày Hoàng Đạo.' : ''}`;

            return (
              <button
                key={dateStr}
                type="button"
                onClick={() => onSelectDate(dateStr)}
                aria-label={accessibleLabel}
                aria-selected={isSelected}
                className={`relative group flex flex-col justify-between p-1 sm:p-2 min-h-[50px] sm:min-h-[64px] lg:min-h-[58px] xl:min-h-[62px] rounded-xl sm:rounded-2xl border transition-all text-left outline-none focus-visible:ring-2 focus-visible:ring-primary ${
                  isSelected
                    ? 'bg-primary text-on-primary border-primary shadow-md scale-[1.02] z-10'
                    : isToday
                      ? 'bg-primary-container/20 border-primary/60 text-on-surface hover:bg-primary-container/30'
                      : 'bg-surface-container-low/40 hover:bg-surface-container-low border-outline-variant/40 text-on-surface'
                }`}
              >
                {/* Top Row: Solar Day + Event Indicators */}
                <div className="flex items-start justify-between w-full">
                  <span
                    className={`font-headline-md text-base sm:text-lg font-semibold leading-none ${
                      isSelected
                        ? 'text-on-primary'
                        : isSunday
                          ? 'text-error'
                          : 'text-on-surface'
                    }`}
                  >
                    {day.solar_day}
                  </span>

                  {/* Indicator Dots */}
                  <div className="flex items-center gap-1 shrink-0">
                    {hasReligiousFeast && (
                      <span
                        className={`w-1.5 h-1.5 rounded-full ${
                          isSelected ? 'bg-secondary-fixed ring-1 ring-secondary-fixed' : 'bg-amber-500'
                        }`}
                        title="Đại lễ / Ngày vía Phật & Bồ Tát"
                      />
                    )}
                    {hasVegDay && (
                      <span
                        className={`w-1.5 h-1.5 rounded-full ${
                          isSelected ? 'bg-emerald-300 ring-1 ring-emerald-300' : 'bg-emerald-600'
                        }`}
                        title="Ngày ăn chay / Trai giới"
                      />
                    )}
                    {!hasReligiousFeast && !hasVegDay && hasAnyEvent && (
                      <span
                        className={`w-1.5 h-1.5 rounded-full ${
                          isSelected ? 'bg-on-primary/80' : 'bg-primary/70'
                        }`}
                        title="Sự kiện Phật giáo"
                      />
                    )}
                  </div>
                </div>

                {/* Bottom Row: Lunar Day & Special Badges */}
                <div className="flex items-end justify-between w-full mt-1">
                  <span
                    className={`font-label-md text-[11px] sm:text-xs truncate ${
                      isSelected
                        ? 'text-on-primary/90 font-medium'
                        : day.lunar_day === 1 || day.lunar_day === 15
                          ? 'text-primary font-semibold'
                          : 'text-outline font-normal'
                    }`}
                  >
                    {lunarCellText}
                  </span>

                  {/* Hoàng Đạo marker (subtle) */}
                  {isHoangDao && !isSelected && (
                    <span
                      className="text-[9px] font-label-caps font-medium text-amber-700/80 uppercase tracking-tighter hidden sm:inline"
                      title="Ngày Hoàng Đạo"
                    >
                      H.Đạo
                    </span>
                  )}
                  {isToday && (
                    <span
                      className={`text-[9px] font-label-caps font-semibold uppercase px-1 rounded ${
                        isSelected
                          ? 'bg-on-primary/20 text-on-primary'
                          : 'bg-primary/10 text-primary'
                      }`}
                    >
                      Nay
                    </span>
                  )}
                </div>
              </button>
            );
          })}

          {/* Trailing empty cells to pad to exact 6 rows (42 cells), keeping frame height 100% constant */}
          {Array.from({ length: trailingEmptyCells }).map((_, idx) => (
            <div
              key={`trailing-empty-${idx}`}
              className="min-h-[50px] sm:min-h-[64px] lg:min-h-[58px] xl:min-h-[62px] rounded-xl sm:rounded-2xl border border-transparent pointer-events-none"
              aria-hidden="true"
            />
          ))}
        </div>
      )}

      {/* Legend & Guide footer */}
      <div className="mt-3 pt-2.5 border-t border-outline-variant/30 flex flex-wrap items-center justify-between gap-2.5 text-xs text-outline">
        <div className="flex items-center gap-4 flex-wrap">
          <div className="flex items-center gap-1.5">
            <span className="w-2 h-2 rounded-full bg-amber-500"></span>
            <span>Lễ &amp; Vía Phật</span>
          </div>
          <div className="flex items-center gap-1.5">
            <span className="w-2 h-2 rounded-full bg-emerald-600"></span>
            <span>Ngày Ăn Chay</span>
          </div>
          <div className="flex items-center gap-1.5">
            <span className="w-2 h-2 rounded-full bg-primary/70"></span>
            <span>Phật Sự Khác</span>
          </div>
        </div>

        <div className="text-[11px] text-outline/80">
          * Âm lịch theo giờ chuẩn Việt Nam (UTC+07:00)
        </div>
      </div>
    </div>
  );
}
