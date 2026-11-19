"use client";

import React, { useEffect, useState } from "react";
import Link from "next/link";
import { DEMO_CALENDAR_DAYS } from "@/data/demo/calendar";
import { DEMO_USER } from "@/data/demo/user";
import { useAuth } from "@/context/AuthContext";
import { getCalendarWeek } from "@/lib/api/calendar";
import { CalendarWeekData, CalendarWeekDay } from "@/types/calendar";

export function GreetingCalendarSection() {
  const { user } = useAuth();
  const [weekData, setWeekData] = useState<CalendarWeekData | null>(null);
  const [selectedDayIndex, setSelectedDayIndex] = useState<number>(() => {
    const todayIdx = DEMO_CALENDAR_DAYS.findIndex((d) => d.isToday);
    return todayIdx >= 0 ? todayIdx : 2;
  });

  useEffect(() => {
    let isMounted = true;
    getCalendarWeek()
      .then((data) => {
        if (isMounted && data?.days?.length === 7) {
          setWeekData(data);
          const todayIdx = data.days.findIndex((d: CalendarWeekDay) => d.is_today);
          if (todayIdx >= 0) {
            setSelectedDayIndex(todayIdx);
          } else if (typeof data.selected_index === "number") {
            setSelectedDayIndex(data.selected_index);
          }
        }
      })
      .catch((err) => {
        console.warn("Could not load calendar week from API:", err);
      });

    return () => {
      isMounted = false;
    };
  }, []);

  const days = weekData
    ? weekData.days.map((d) => ({
        dayOfWeek: d.day_of_week,
        solarDay: d.solar_day,
        lunarDay: d.lunar_display,
        isToday: d.is_today,
        tag: d.tag || undefined,
        tagType: d.tag_type || undefined,
        isSunday: d.is_sunday,
        dayCanChi: d.day_can_chi,
        solarTerm: d.solar_term,
        auspiciousDetail: d.auspicious_detail,
      }))
    : DEMO_CALENDAR_DAYS.map((d) => ({
        dayOfWeek: d.dayOfWeek,
        solarDay: d.solarDay,
        lunarDay: d.lunarDay,
        isToday: d.isToday,
        tag: d.tag,
        tagType: d.tagType,
        isSunday: d.isSunday,
        dayCanChi: undefined,
        solarTerm: undefined,
        auspiciousDetail: undefined,
      }));

  const activeDay = days[selectedDayIndex] || days[0];
  const displayName = user?.name || DEMO_USER.name;
  const eventTitle = activeDay?.isToday
    ? "Lịch Pháp Sự Hôm Nay"
    : `Pháp Sự ${activeDay?.dayOfWeek || "Hôm Nay"}`;
  const eventDetail =
    activeDay?.auspiciousDetail ||
    (activeDay?.dayCanChi
      ? `Ngày ${activeDay.dayCanChi} • Tiết ${activeDay.solarTerm || ""}`
      : DEMO_USER.auspiciousEventDetail);

  return (
    <section className="space-y-6">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-1">
        <div className="space-y-1.5">
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 border border-primary/20 text-primary text-[11px] font-semibold tracking-wider uppercase shadow-2xs">
            <span className="material-symbols-outlined text-[15px] text-primary">spa</span>
            <span>Chánh niệm hôm nay</span>
          </div>
          <h1 className="font-serif text-3xl sm:text-4xl lg:text-[42px] font-bold text-on-surface tracking-tight leading-tight">
            Xin chào, <span className="text-primary font-serif font-bold">{displayName}</span>!
          </h1>
          <p className="font-body-md text-sm sm:text-base text-on-surface-variant/90 leading-relaxed max-w-xl">
            {DEMO_USER.mindfulGreeting}
          </p>
        </div>

        {/* Auspicious Day Badge linking to /lich-van-nien */}
        <Link
          href="/lich-van-nien"
          className="bg-surface-container-lowest/90 border border-outline-variant/50 px-4 py-3 rounded-2xl flex items-center gap-3.5 shrink-0 hover:border-primary/50 hover:shadow-sm transition-all group cursor-pointer"
          title="Xem trọn vẹn Lịch Vạn Niên"
        >
          <div className="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 group-hover:bg-primary group-hover:text-on-primary transition-colors">
            <span className="material-symbols-outlined text-[20px]">
              calendar_month
            </span>
          </div>
          <div>
            <div className="font-label-md text-xs text-outline flex items-center gap-1 group-hover:text-primary transition-colors">
              <span>{eventTitle}</span>
              <span className="material-symbols-outlined text-[13px] group-hover:translate-x-0.5 transition-transform">
                arrow_forward
              </span>
            </div>
            <div className="font-label-lg text-sm sm:text-base font-semibold text-on-surface mt-0.5">
              {eventDetail}
            </div>
          </div>
        </Link>
      </div>

      {/* Weekly Lunar/Solar Calendar Strip */}
      <div className="bg-surface-container-lowest p-3 rounded-2xl shadow-sm overflow-x-auto">
        <div className="grid grid-cols-7 gap-2 min-w-[560px] md:min-w-0">
          {days.map((day, index: number) => {
            const isSelected = selectedDayIndex === index;

            if (isSelected) {
              return (
                <button
                  key={`${day.solarDay}-${index}`}
                  type="button"
                  suppressHydrationWarning
                  onClick={() => setSelectedDayIndex(index)}
                  className="flex flex-col items-center py-2.5 px-2 rounded-xl bg-primary text-on-primary shadow-md relative text-center transition-all cursor-pointer"
                >
                  <span className="font-label-md text-label-md text-primary-fixed uppercase tracking-wide">
                    {day.dayOfWeek}
                  </span>
                  <div className="flex items-center gap-1 mt-0.5">
                    <span className="font-headline-md text-headline-md font-bold text-on-primary">
                      {day.solarDay}
                    </span>
                    <span className="w-1.5 h-1.5 rounded-full bg-secondary-fixed"></span>
                  </div>
                  <span className="font-label-md text-label-md text-primary-fixed-dim font-medium">
                    {day.lunarDay}
                  </span>
                  {day.tag && (
                    <span className="absolute -top-1 right-2 px-1.5 py-0.2 bg-secondary text-on-secondary rounded-full font-label-caps text-[9px] tracking-tighter">
                      {day.tag}
                    </span>
                  )}
                </button>
              );
            }

            return (
              <button
                key={`${day.solarDay}-${index}`}
                type="button"
                suppressHydrationWarning
                onClick={() => setSelectedDayIndex(index)}
                className="flex flex-col items-center py-2.5 px-2 rounded-xl transition-all hover:bg-surface-container-low text-center group relative cursor-pointer"
              >
                <span
                  className={`font-label-md text-label-md ${
                    day.isSunday
                      ? "text-error/80 group-hover:text-error"
                      : "text-outline group-hover:text-on-surface"
                  }`}
                >
                  {day.dayOfWeek}
                </span>
                <div className="flex items-center gap-1 mt-0.5">
                  <span
                    className={`font-headline-md text-headline-md font-semibold ${
                      day.isSunday ? "text-error" : "text-on-surface"
                    }`}
                  >
                    {day.solarDay}
                  </span>
                  {day.tagType === "veg" && (
                    <span className="w-1.5 h-1.5 rounded-full bg-primary"></span>
                  )}
                  {day.tagType === "first_day" && (
                    <span className="w-1.5 h-1.5 rounded-full bg-tertiary"></span>
                  )}
                </div>
                <span className="font-label-md text-label-md text-outline">
                  {day.lunarDay}
                </span>
                {day.tag && (
                  <span
                    className={`text-[10px] font-medium ${
                      day.tagType === "first_day"
                        ? "text-tertiary"
                        : "text-primary"
                    }`}
                  >
                    {day.tag}
                  </span>
                )}
              </button>
            );
          })}
        </div>
      </div>
    </section>
  );
}
