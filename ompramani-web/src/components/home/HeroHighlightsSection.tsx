"use client";

import React, { useState, useEffect } from "react";
import Link from "next/link";
import Image from "next/image";
import { DEMO_HERO_VERSE } from "@/data/demo/audioPractice";
import { getCalendarWeek } from "@/lib/api/calendar";
import { CalendarWeekDay } from "@/types/calendar";
import { PracticeStreakCard } from "@/components/practice-streak/PracticeStreakCard";

export function HeroHighlightsSection() {
  const [calendarInfo, setCalendarInfo] = useState<{
    solarDate: string;
    yearName: string;
    lunarDate: string;
  }>({
    solarDate: DEMO_HERO_VERSE.solarDate,
    yearName: DEMO_HERO_VERSE.yearName,
    lunarDate: DEMO_HERO_VERSE.lunarDate,
  });

  useEffect(() => {
    let isMounted = true;
    getCalendarWeek()
      .then((data) => {
        if (!isMounted || !data?.days) return;
        const today = data.days.find((d: CalendarWeekDay) => d.is_today) || data.days[0];
        if (today) {
          setCalendarInfo({
            solarDate: `${today.day_of_week} · ${today.solar_day}`,
            yearName: `Năm ${today.year_can_chi}`,
            lunarDate: `Âm Lịch ${today.lunar_display}`,
          });
        }
      })
      .catch((err) => {
        console.warn("Could not load dynamic date for hero banner:", err);
      });

    return () => {
      isMounted = false;
    };
  }, []);

  return (
    <section className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
      {/* Left Hero Banner: Mountain Dawn Meditation (8 cols) */}
      <div className="lg:col-span-8 relative rounded-3xl overflow-hidden shadow-md flex flex-col justify-between min-h-[420px] p-6 sm:p-8 text-on-primary group">
        <Image
          src="/images/hero-meditation.jpg"
          alt={DEMO_HERO_VERSE.backgroundImageAlt}
          fill
          priority
          sizes="(max-width: 1024px) 100vw, 66vw"
          className="object-cover object-center group-hover:scale-105 transition-transform duration-700"
        />
        {/* Soft vignette gradient: dark on left for text legibility, transparent on right for golden Buddha */}
        <div className="absolute inset-0 bg-gradient-to-r from-black/85 via-black/45 via-50% to-transparent pointer-events-none" />

        {/* Top banner status */}
        <div className="relative z-10 flex flex-wrap items-center justify-between gap-2">
          <div className="bg-black/35 backdrop-blur-md border border-white/15 px-3.5 py-1.5 rounded-full flex items-center gap-2 shadow-xs">
            <span className="material-symbols-outlined text-[16px] text-secondary-fixed">
              wb_twilight
            </span>
            <span className="font-label-md text-label-md text-surface tracking-wider">
              {calendarInfo.solarDate} · {calendarInfo.yearName}
            </span>
          </div>
          <span className="bg-primary/90 backdrop-blur-md border border-primary/20 px-3.5 py-1 rounded-full font-label-caps text-surface shadow-xs">
            {calendarInfo.lunarDate}
          </span>
        </div>

        {/* Center: Mindful Practice Focus */}
        <div className="relative z-10 my-6 max-w-xl">
          <h2 className="font-display-lg text-[28px] sm:text-[34px] font-bold leading-tight text-surface drop-shadow-md mb-3 font-serif">
            Hơi thở an lạc,<br />Tâm thanh tịnh giữa đời thường
          </h2>
          <p className="font-body-md text-surface/90 text-sm sm:text-base leading-relaxed drop-shadow-xs max-w-lg">
            Mỗi phút giây hiện tại là một cơ duyên tỉnh thức. Hãy để thân tâm lắng đọng và bắt đầu thời khóa tu tập hôm nay.
          </p>
        </div>

        {/* Bottom interactive trigger */}
        <div className="relative z-10 flex flex-wrap items-center gap-4">
          <Link
            href="/thien?duration=15"
            className="px-5 py-2.5 rounded-full bg-secondary-container text-on-secondary-container hover:bg-primary hover:text-on-primary transition-all font-label-lg text-label-lg font-semibold flex items-center gap-2 shadow-sm cursor-pointer"
          >
            <span className="material-symbols-outlined text-[20px]">
              self_improvement
            </span>
            <span>Khởi tâm tọa thiền 15 phút</span>
          </Link>
          <span className="font-body-sm text-body-sm text-surface/90 flex items-center gap-1.5 drop-shadow-xs">
            <span className="material-symbols-outlined text-[16px] text-secondary-fixed">
              spa
            </span>
            <span>Không gian tĩnh lặng · Khởi tâm chánh niệm</span>
          </span>
        </div>
      </div>

      {/* Right Column: Chanting & Practice Streak (4 cols) */}
      <div className="lg:col-span-4 flex flex-col gap-6">
        {/* Sacred Chanting & Recitation Practice Card (Task 22) */}
        <div className="bg-surface-container-high rounded-3xl p-6 relative overflow-hidden shadow-sm flex flex-col justify-between flex-1">
          <div className="absolute -right-8 -top-8 w-40 h-40 bg-secondary-container/20 rounded-full blur-2xl pointer-events-none" />
          <div>
            <div className="flex items-center justify-between mb-3">
              <span className="bg-secondary/15 text-secondary px-3 py-1 rounded-full font-label-caps text-label-caps uppercase tracking-wider">
                Thời Khóa Tịnh Tâm
              </span>
              <span className="material-symbols-outlined text-outline">
                spa
              </span>
            </div>
            <h3 className="font-headline-lg text-headline-lg text-on-surface font-semibold leading-snug">
              Trì Chú
            </h3>
            <p className="font-body-sm text-body-sm text-on-surface-variant mt-2 leading-relaxed">
              Không gian trì tụng chân ngôn và thần chú chư Phật, Bồ Tát trang nghiêm với âm thanh trợ niệm thanh tịnh.
            </p>
          </div>

          <div className="mt-4 pt-2 border-t border-outline-variant/30">
            <Link
              href="/tri-chu"
              className="w-full px-4 py-2.5 rounded-xl bg-surface-container text-on-surface hover:text-primary hover:bg-surface-container-lowest transition-colors font-label-md text-xs font-semibold flex items-center justify-center gap-2 shadow-2xs"
            >
              <span className="material-symbols-outlined text-[18px] text-primary">spa</span>
              <span>Bắt Đầu Trì Chú</span>
            </Link>
          </div>
        </div>

        {/* Practice Streak Card (Task 26) */}
        <PracticeStreakCard />
      </div>
    </section>
  );
}
