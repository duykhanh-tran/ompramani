"use client";

import React from "react";
import Link from "next/link";

interface InstrumentHeaderProps {
  onOpenSettings: () => void;
  syncStatus?: "saved" | "syncing" | "unsynced" | "guest";
  isRecording?: boolean;
}

export function InstrumentHeader({
  onOpenSettings,
  syncStatus,
  isRecording,
}: InstrumentHeaderProps) {
  return (
    <header className="sticky top-0 z-30 w-full bg-surface/80 backdrop-blur-md px-4 sm:px-6 py-3 flex items-center justify-between border-b border-outline-variant/20">
      {/* Back button */}
      <Link
        href="/"
        className="w-10 h-10 rounded-full flex items-center justify-center text-on-surface hover:text-primary hover:bg-surface-container-low transition-colors"
        title="Quay lại"
      >
        <span className="material-symbols-outlined text-[24px]">chevron_left</span>
      </Link>

      {/* Center Title & Recording Indicator */}
      <div className="flex flex-col items-center">
        <h1 className="font-serif text-[18px] sm:text-[20px] font-semibold text-on-surface tracking-tight">
          Chuông mõ
        </h1>
        {isRecording && (
          <div className="flex items-center gap-1.5 mt-0.5">
            <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
            <span className="font-label-caps text-[10px] text-emerald-700 tracking-wider font-medium uppercase">
              {syncStatus === "syncing" ? "Đang đồng bộ..." : "Đang ghi nhận"}
            </span>
          </div>
        )}
      </div>

      {/* Settings Gear Button */}
      <button
        type="button"
        onClick={onOpenSettings}
        className="w-10 h-10 rounded-full flex items-center justify-center text-on-surface hover:text-primary hover:bg-surface-container-low transition-colors focus:outline-none"
        title="Cài đặt âm lượng & thời khóa"
        aria-label="Cài đặt"
      >
        <span className="material-symbols-outlined text-[22px]">settings</span>
      </button>
    </header>
  );
}
