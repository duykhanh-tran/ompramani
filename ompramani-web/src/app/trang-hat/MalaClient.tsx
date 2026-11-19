"use client";

import React, { useState, useEffect, useCallback, useRef } from "react";
import Link from "next/link";
import { BEADS_PER_ROUND, calculateMalaRounds } from "@/lib/practice/mala";
import {
  initBeadAudio,
  playBeadClick,
  playRoundMilestoneBell,
} from "@/lib/utils/beadAudio";
import { MalaVerticalString } from "@/components/mala/MalaVerticalString";

export function MalaClient() {
  const [count, setCount] = useState<number>(0);
  const [soundEnabled, setSoundEnabled] = useState<boolean>(() => {
    if (typeof window === "undefined") return true;
    try {
      const stored = localStorage.getItem("ompramani_mala_sound");
      return stored !== null ? stored === "true" : true;
    } catch {
      return true;
    }
  });
  const [showResetConfirm, setShowResetConfirm] = useState<boolean>(false);
  const isAdvancingRef = useRef<boolean>(false);

  const toggleSound = () => {
    setSoundEnabled((prev) => {
      const next = !prev;
      try {
        localStorage.setItem("ompramani_mala_sound", String(next));
      } catch {
        // Ignore storage errors
      }
      return next;
    });
  };

  // Primary bead advance function
  const handleAdvanceBead = useCallback(() => {
    if (isAdvancingRef.current) return;

    setCount((prev) => {
      if (prev >= BEADS_PER_ROUND) {
        // Strict boundary: stop at 108
        return BEADS_PER_ROUND;
      }

      const next = prev + 1;

      // Audio feedback
      if (soundEnabled) {
        initBeadAudio();
        if (next === BEADS_PER_ROUND) {
          playRoundMilestoneBell();
        } else {
          playBeadClick();
        }
      }

      // Haptic feedback
      try {
        if (typeof navigator !== "undefined" && navigator.vibrate) {
          if (next === BEADS_PER_ROUND) {
            navigator.vibrate([30, 60, 30]);
          } else {
            navigator.vibrate(15);
          }
        }
      } catch {
        // Ignore haptic errors
      }

      return next;
    });

    isAdvancingRef.current = true;
    setTimeout(() => {
      isAdvancingRef.current = false;
    }, 90);
  }, [soundEnabled]);

  // Global Keyboard listener: Space and Enter
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      // Check if user is typing in an input, textarea, or contentEditable element
      const target = e.target as HTMLElement | null;
      if (
        target &&
        (target.tagName === "INPUT" ||
          target.tagName === "TEXTAREA" ||
          target.isContentEditable)
      ) {
        return;
      }

      // Ignore auto-repeated keydown events when holding a key
      if (e.repeat) return;

      if (e.code === "Space" || e.key === " " || e.key === "Enter") {
        // Prevent default page scroll on Space key
        if (e.code === "Space" || e.key === " ") {
          e.preventDefault();
        }
        handleAdvanceBead();
      }
    };

    window.addEventListener("keydown", handleKeyDown);
    return () => {
      window.removeEventListener("keydown", handleKeyDown);
    };
  }, [handleAdvanceBead]);

  // Handle Reset button click
  const handleResetClick = () => {
    if (count === 0) return;
    if (count === BEADS_PER_ROUND) {
      // At 108, directly restart next round
      setCount(0);
    } else {
      // In progress, ask for quick confirmation to prevent accidental loss
      setShowResetConfirm(true);
    }
  };

  const handleConfirmReset = () => {
    setCount(0);
    setShowResetConfirm(false);
  };

  const { beadCount, isCompletedRound } = calculateMalaRounds(count);

  return (
    <div className="w-full max-w-xl mx-auto px-4 py-4 sm:py-6 flex flex-col justify-between min-h-[calc(100vh-64px)] select-none">
      {/* 1. Header Bar */}
      <header className="flex items-center justify-between border-b border-[#ebdcca]/60 pb-3">
        <Link
          href="/"
          className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs sm:text-sm font-medium text-[#6b533a] hover:text-[#2c1d0f] hover:bg-[#ebdcca]/40 transition-colors"
          title="Trở về Trang chủ"
        >
          <span className="material-symbols-outlined text-lg leading-none">arrow_back</span>
          <span>Trang chủ</span>
        </Link>

        <h1 className="font-serif text-lg sm:text-xl font-bold text-[#382716] tracking-wide">
          CHUỖI HẠT 108
        </h1>

        <button
          type="button"
          onClick={toggleSound}
          className={`inline-flex items-center justify-center w-9 h-9 rounded-full border transition-colors ${
            soundEnabled
              ? "border-[#c49a45]/50 bg-[#fbf5e8] text-[#855e16] hover:bg-[#f7edce]"
              : "border-[#d8ccbc] bg-[#f0eae1] text-[#8c7b68] hover:bg-[#e7dfd4]"
          }`}
          title={soundEnabled ? "Tắt âm thanh lần hạt" : "Bật âm thanh lần hạt"}
          aria-label={soundEnabled ? "Tắt âm thanh" : "Bật âm thanh"}
        >
          <span className="material-symbols-outlined text-lg">
            {soundEnabled ? "volume_up" : "volume_off"}
          </span>
        </button>
      </header>

      {/* 2. Main Content */}
      <main className="flex-1 flex flex-col items-center justify-center my-3 sm:my-4 space-y-3 sm:space-y-4">
        {/* Large Counter Display */}
        <div className="text-center">
          <div
            className="font-serif text-5xl sm:text-6xl md:text-7xl font-bold text-[#3d2c18] tracking-wider tabular-nums drop-shadow-2xs"
            aria-live="polite"
          >
            {beadCount} / {BEADS_PER_ROUND}
          </div>

          {/* Status / Milestone Badge */}
          <div className="mt-2 min-h-[28px] flex items-center justify-center">
            {isCompletedRound ? (
              <div className="inline-flex items-center gap-1.5 px-4 py-1 rounded-full bg-[#fcedc7] border border-[#d4af37] text-[#694808] font-serif text-xs sm:text-sm font-bold shadow-xs animate-in fade-in duration-200">
                <span className="material-symbols-outlined text-sm sm:text-base leading-none text-[#b8860b]">
                  workspace_premium
                </span>
                <span>Hoàn thành 1 vòng</span>
              </div>
            ) : (
              <span className="text-xs text-[#8c7457] font-sans font-medium">
                {beadCount === 0 ? "Sẵn sàng lần hạt" : `Đang lần hạt thứ ${beadCount}`}
              </span>
            )}
          </div>
        </div>

        {/* 3D Vertical Bead String Component */}
        <div className="w-full">
          <MalaVerticalString
            currentRoundBeads={beadCount}
            onAdvanceBead={handleAdvanceBead}
            disabled={isCompletedRound}
          />
        </div>

        {/* Gesture & Keyboard Instructions */}
        <div className="text-center space-y-1">
          <p className="text-xs sm:text-sm font-medium text-[#5c462e] flex items-center justify-center gap-1">
            <span className="material-symbols-outlined text-base leading-none text-[#855e16]">
              swipe_vertical
            </span>
            <span>Vuốt lên hoặc xuống để lần hạt</span>
          </p>
          <p className="text-[11px] sm:text-xs text-[#8c7457] flex items-center justify-center gap-1">
            <span className="material-symbols-outlined text-sm leading-none text-[#a6927a]">
              keyboard
            </span>
            <span>Có thể nhấn Space hoặc Enter</span>
          </p>
        </div>
      </main>

      {/* 3. Action Controls */}
      <footer className="pt-2 sm:pt-3 border-t border-[#ebdcca]/50 flex flex-col sm:flex-row items-center justify-center gap-3">
        {/* Accessible "+1 Hạt" button for single tap */}
        <button
          type="button"
          onClick={handleAdvanceBead}
          disabled={isCompletedRound}
          className={`w-full sm:w-auto px-6 py-2.5 rounded-full font-serif text-sm sm:text-base font-semibold shadow-xs flex items-center justify-center gap-2 transition-all ${
            isCompletedRound
              ? "bg-[#e5dacf] text-[#9c8e7e] cursor-not-allowed border border-[#d6c7b7]"
              : "bg-[#8f5a28] hover:bg-[#784617] active:scale-98 text-[#fff9f0] border border-[#754415]"
          }`}
          title="Lần một hạt"
        >
          <span className="material-symbols-outlined text-lg leading-none">touch_app</span>
          <span>Lần một hạt</span>
        </button>

        {/* Reset / New Round button */}
        <button
          type="button"
          onClick={handleResetClick}
          disabled={count === 0}
          className={`w-full sm:w-auto px-5 py-2.5 rounded-full font-sans text-xs sm:text-sm font-medium transition-colors flex items-center justify-center gap-1.5 ${
            count === 0
              ? "text-[#b3a493] cursor-not-allowed"
              : isCompletedRound
              ? "bg-[#eeddb3] hover:bg-[#e4d09f] text-[#5e4209] font-semibold border border-[#c9aa5d]"
              : "text-[#6b533a] hover:text-[#2c1d0f] hover:bg-[#ebdcca]/50 border border-[#d8ccbc]"
          }`}
          title="Bắt đầu lại vòng chuỗi"
        >
          <span className="material-symbols-outlined text-base leading-none">refresh</span>
          <span>{isCompletedRound ? "Lần vòng mới" : "Bắt đầu lại"}</span>
        </button>
      </footer>

      {/* Reset Confirmation Dialog */}
      {showResetConfirm && (
        <div
          role="dialog"
          aria-modal="true"
          className="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4 animate-in fade-in duration-150"
        >
          <div className="bg-[#FAF7F0] border border-[#dfd2be] rounded-3xl p-6 max-w-sm w-full shadow-2xl space-y-4 text-[#382716]">
            <h3 className="font-serif text-base sm:text-lg font-bold text-[#382716]">
              Bắt đầu lại vòng chuỗi?
            </h3>
            <p className="font-sans text-xs sm:text-sm text-[#6d573f] leading-relaxed">
              Bạn đang ở hạt <strong>{count} / 108</strong>. Bạn có chắc chắn muốn đưa số hạt về 0 và bắt đầu lại?
            </p>
            <div className="flex items-center gap-3 pt-2">
              <button
                type="button"
                onClick={() => setShowResetConfirm(false)}
                className="flex-1 py-2.5 rounded-xl border border-[#c7b9a6] font-sans text-xs sm:text-sm font-medium text-[#5c462e] hover:bg-[#ebdcca]/50 transition-colors"
              >
                Hủy
              </button>
              <button
                type="button"
                onClick={handleConfirmReset}
                className="flex-1 py-2.5 rounded-xl bg-[#8f5a28] hover:bg-[#784617] text-[#fff9f0] font-sans text-xs sm:text-sm font-semibold shadow-xs transition-colors"
              >
                Bắt đầu lại
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
