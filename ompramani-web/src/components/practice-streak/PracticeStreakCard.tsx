"use client";

import React, { useEffect, useState } from "react";
import Link from "next/link";
import { useAuth } from "@/context/AuthContext";
import { PracticeStreakData } from "@/types/practice-streak";
import { getPracticeStreak } from "@/lib/api/practice-streak";

export function PracticeStreakCard() {
  const { isAuthenticated, isLoading: isAuthLoading } = useAuth();
  const [streakData, setStreakData] = useState<PracticeStreakData | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [hasError, setHasError] = useState<boolean>(false);

  useEffect(() => {
    let isMounted = true;
    if (isAuthenticated) {
      setIsLoading(true);
      setHasError(false);
      getPracticeStreak(7)
        .then((data) => {
          if (isMounted) {
            setStreakData(data);
          }
        })
        .catch(() => {
          if (isMounted) {
            setHasError(true);
          }
        })
        .finally(() => {
          if (isMounted) {
            setIsLoading(false);
          }
        });
    } else {
      setStreakData(null);
    }

    return () => {
      isMounted = false;
    };
  }, [isAuthenticated]);

  // Guest State
  if (!isAuthLoading && !isAuthenticated) {
    return (
      <div className="bg-surface-container-low hover:bg-surface-container rounded-3xl p-6 border border-outline-variant/30 transition-all shadow-sm flex flex-col justify-between">
        <div className="flex items-start gap-4">
          <div className="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
            <span className="material-symbols-outlined text-[26px]">
              self_improvement
            </span>
          </div>
          <div>
            <span className="font-label-caps text-[11px] text-primary uppercase tracking-wider font-semibold">
              Chuỗi Ngày Tinh Tấn
            </span>
            <h3 className="font-headline-md text-base sm:text-lg font-semibold text-on-surface mt-0.5">
              Nuôi dưỡng chánh niệm đều đặn
            </h3>
            <p className="font-body-sm text-xs sm:text-sm text-on-surface-variant mt-1.5 leading-relaxed">
              Đăng nhập để tự động ghi nhận chuỗi ngày hành trì, tọa thiền và trì niệm của bạn.
            </p>
          </div>
        </div>

        <div className="mt-5 pt-4 border-t border-outline-variant/30 flex items-center justify-between">
          <span className="text-xs text-outline font-label-md">
            Ghi nhận an lạc mỗi ngày
          </span>
          <Link
            href="/dang-nhap?next=/tai-khoan/hanh-trinh"
            className="px-4 py-2 rounded-xl bg-primary text-on-primary hover:bg-primary/90 font-label-md text-xs font-semibold transition-all inline-flex items-center gap-1.5 shadow-xs"
          >
            <span>Đăng nhập</span>
            <span className="material-symbols-outlined text-[16px]">arrow_forward</span>
          </Link>
        </div>
      </div>
    );
  }

  // Loading State
  if (isAuthLoading || isLoading) {
    return (
      <div className="bg-surface-container-low rounded-3xl p-6 border border-outline-variant/30 animate-pulse space-y-4">
        <div className="flex items-center gap-3">
          <div className="w-12 h-12 rounded-2xl bg-surface-container-high" />
          <div className="space-y-2 flex-1">
            <div className="h-3 w-28 bg-surface-container-high rounded" />
            <div className="h-5 w-44 bg-surface-container-high rounded" />
          </div>
        </div>
        <div className="h-10 bg-surface-container-high rounded-xl" />
      </div>
    );
  }

  // Error State
  if (hasError) {
    return (
      <div className="bg-surface-container-low rounded-3xl p-5 border border-outline-variant/30 text-center space-y-2">
        <span className="material-symbols-outlined text-outline text-[24px]">
          info
        </span>
        <p className="font-body-sm text-xs text-on-surface-variant">
          Chưa thể tải chuỗi ngày tinh tấn lúc này.
        </p>
        <Link
          href="/tai-khoan/hanh-trinh"
          className="text-xs font-label-md text-primary hover:underline inline-block"
        >
          Đến trang Hành Trình
        </Link>
      </div>
    );
  }

  const streak = streakData?.current_streak_days ?? 0;
  const status = streakData?.status ?? "inactive";
  const calendar = streakData?.calendar_days ?? [];

  return (
    <div className="bg-surface-container-low hover:bg-surface-container/80 rounded-3xl p-6 border border-outline-variant/30 transition-all shadow-sm flex flex-col justify-between">
      <div>
        {/* Header */}
        <div className="flex items-start justify-between gap-3">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 shadow-xs">
              <span className="material-symbols-outlined text-[28px]">
                local_fire_department
              </span>
            </div>
            <div>
              <div className="flex items-center gap-1.5">
                <span className="font-label-caps text-[10px] text-primary uppercase tracking-wider font-semibold">
                  Chuỗi Tinh Tấn
                </span>
                <span className="text-[10px] text-outline font-label-md">
                  · {streakData?.timezone || "Asia/Ho_Chi_Minh"}
                </span>
              </div>
              <div className="flex items-baseline gap-2 mt-0.5">
                <span className="font-display-lg text-2xl sm:text-3xl font-bold text-on-surface">
                  {streak}
                </span>
                <span className="font-label-md text-xs sm:text-sm text-on-surface-variant">
                  ngày liên tiếp
                </span>
              </div>
            </div>
          </div>

          <Link
            href="/tai-khoan/hanh-trinh"
            className="w-8 h-8 rounded-full hover:bg-surface-container flex items-center justify-center text-outline hover:text-primary transition-colors"
            title="Xem chi tiết hành trình"
          >
            <span className="material-symbols-outlined text-[20px]">
              arrow_forward
            </span>
          </Link>
        </div>

        {/* Status notice */}
        <div className="mt-3.5">
          {status === "active_today" && (
            <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 text-xs font-label-md">
              <span className="material-symbols-outlined text-[16px]">check_circle</span>
              <span>Đã hoàn thành thời khóa hôm nay</span>
            </div>
          )}
          {status === "awaiting_today" && (
            <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/10 text-amber-800 dark:text-amber-300 text-xs font-label-md">
              <span className="material-symbols-outlined text-[16px]">schedule</span>
              <span>Hôm nay chưa hành trì · Tiếp tục duy trì chuỗi</span>
            </div>
          )}
          {status === "inactive" && (
            <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-surface-container text-on-surface-variant text-xs font-label-md">
              <span className="material-symbols-outlined text-[16px]">spa</span>
              <span>Hôm nay là cơ hội tốt để bắt đầu lại</span>
            </div>
          )}
        </div>

        {/* 7-Day Mini Calendar Strip */}
        {calendar.length > 0 && (
          <div className="mt-4 pt-3.5 border-t border-outline-variant/30">
            <div className="grid grid-cols-7 gap-1 text-center">
              {calendar.map((day) => {
                const dayLabels = ["T2", "T3", "T4", "T5", "T6", "T7", "CN"];
                const dayLabel = dayLabels[day.day_of_week - 1] || "";

                return (
                  <div
                    key={day.date}
                    className="flex flex-col items-center gap-1"
                    title={`${day.date} (${day.label}): ${day.qualified ? `${day.completed_sessions_count} thời khóa` : "Chưa có thời khóa"}`}
                  >
                    <span className="text-[10px] font-label-md text-outline">
                      {dayLabel}
                    </span>
                    <div
                      className={`w-7 h-7 sm:w-8 sm:h-8 rounded-xl flex items-center justify-center text-xs font-semibold transition-all ${
                        day.qualified
                          ? "bg-amber-500 text-white shadow-xs"
                          : "bg-surface-container-high text-on-surface-variant/60"
                      } ${day.is_today ? "ring-2 ring-primary ring-offset-1 ring-offset-surface" : ""}`}
                    >
                      {day.qualified ? (
                        <span className="material-symbols-outlined text-[15px]">done</span>
                      ) : (
                        <span className="text-[10px] text-outline">{day.label.split("/")[0]}</span>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        )}
      </div>

      {/* Footer links */}
      <div className="mt-4 pt-3 border-t border-outline-variant/30 flex items-center justify-between text-xs font-label-md">
        <span className="text-on-surface-variant">
          Kỷ lục: <strong className="text-on-surface">{streakData?.longest_streak_days ?? 0} ngày</strong>
        </span>
        <Link
          href="/tai-khoan/hanh-trinh"
          className="text-primary hover:underline font-semibold inline-flex items-center gap-0.5"
        >
          <span>Chi tiết</span>
          <span className="material-symbols-outlined text-[14px]">chevron_right</span>
        </Link>
      </div>
    </div>
  );
}
