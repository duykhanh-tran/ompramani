"use client";

import React from "react";
import Link from "next/link";
import { formatTimer } from "@/lib/practice/instruments";

interface InstrumentCompletionModalProps {
  isOpen: boolean;
  onClose: () => void;
  bellCount: number;
  woodenFishCount: number;
  durationSeconds: number;
  completedAt?: string;
  onContinue: () => void;
}

export function InstrumentCompletionModal({
  isOpen,
  onClose,
  bellCount,
  woodenFishCount,
  durationSeconds,
  completedAt,
  onContinue,
}: InstrumentCompletionModalProps) {
  if (!isOpen) return null;

  const dateFormatted = completedAt
    ? new Date(completedAt).toLocaleTimeString("vi-VN", {
        hour: "2-digit",
        minute: "2-digit",
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
      })
    : new Date().toLocaleTimeString("vi-VN", {
        hour: "2-digit",
        minute: "2-digit",
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
      });

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs animate-in fade-in duration-200">
      <div
        className="w-full max-w-sm rounded-3xl bg-surface-container-lowest border border-outline-variant/30 shadow-2xl p-6 flex flex-col items-center text-center animate-in zoom-in-95 duration-200 relative"
        role="dialog"
        aria-modal="true"
      >
        {/* Close button */}
        <button
          type="button"
          onClick={onClose}
          className="absolute top-4 right-4 w-8 h-8 rounded-full flex items-center justify-center text-outline hover:text-on-surface hover:bg-surface-container-high transition-colors"
          title="Đóng"
        >
          <span className="material-symbols-outlined text-[18px]">close</span>
        </button>

        {/* Sacred Icon Lotus / Bell */}
        <div className="w-16 h-16 rounded-full bg-primary/10 text-primary flex items-center justify-center mb-4">
          <span className="material-symbols-outlined text-[36px]">
            notifications_active
          </span>
        </div>

        <span className="font-label-caps text-[11px] text-primary uppercase tracking-widest font-semibold">
          Công đức viên mãn
        </span>
        <h2 className="font-serif text-xl sm:text-2xl font-bold text-on-surface mt-1">
          Thời Khóa Hoàn Tất
        </h2>
        <p className="text-xs font-body-sm text-outline mt-1">
          Thời khóa Chuông & Mõ đã được ghi nhận vào nhật ký tu tập của bạn.
        </p>

        {/* Stats Grid */}
        <div className="w-full grid grid-cols-3 gap-2 my-5 p-3 rounded-2xl bg-surface-container-low border border-outline-variant/20">
          <div className="flex flex-col items-center">
            <span className="text-[10px] font-label-caps uppercase text-outline">
              Chuông
            </span>
            <span className="text-lg font-serif font-bold text-primary mt-0.5">
              {bellCount}
            </span>
          </div>

          <div className="flex flex-col items-center border-x border-outline-variant/30">
            <span className="text-[10px] font-label-caps uppercase text-outline">
              Mõ
            </span>
            <span className="text-lg font-serif font-bold text-amber-700 mt-0.5">
              {woodenFishCount}
            </span>
          </div>

          <div className="flex flex-col items-center">
            <span className="text-[10px] font-label-caps uppercase text-outline">
              Thời gian
            </span>
            <span className="text-lg font-mono font-bold text-on-surface mt-0.5">
              {formatTimer(durationSeconds)}
            </span>
          </div>
        </div>

        <div className="text-[11px] font-mono text-outline mb-6">
          {dateFormatted}
        </div>

        {/* Buttons */}
        <div className="w-full flex flex-col gap-2">
          <button
            type="button"
            onClick={onContinue}
            className="w-full py-2.5 px-4 rounded-xl bg-primary text-on-primary font-label-md text-xs font-semibold shadow-xs hover:bg-primary-hover transition-colors"
          >
            Thực hành tiếp
          </button>

          <Link
            href="/tai-khoan"
            className="w-full py-2.5 px-4 rounded-xl bg-surface-container-high text-on-surface font-label-md text-xs font-semibold hover:bg-surface-container-highest transition-colors text-center"
          >
            Xem lịch sử tu tập
          </Link>

          <Link
            href="/"
            className="w-full py-2 px-4 rounded-xl text-outline font-label-md text-xs hover:text-on-surface transition-colors text-center"
          >
            Về trang chủ
          </Link>
        </div>
      </div>
    </div>
  );
}
