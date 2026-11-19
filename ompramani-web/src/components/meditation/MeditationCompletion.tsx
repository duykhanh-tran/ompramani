"use client";

import React from "react";
import Link from "next/link";
import { AuthUser } from "@/types/auth";

interface MeditationCompletionProps {
  durationSeconds: number;
  plannedMinutes: number;
  user: AuthUser | null;
  onNewSession: () => void;
}

export function MeditationCompletion({
  durationSeconds,
  plannedMinutes,
  user,
  onNewSession,
}: MeditationCompletionProps) {
  const actualMinutes = Math.floor(durationSeconds / 60);
  const actualSeconds = durationSeconds % 60;

  return (
    <div className="max-w-xl mx-auto px-4 py-10 sm:py-16 text-center animate-in fade-in zoom-in-95 duration-200">
      {/* Golden Lotus Blossom Badge */}
      <div className="w-20 h-20 sm:w-24 sm:h-24 mx-auto rounded-full bg-gradient-to-br from-secondary-container via-primary-container to-primary text-on-primary flex items-center justify-center shadow-lg ring-8 ring-secondary-container/40 mb-6">
        <span className="material-symbols-outlined text-[40px] sm:text-[48px] text-surface">
          spa
        </span>
      </div>

      <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-secondary-container/60 text-primary font-label-caps text-xs tracking-wider uppercase mb-2 border border-secondary-container/40">
        <span className="material-symbols-outlined text-[16px]">
          verified
        </span>
        <span>Công đức viên mãn</span>
      </div>

      <h1 className="font-display-lg text-2xl sm:text-3xl font-semibold text-on-surface mb-2">
        Tùy Hỷ Công Đức
      </h1>
      <p className="font-body-md text-sm sm:text-base text-on-surface-variant max-w-md mx-auto mb-8">
        Bạn vừa hoàn tất một thời thiền định an lạc. Nguyện tâm bạn luôn giữ được sự an tịnh và sáng suốt này.
      </p>

      {/* Practice Summary Card */}
      <div className="bg-surface-container-lowest rounded-3xl p-6 sm:p-7 shadow-sm border border-outline-variant/40 text-left mb-8 space-y-4">
        <div className="flex items-center justify-between border-b border-outline-variant/30 pb-3">
          <span className="font-label-md text-xs text-outline uppercase tracking-wider">
            Thời lượng tĩnh tọa
          </span>
          <span className="font-headline-md text-base sm:text-lg font-semibold text-primary">
            {actualMinutes > 0 ? `${actualMinutes} phút ` : ""}
            {actualSeconds > 0 || actualMinutes === 0 ? `${actualSeconds} giây` : ""}
            <span className="text-xs font-normal text-outline ml-1.5">
              (Dự kiến: {plannedMinutes} phút)
            </span>
          </span>
        </div>

        <div className="flex items-center justify-between border-b border-outline-variant/30 pb-3">
          <span className="font-label-md text-xs text-outline uppercase tracking-wider">
            Pháp môn thực hành
          </span>
          <span className="font-label-lg text-sm font-medium text-on-surface">
            Quán niệm hơi thở (Anapanasati)
          </span>
        </div>

        <div className="flex items-center justify-between border-b border-outline-variant/30 pb-3">
          <span className="font-label-md text-xs text-outline uppercase tracking-wider">
            Thời điểm hoàn mãn
          </span>
          <span className="font-label-lg text-sm text-on-surface">
            {new Date().toLocaleTimeString("vi-VN", {
              hour: "2-digit",
              minute: "2-digit",
            })}{" "}
            ·{" "}
            {new Date().toLocaleDateString("vi-VN", {
              day: "numeric",
              month: "numeric",
              year: "numeric",
            })}
          </span>
        </div>

        {/* User Account / Guest Status */}
        {user ? (
          <div className="flex items-center gap-2.5 p-3 rounded-2xl bg-secondary-container/40 border border-secondary-container text-primary font-label-md text-xs">
            <span className="material-symbols-outlined text-[18px]">
              cloud_done
            </span>
            <span>
              Đã ghi nhận vào lịch sử tu tập của đạo hữu <strong>{user.name}</strong>.
            </span>
          </div>
        ) : (
          <div className="p-3.5 rounded-2xl bg-surface-container border border-outline-variant/40 space-y-2">
            <div className="flex items-start gap-2 text-on-surface font-label-md text-xs">
              <span className="material-symbols-outlined text-[18px] text-primary shrink-0 mt-0.5">
                info
              </span>
              <span>
                Bạn đang thiền ở chế độ <strong>Khách</strong>. Hãy đăng nhập để lưu chuỗi ngày tu tập và tiến trình cá nhân.
              </span>
            </div>
            <Link
              href="/dang-nhap?next=/thien"
              className="inline-flex items-center gap-1 font-label-md text-xs text-primary font-semibold hover:underline"
            >
              <span>Đăng nhập ngay</span>
              <span className="material-symbols-outlined text-[14px]">
                arrow_forward
              </span>
            </Link>
          </div>
        )}
      </div>

      {/* Dedication Verse */}
      <div className="bg-surface-container-low/60 rounded-2xl p-5 border border-outline-variant/30 mb-8 max-w-md mx-auto">
        <p className="font-quote-editorial text-xs sm:text-sm italic text-on-surface-variant leading-relaxed">
          &ldquo;Nguyện đem công đức này,<br />
          Hướng về khắp tất cả,<br />
          Đệ tử và chúng sinh,<br />
          Đều trọn thành Phật đạo.&rdquo;
        </p>
      </div>

      {/* Navigation Actions */}
      <div className="flex flex-col sm:flex-row items-center justify-center gap-3">
        <button
          type="button"
          suppressHydrationWarning
          onClick={onNewSession}
          className="w-full sm:w-auto px-6 py-3 rounded-full bg-primary hover:bg-primary-hover text-on-primary font-label-lg text-sm font-semibold transition-all shadow-sm cursor-pointer"
        >
          Tọa thiền thời mới
        </button>

        {user && (
          <Link
            href="/tai-khoan"
            className="w-full sm:w-auto px-5 py-3 rounded-full bg-surface-container-low hover:bg-surface-container border border-outline-variant/60 text-on-surface font-label-lg text-sm transition-colors"
          >
            Xem lịch sử tu tập
          </Link>
        )}

        <Link
          href="/"
          className="w-full sm:w-auto px-5 py-3 rounded-full hover:bg-surface-container-low text-on-surface-variant font-label-lg text-sm transition-colors"
        >
          Về trang chủ
        </Link>
      </div>
    </div>
  );
}
