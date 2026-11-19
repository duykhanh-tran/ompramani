"use client";

import React, { useState, useRef, useEffect } from "react";
import Image from "next/image";
import { BELL_INTERVAL_OPTIONS } from "@/lib/practice/instruments";

interface BellSectionProps {
  onStrike: () => void;
  isStriking: boolean;
  isAutoActive: boolean;
  onToggleAuto: () => void;
  intervalSeconds: number;
  onSelectInterval: (seconds: number) => void;
}

export function BellSection({
  onStrike,
  isStriking,
  isAutoActive,
  onToggleAuto,
  intervalSeconds,
  onSelectInterval,
}: BellSectionProps) {
  const [dropdownOpen, setDropdownOpen] = useState<boolean>(false);
  const dropdownRef = useRef<HTMLDivElement>(null);

  // Close dropdown when clicking outside
  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (
        dropdownRef.current &&
        !dropdownRef.current.contains(event.target as Node)
      ) {
        setDropdownOpen(false);
      }
    }
    if (dropdownOpen) {
      document.addEventListener("mousedown", handleClickOutside);
    }
    return () => {
      document.removeEventListener("mousedown", handleClickOutside);
    };
  }, [dropdownOpen]);

  const currentOption =
    BELL_INTERVAL_OPTIONS.find((opt) => opt.seconds === intervalSeconds) ||
    BELL_INTERVAL_OPTIONS[1];

  return (
    <div className="flex flex-col items-center justify-center py-2 sm:py-6 select-none w-full">
      {/* 1. Bell Instrument Tap Target */}
      <div className="relative flex items-center justify-center">
        {/* Subtle glowing resonance wave on strike */}
        <div
          className={`absolute w-44 h-40 sm:w-60 sm:h-52 md:w-72 md:h-64 lg:w-80 lg:h-72 rounded-full bg-amber-400/20 blur-xl pointer-events-none transition-opacity duration-300 ${
            isStriking ? "opacity-100 scale-110" : "opacity-0 scale-95"
          }`}
        />

        <button
          type="button"
          onClick={onStrike}
          className={`relative z-10 w-44 sm:w-60 md:w-72 lg:w-80 h-36 sm:h-48 md:h-56 lg:h-64 flex items-center justify-center p-1 sm:p-2 rounded-3xl transition-transform duration-150 cursor-pointer active:scale-95 focus:outline-none ${
            isStriking ? "scale-105" : "hover:scale-[1.02]"
          }`}
          title="Chạm để thỉnh chuông"
          aria-label="Thỉnh chuông"
        >
          <div className="relative w-full h-full flex items-center justify-center">
            <Image
              src="/images/instruments/bell.png"
              alt="Chuông bát Phật giáo"
              width={320}
              height={244}
              className="object-contain drop-shadow-md select-none pointer-events-none transition-all duration-200 max-h-full max-w-full"
              priority
            />
          </div>
        </button>
      </div>

      {/* 2. Pill Control Bar (Matching Reference Screenshot) */}
      <div className="relative mt-3 sm:mt-5" ref={dropdownRef}>
        <div className="flex items-center rounded-full bg-surface-container-low border border-outline-variant/50 shadow-xs px-1 sm:px-1.5 py-0.5 sm:py-1 text-on-surface text-[11px] sm:text-xs font-label-md">
          {/* Toggle Auto Mode Button */}
          <button
            type="button"
            onClick={onToggleAuto}
            className={`flex items-center gap-1 sm:gap-1.5 px-2.5 sm:px-3.5 py-1 sm:py-1.5 rounded-full transition-all font-medium ${
              isAutoActive
                ? "bg-primary text-on-primary shadow-xs"
                : "hover:bg-surface-container-high text-on-surface"
            }`}
            title={isAutoActive ? "Tạm dừng tự động thỉnh chuông" : "Bật tự động thỉnh chuông"}
          >
            <span className="material-symbols-outlined text-[15px] sm:text-[16px]">
              {isAutoActive ? "pause" : "play_arrow"}
            </span>
            <span>Tự động</span>
          </button>

          {/* Divider */}
          <span className="w-px h-3.5 sm:h-4 bg-outline-variant/60 mx-0.5 sm:mx-1" />

          {/* Interval Dropdown Trigger */}
          <button
            type="button"
            suppressHydrationWarning
            onClick={() => setDropdownOpen((prev) => !prev)}
            className="flex items-center gap-0.5 sm:gap-1 px-2 sm:px-3 py-1 sm:py-1.5 rounded-full hover:bg-surface-container-high transition-colors font-medium text-on-surface whitespace-nowrap"
            title="Chọn khoảng cách giữa các tiếng chuông"
            aria-expanded={dropdownOpen}
          >
            <span suppressHydrationWarning>{currentOption.label}</span>
            <span className="material-symbols-outlined text-[15px] sm:text-[16px] text-outline">
              keyboard_arrow_down
            </span>
          </button>
        </div>

        {/* Dropdown Menu */}
        {dropdownOpen && (
          <div className="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-44 rounded-2xl bg-surface-container-lowest border border-outline-variant/30 shadow-lg p-1.5 z-40 max-h-56 overflow-y-auto animate-in fade-in zoom-in-95 duration-150">
            <div className="px-2 py-1 text-[11px] font-label-caps uppercase text-outline font-semibold">
              Khoảng cách thỉnh
            </div>
            {BELL_INTERVAL_OPTIONS.map((opt) => (
              <button
                key={opt.seconds}
                type="button"
                onClick={() => {
                  onSelectInterval(opt.seconds);
                  setDropdownOpen(false);
                }}
                className={`w-full text-left px-3 py-2 rounded-xl text-xs font-label-md transition-colors flex items-center justify-between ${
                  opt.seconds === intervalSeconds
                    ? "bg-primary/10 text-primary font-semibold"
                    : "text-on-surface hover:bg-surface-container-low"
                }`}
              >
                <span>{opt.label}</span>
                {opt.seconds === intervalSeconds && (
                  <span className="material-symbols-outlined text-[16px] text-primary">
                    check
                  </span>
                )}
              </button>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
