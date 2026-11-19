"use client";

import React, { useState, useRef, useEffect } from "react";
import Image from "next/image";
import { WOODEN_FISH_INTERVAL_OPTIONS } from "@/lib/practice/instruments";

interface WoodenFishSectionProps {
  onStrike: () => void;
  isStriking: boolean;
  isAutoActive: boolean;
  onToggleAuto: () => void;
  intervalSeconds: number;
  onSelectInterval: (seconds: number) => void;
}

export function WoodenFishSection({
  onStrike,
  isStriking,
  isAutoActive,
  onToggleAuto,
  intervalSeconds,
  onSelectInterval,
}: WoodenFishSectionProps) {
  const [dropdownOpen, setDropdownOpen] = useState<boolean>(false);
  const dropdownRef = useRef<HTMLDivElement>(null);

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
    WOODEN_FISH_INTERVAL_OPTIONS.find((opt) => opt.seconds === intervalSeconds) ||
    WOODEN_FISH_INTERVAL_OPTIONS[4]; // Default: 2 giây

  return (
    <div className="flex flex-col items-center justify-center py-2 sm:py-6 select-none w-full">
      {/* 1. Wooden Fish Instrument Tap Target */}
      <div className="relative flex items-center justify-center">
        {/* Subtle resonance aura on strike */}
        <div
          className={`absolute w-44 h-40 sm:w-60 sm:h-52 md:w-72 md:h-64 lg:w-80 lg:h-72 rounded-full bg-amber-600/15 blur-xl pointer-events-none transition-opacity duration-200 ${
            isStriking ? "opacity-100 scale-105" : "opacity-0 scale-95"
          }`}
        />

        <button
          type="button"
          onClick={onStrike}
          className={`relative z-10 w-44 sm:w-60 md:w-72 lg:w-80 h-36 sm:h-48 md:h-56 lg:h-64 flex items-center justify-center p-1 sm:p-2 rounded-3xl transition-transform duration-100 cursor-pointer active:scale-90 focus:outline-none ${
            isStriking ? "scale-95" : "hover:scale-[1.02]"
          }`}
          title="Chạm để gõ mõ"
          aria-label="Gõ mõ"
        >
          <div className="relative w-full h-full flex items-center justify-center">
            <Image
              src="/images/instruments/wooden-fish.png"
              alt="Mõ gỗ tụng kinh"
              width={330}
              height={252}
              className="object-contain drop-shadow-md select-none pointer-events-none transition-all duration-150 max-h-full max-w-full"
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
            title={isAutoActive ? "Tạm dừng tự động gõ mõ" : "Bật tự động gõ mõ"}
          >
            <span className="material-symbols-outlined text-[15px] sm:text-[16px]">
              {isAutoActive ? "pause" : "play_arrow"}
            </span>
            <span>Tự động</span>
          </button>

          {/* Divider */}
          <span className="w-px h-3.5 sm:h-4 bg-outline-variant/60 mx-0.5 sm:mx-1" />

          {/* Interval/Tempo Dropdown Trigger */}
          <button
            type="button"
            suppressHydrationWarning
            onClick={() => setDropdownOpen((prev) => !prev)}
            className="flex items-center gap-0.5 sm:gap-1 px-2 sm:px-3 py-1 sm:py-1.5 rounded-full hover:bg-surface-container-high transition-colors font-medium text-on-surface whitespace-nowrap"
            title="Chọn nhịp độ gõ mõ"
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
          <div className="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-52 rounded-2xl bg-surface-container-lowest border border-outline-variant/30 shadow-lg p-1.5 z-40 max-h-56 overflow-y-auto animate-in fade-in zoom-in-95 duration-150">
            <div className="px-2 py-1 text-[11px] font-label-caps uppercase text-outline font-semibold">
              Nhịp độ gõ mõ
            </div>
            {WOODEN_FISH_INTERVAL_OPTIONS.map((opt) => (
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
