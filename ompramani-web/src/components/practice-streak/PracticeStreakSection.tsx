"use client";

import React, { useEffect, useState, useCallback } from "react";
import Link from "next/link";
import { PracticeStreakData } from "@/types/practice-streak";
import { getPracticeStreak } from "@/lib/api/practice-streak";

interface PracticeStreakSectionProps {
  initialDays?: 7 | 30 | 90;
}

export function PracticeStreakSection({ initialDays = 30 }: PracticeStreakSectionProps) {
  const [days, setDays] = useState<7 | 30 | 90>(initialDays);
  const [streakData, setStreakData] = useState<PracticeStreakData | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [hasError, setHasError] = useState<boolean>(false);

  const loadStreak = useCallback(async (selectedDays: 7 | 30 | 90) => {
    setIsLoading(true);
    setHasError(false);
    try {
      const data = await getPracticeStreak(selectedDays);
      setStreakData(data);
    } catch {
      setHasError(true);
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    loadStreak(days);
  }, [days, loadStreak]);

  if (isLoading && !streakData) {
    return (
      <div className="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-outline-variant/30 animate-pulse space-y-6">
        <div className="h-6 w-48 bg-surface-container-high rounded" />
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="h-24 bg-surface-container-high rounded-2xl" />
          ))}
        </div>
        <div className="h-44 bg-surface-container-high rounded-2xl" />
      </div>
    );
  }

  if (hasError) {
    return (
      <div className="bg-surface-container-lowest rounded-3xl p-6 border border-outline-variant/30 text-center space-y-3">
        <span className="material-symbols-outlined text-outline text-[32px]">
          info
        </span>
        <p className="text-sm font-label-md text-on-surface-variant">
          Chưa thể tải dữ liệu chuỗi ngày tinh tấn lúc này.
        </p>
        <button
          type="button"
          onClick={() => loadStreak(days)}
          className="px-4 py-2 rounded-xl bg-primary text-on-primary text-xs font-semibold"
        >
          Thử lại
        </button>
      </div>
    );
  }

  if (!streakData) return null;

  const {
    timezone,
    current_streak_days,
    longest_streak_days,
    total_qualifying_days,
    last_qualified_date,
    status,
    calendar_days,
  } = streakData;

  return (
    <section className="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-outline-variant/30 shadow-xs space-y-6">
      {/* 1. Header with Title & Window Selector */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-outline-variant/30">
        <div>
          <div className="flex items-center gap-2">
            <span className="w-2.5 h-2.5 rounded-full bg-amber-500" />
            <h2 className="font-display-lg text-lg sm:text-xl font-bold text-on-surface">
              Chuỗi Ngày Tinh Tấn
            </h2>
          </div>
          <p className="font-body-sm text-xs sm:text-sm text-on-surface-variant mt-1">
            Ghi nhận sự đều đặn trên bước đường tu học · Múi giờ:{" "}
            <span className="font-semibold text-on-surface">{timezone}</span>
          </p>
        </div>

        {/* Days Window Selector */}
        <div className="inline-flex items-center p-1 rounded-xl bg-surface-container-low border border-outline-variant/30 self-start sm:self-auto">
          {([7, 30, 90] as const).map((d) => (
            <button
              key={d}
              type="button"
              onClick={() => setDays(d)}
              className={`px-3 py-1.5 rounded-lg text-xs font-label-md font-semibold transition-all ${
                days === d
                  ? "bg-surface-container-lowest text-primary shadow-xs"
                  : "text-outline hover:text-on-surface"
              }`}
            >
              {d} ngày
            </button>
          ))}
        </div>
      </div>

      {/* 2. Stat Metric Badges */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        {/* Current Streak */}
        <div className="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center gap-3.5">
          <div className="w-11 h-11 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs">
            <span className="material-symbols-outlined text-[24px]">
              local_fire_department
            </span>
          </div>
          <div>
            <div className="text-[11px] font-label-md text-amber-900 dark:text-amber-200 uppercase tracking-wider">
              Chuỗi hiện tại
            </div>
            <div className="flex items-baseline gap-1.5 mt-0.5">
              <span className="font-display-lg text-2xl font-bold text-amber-950 dark:text-amber-100">
                {current_streak_days}
              </span>
              <span className="text-xs font-label-md text-amber-800/80 dark:text-amber-300">ngày</span>
            </div>
          </div>
        </div>

        {/* Longest Streak */}
        <div className="p-4 rounded-2xl bg-primary/10 border border-primary/20 flex items-center gap-3.5">
          <div className="w-11 h-11 rounded-xl bg-primary text-on-primary flex items-center justify-center shrink-0 shadow-xs">
            <span className="material-symbols-outlined text-[24px]">
              emoji_events
            </span>
          </div>
          <div>
            <div className="text-[11px] font-label-md text-primary uppercase tracking-wider">
              Kỷ lục dài nhất
            </div>
            <div className="flex items-baseline gap-1.5 mt-0.5">
              <span className="font-display-lg text-2xl font-bold text-on-surface">
                {longest_streak_days}
              </span>
              <span className="text-xs font-label-md text-on-surface-variant">ngày</span>
            </div>
          </div>
        </div>

        {/* Total Qualifying Days */}
        <div className="p-4 rounded-2xl bg-surface-container-low border border-outline-variant/30 flex items-center gap-3.5">
          <div className="w-11 h-11 rounded-xl bg-secondary-container text-on-secondary-container flex items-center justify-center shrink-0">
            <span className="material-symbols-outlined text-[24px]">
              calendar_month
            </span>
          </div>
          <div>
            <div className="text-[11px] font-label-md text-outline uppercase tracking-wider">
              Tổng ngày tinh tấn
            </div>
            <div className="flex items-baseline gap-1.5 mt-0.5">
              <span className="font-display-lg text-2xl font-bold text-on-surface">
                {total_qualifying_days}
              </span>
              <span className="text-xs font-label-md text-on-surface-variant">ngày</span>
            </div>
          </div>
        </div>

        {/* Last Qualified Date */}
        <div className="p-4 rounded-2xl bg-surface-container-low border border-outline-variant/30 flex items-center gap-3.5">
          <div className="w-11 h-11 rounded-xl bg-tertiary-container text-on-tertiary-container flex items-center justify-center shrink-0">
            <span className="material-symbols-outlined text-[24px]">
              history
            </span>
          </div>
          <div>
            <div className="text-[11px] font-label-md text-outline uppercase tracking-wider">
              Ngày gần nhất
            </div>
            <div className="font-headline-md text-base sm:text-lg font-semibold text-on-surface mt-0.5">
              {last_qualified_date || "Chưa có"}
            </div>
          </div>
        </div>
      </div>

      {/* 3. Mindful Today Status Notice */}
      <div>
        {status === "active_today" && (
          <div className="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div className="flex items-center gap-2.5">
              <span className="material-symbols-outlined text-emerald-600 text-[22px]">
                check_circle
              </span>
              <span className="text-xs sm:text-sm font-label-md text-emerald-900 dark:text-emerald-200">
                Hôm nay bạn đã có thời khóa tu tập được hệ thống ghi nhận. Chuỗi tinh tấn đang được duy trì liên tục!
              </span>
            </div>
          </div>
        )}

        {status === "awaiting_today" && (
          <div className="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div className="flex items-center gap-2.5">
              <span className="material-symbols-outlined text-amber-600 text-[22px]">
                schedule
              </span>
              <span className="text-xs sm:text-sm font-label-md text-amber-900 dark:text-amber-200">
                Hôm nay bạn chưa có thời khóa được ghi nhận. Hãy dành ít phút tịnh tâm để tiếp tục chuỗi ngày tinh tấn.
              </span>
            </div>
            <Link
              href="/thien"
              className="px-3.5 py-1.5 rounded-xl bg-amber-600 text-white hover:bg-amber-700 text-xs font-semibold shadow-xs shrink-0 inline-flex items-center gap-1.5"
            >
              <span>Tọa thiền ngay</span>
              <span className="material-symbols-outlined text-[16px]">arrow_forward</span>
            </Link>
          </div>
        )}

        {status === "inactive" && (
          <div className="p-4 rounded-2xl bg-surface-container-low border border-outline-variant/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div className="flex items-center gap-2.5">
              <span className="material-symbols-outlined text-primary text-[22px]">
                spa
              </span>
              <span className="text-xs sm:text-sm font-label-md text-on-surface">
                Chưa có chuỗi ngày đang duy trì. Hôm nay là một cơ hội thanh tịnh để bắt đầu lại bước đường tu học.
              </span>
            </div>
            <Link
              href="/thien"
              className="px-3.5 py-1.5 rounded-xl bg-primary text-on-primary hover:bg-primary/90 text-xs font-semibold shadow-xs shrink-0 inline-flex items-center gap-1.5"
            >
              <span>Bắt đầu thời khóa</span>
              <span className="material-symbols-outlined text-[16px]">arrow_forward</span>
            </Link>
          </div>
        )}
      </div>

      {/* 4. Activity Calendar View */}
      <div className="space-y-3">
        <div className="flex items-center justify-between">
          <h3 className="font-label-caps text-xs text-outline uppercase tracking-wider font-semibold">
            Lịch Hoạt Động {days} Ngày Gần Nhất
          </h3>
          <div className="flex items-center gap-4 text-[11px] font-label-md text-on-surface-variant">
            <div className="flex items-center gap-1.5">
              <span className="w-3 h-3 rounded-md bg-amber-500 shadow-xs" />
              <span>Có hành trì</span>
            </div>
            <div className="flex items-center gap-1.5">
              <span className="w-3 h-3 rounded-md bg-surface-container-high" />
              <span>Chưa có</span>
            </div>
            <div className="flex items-center gap-1.5">
              <span className="w-3 h-3 rounded-md border-2 border-primary" />
              <span>Hôm nay</span>
            </div>
          </div>
        </div>

        {/* Calendar Grid */}
        <div className="p-4 rounded-2xl bg-surface-container-low border border-outline-variant/30 overflow-x-auto">
          {days === 7 ? (
            /* 7 Days Row */
            <div className="grid grid-cols-7 gap-2 min-w-[320px]">
              {calendar_days.map((day) => {
                const dayLabels = ["T2", "T3", "T4", "T5", "T6", "T7", "CN"];
                const dayLabel = dayLabels[day.day_of_week - 1] || "";

                return (
                  <div
                    key={day.date}
                    className="flex flex-col items-center gap-1.5 p-2 rounded-xl text-center transition-all"
                  >
                    <span className="text-xs font-label-md text-outline font-medium">
                      {dayLabel}
                    </span>
                    <div
                      className={`w-10 h-10 sm:w-12 sm:h-12 rounded-2xl flex flex-col items-center justify-center transition-all ${
                        day.qualified
                          ? "bg-amber-500 text-white shadow-xs"
                          : "bg-surface-container-high text-on-surface-variant"
                      } ${day.is_today ? "ring-2 ring-primary ring-offset-2 ring-offset-surface" : ""}`}
                      title={`${day.date}: ${day.qualified ? `${day.completed_sessions_count} thời khóa` : "Chưa có thời khóa"}`}
                    >
                      <span className="text-xs font-semibold">{day.label.split("/")[0]}</span>
                      {day.qualified && (
                        <span className="text-[10px] opacity-90">{day.completed_sessions_count} phiên</span>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          ) : (
            /* 30 or 90 Days Grid */
            <div className="grid grid-cols-7 sm:grid-cols-10 md:grid-cols-15 gap-2 min-w-[320px]">
              {calendar_days.map((day) => (
                <div
                  key={day.date}
                  className={`aspect-square rounded-xl p-1 flex flex-col items-center justify-center text-center transition-all cursor-default ${
                    day.qualified
                      ? "bg-amber-500 text-white shadow-xs"
                      : "bg-surface-container-high text-on-surface-variant"
                  } ${day.is_today ? "ring-2 ring-primary ring-offset-1 ring-offset-surface" : ""}`}
                  title={`${day.date} (${day.label}): ${day.qualified ? `${day.completed_sessions_count} thời khóa hoàn thành` : "Chưa có thời khóa"}`}
                >
                  <span className="text-[11px] font-semibold leading-none">
                    {day.label.split("/")[0]}
                  </span>
                  <span className="text-[9px] opacity-75 leading-none mt-0.5">
                    {day.label.split("/")[1]}
                  </span>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </section>
  );
}
