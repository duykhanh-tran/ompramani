"use client";

import React, { useState, useEffect } from "react";
import { playSingingBowl } from "@/lib/utils/bellAudio";

interface MeditationActiveSessionProps {
  plannedDurationSeconds: number;
  activeDurationSeconds: number;
  isPaused: boolean;
  soundEnabled: boolean;
  onToggleSound: () => void;
  onPause: () => Promise<void> | void;
  onResume: () => Promise<void> | void;
  onComplete: () => Promise<void> | void;
  onCancel: () => Promise<void> | void;
  isOperating?: boolean;
}

export function MeditationActiveSession({
  plannedDurationSeconds,
  activeDurationSeconds,
  isPaused,
  soundEnabled,
  onToggleSound,
  onPause,
  onResume,
  onComplete,
  onCancel,
  isOperating = false,
}: MeditationActiveSessionProps) {
  const [showExitConfirm, setShowExitConfirm] = useState(false);
  const [isFullscreen, setIsFullscreen] = useState(false);

  // Breathing cycle: 4s inhale, 4s exhale
  const [breathPhase, setBreathPhase] = useState<"inhale" | "exhale">("inhale");

  // Track remaining countdown seconds
  const remainingSeconds = Math.max(
    0,
    plannedDurationSeconds - activeDurationSeconds
  );

  // Smooth breathing cycle progression (switches every 4s) when not paused
  useEffect(() => {
    if (isPaused) return;

    const interval = setInterval(() => {
      setBreathPhase((prev) => (prev === "inhale" ? "exhale" : "inhale"));
    }, 4000);

    return () => clearInterval(interval);
  }, [isPaused]);

  // Auto-complete when countdown reaches 0
  useEffect(() => {
    if (!isPaused && remainingSeconds <= 0 && plannedDurationSeconds > 0) {
      const timer = setTimeout(() => {
        onComplete();
      }, 500);
      return () => clearTimeout(timer);
    }
  }, [isPaused, remainingSeconds, plannedDurationSeconds, onComplete]);

  // Handle full-screen toggling
  const toggleFullscreen = async () => {
    try {
      if (!document.fullscreenElement) {
        await document.documentElement.requestFullscreen();
        setIsFullscreen(true);
      } else {
        if (document.exitFullscreen) {
          await document.exitFullscreen();
          setIsFullscreen(false);
        }
      }
    } catch (err) {
      console.warn("Fullscreen toggle error:", err);
    }
  };

  useEffect(() => {
    const handleFsChange = () => {
      setIsFullscreen(!!document.fullscreenElement);
    };
    document.addEventListener("fullscreenchange", handleFsChange);
    return () => document.removeEventListener("fullscreenchange", handleFsChange);
  }, []);

  // Format seconds to MM:SS
  const formatTime = (secs: number) => {
    const m = Math.floor(secs / 60);
    const s = secs % 60;
    return `${m.toString().padStart(2, "0")}:${s.toString().padStart(2, "0")}`;
  };

  // Ring scale and styling based on breath phase
  const isExhaling = breathPhase === "exhale";

  return (
    <div className="fixed inset-0 z-50 bg-[#131211] text-stone-100 flex flex-col justify-between select-none overflow-hidden font-sans">
      {/* Ambient background glow */}
      <div className="absolute inset-0 pointer-events-none flex items-center justify-center">
        <div
          className={`w-[520px] h-[520px] rounded-full transition-all duration-[4000ms] ease-in-out blur-3xl opacity-30 ${
            isExhaling ? "scale-75 bg-amber-900/30" : "scale-110 bg-[#c49b4f]/40"
          }`}
        />
      </div>

      {/* Top Bar Navigation */}
      <header className="relative z-10 w-full px-4 sm:px-8 py-5 flex items-center justify-between border-b border-stone-800/60 bg-black/20 backdrop-blur-md">
        <div className="flex items-center gap-3">
          <div className="w-9 h-9 rounded-full bg-[#27231c] border border-amber-500/30 flex items-center justify-center text-amber-400">
            <span className="material-symbols-outlined text-[20px]">
              self_improvement
            </span>
          </div>
          <div>
            <h2 className="font-headline-md text-base sm:text-lg font-semibold text-stone-100 leading-tight">
              Quán niệm hơi thở
            </h2>
            <p className="font-label-caps text-[11px] text-amber-400/80 tracking-wider uppercase">
              {formatTime(plannedDurationSeconds)} tĩnh tọa · Chánh niệm
            </p>
          </div>
        </div>

        <div className="flex items-center gap-2 sm:gap-3">
          {/* Sound Toggle */}
          <button
            type="button"
            suppressHydrationWarning
            onClick={() => {
              onToggleSound();
              if (!soundEnabled) {
                playSingingBowl("bell");
              }
            }}
            className={`w-9 h-9 sm:w-10 sm:h-10 rounded-full border flex items-center justify-center transition-colors cursor-pointer ${
              soundEnabled
                ? "bg-amber-900/30 border-amber-500/50 text-amber-300"
                : "bg-stone-900/70 border-stone-800 text-stone-400 hover:text-stone-200"
            }`}
            title={soundEnabled ? "Tắt tiếng chuông" : "Bật tiếng chuông"}
            aria-label="Chuyển đổi âm thanh"
          >
            <span className="material-symbols-outlined text-[18px]">
              {soundEnabled ? "notifications_active" : "notifications_off"}
            </span>
          </button>

          {/* Fullscreen Toggle */}
          <button
            type="button"
            suppressHydrationWarning
            onClick={toggleFullscreen}
            className="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-stone-900/70 border border-stone-800 text-stone-400 hover:text-stone-200 flex items-center justify-center transition-colors cursor-pointer"
            title={isFullscreen ? "Thoát toàn màn hình" : "Toàn màn hình"}
            aria-label="Toàn màn hình"
          >
            <span className="material-symbols-outlined text-[18px]">
              {isFullscreen ? "fullscreen_exit" : "fullscreen"}
            </span>
          </button>

          {/* Close / Exit Button */}
          <button
            type="button"
            suppressHydrationWarning
            onClick={() => setShowExitConfirm(true)}
            className="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-stone-900/70 border border-stone-800 text-stone-400 hover:text-red-400 hover:border-red-900/50 flex items-center justify-center transition-colors cursor-pointer ml-1"
            title="Dừng thời thiền"
            aria-label="Dừng thời thiền"
          >
            <span className="material-symbols-outlined text-[20px]">
              close
            </span>
          </button>
        </div>
      </header>

      {/* Center Breathing Visualizer */}
      <main className="relative z-10 flex-1 flex flex-col items-center justify-center px-4 my-auto">
        <div className="relative flex flex-col items-center justify-center">
          {/* Outermost Pulsing Concentric Aura Rings */}
          <div
            className={`absolute rounded-full border border-amber-500/20 pointer-events-none transition-all duration-[4000ms] ease-in-out ${
              isExhaling
                ? "w-[280px] h-[280px] sm:w-[360px] sm:h-[360px] scale-90 opacity-20"
                : "w-[340px] h-[340px] sm:w-[450px] sm:h-[450px] scale-115 opacity-60"
            }`}
          />
          <div
            className={`absolute rounded-full border border-amber-400/30 pointer-events-none transition-all duration-[4000ms] ease-in-out ${
              isExhaling
                ? "w-[240px] h-[240px] sm:w-[310px] sm:h-[310px] scale-95 opacity-30"
                : "w-[290px] h-[290px] sm:w-[380px] sm:h-[380px] scale-110 opacity-70 shadow-[0_0_40px_rgba(212,175,55,0.25)]"
            }`}
          />

          {/* Main Breathing Circle (Expanding & Contracting with Breath) */}
          <div
            className={`relative rounded-full flex flex-col items-center justify-center transition-all duration-[4000ms] ease-in-out border-2 shadow-2xl ${
              isExhaling
                ? "w-48 h-48 sm:w-64 sm:h-64 scale-90 border-amber-600/40 bg-gradient-to-br from-[#1e1a14] via-[#16130f] to-[#0e0c0a]"
                : "w-48 h-48 sm:w-64 sm:h-64 scale-110 border-[#d4af37] bg-gradient-to-br from-[#2f2719] via-[#211b10] to-[#141009] shadow-[0_0_60px_rgba(212,175,55,0.4)]"
            }`}
          >
            {/* Inner gentle lotus icon watermarked */}
            <span className="material-symbols-outlined text-[32px] sm:text-[40px] text-amber-500/40 mb-1 transition-opacity duration-1000">
              spa
            </span>

            {/* Inhale / Exhale guidance text matching user screenshot */}
            <h3
              className={`font-display-lg text-xl sm:text-2xl font-medium tracking-wide transition-all duration-1000 ${
                isExhaling
                  ? "text-stone-300 scale-95"
                  : "text-amber-200 scale-105 drop-shadow-[0_2px_8px_rgba(212,175,55,0.5)]"
              }`}
            >
              {isPaused
                ? "Tạm dừng"
                : breathPhase === "inhale"
                ? "Hít vào..."
                : "Thở ra..."}
            </h3>

            {/* Mindful subtext */}
            <p className="font-label-caps text-[10px] sm:text-[11px] text-amber-400/80 tracking-widest uppercase mt-1">
              {isPaused
                ? "Tâm an trú"
                : breathPhase === "inhale"
                ? "Tâm tĩnh lặng"
                : "Miệng mỉm cười"}
            </p>
          </div>

          {/* Countdown Timer Display */}
          <div className="mt-8 sm:mt-10 flex flex-col items-center text-center">
            <div className="font-headline-xl text-4xl sm:text-5xl md:text-6xl font-light tracking-wider text-stone-100 tabular-nums font-mono drop-shadow-md">
              {formatTime(remainingSeconds)}
            </div>

            <div className="flex items-center gap-2 mt-2 px-3 py-1 rounded-full bg-stone-900/60 border border-stone-800">
              <span
                className={`w-2 h-2 rounded-full ${
                  isPaused
                    ? "bg-amber-500"
                    : "bg-emerald-400 animate-pulse"
                }`}
              />
              <span className="font-label-caps text-xs text-stone-300">
                {isPaused ? "Đang tạm dừng" : "Đang thiền"}
              </span>
            </div>
          </div>
        </div>
      </main>

      {/* Bottom Interactive Controls */}
      <footer className="relative z-10 w-full px-4 sm:px-8 py-6 flex flex-col sm:flex-row items-center justify-center gap-4 bg-black/30 backdrop-blur-md border-t border-stone-800/60">
        <div className="flex items-center gap-3 sm:gap-4 w-full sm:w-auto justify-center">
          {/* Pause / Resume Button */}
          {isPaused ? (
            <button
              type="button"
              suppressHydrationWarning
              disabled={isOperating}
              onClick={onResume}
              className="px-6 py-3 rounded-full bg-amber-500 hover:bg-amber-400 text-stone-950 font-label-lg text-sm font-semibold flex items-center justify-center gap-2 shadow-lg transition-all cursor-pointer min-w-[140px]"
            >
              <span className="material-symbols-outlined text-[20px]">
                play_arrow
              </span>
              <span>Tiếp tục</span>
            </button>
          ) : (
            <button
              type="button"
              suppressHydrationWarning
              disabled={isOperating}
              onClick={onPause}
              className="px-6 py-3 rounded-full bg-stone-800 hover:bg-stone-700 text-stone-100 border border-stone-700 font-label-lg text-sm font-semibold flex items-center justify-center gap-2 transition-all cursor-pointer min-w-[140px]"
            >
              <span className="material-symbols-outlined text-[20px]">
                pause
              </span>
              <span>Tạm dừng</span>
            </button>
          )}

          {/* Early Complete Button */}
          <button
            type="button"
            suppressHydrationWarning
            disabled={isOperating}
            onClick={onComplete}
            className="px-5 py-3 rounded-full bg-stone-900/80 hover:bg-stone-800 text-stone-300 hover:text-stone-100 border border-stone-800 font-label-lg text-sm flex items-center justify-center gap-2 transition-all cursor-pointer"
            title="Kết thúc và ghi nhận thời gian thiền"
          >
            <span className="material-symbols-outlined text-[18px] text-amber-400">
              check_circle
            </span>
            <span className="hidden sm:inline">Hoàn thành thời thiền</span>
            <span className="sm:hidden">Hoàn thành</span>
          </button>

          {/* Cancel Button */}
          <button
            type="button"
            suppressHydrationWarning
            disabled={isOperating}
            onClick={() => setShowExitConfirm(true)}
            className="px-4 py-3 rounded-full hover:bg-stone-900 text-stone-400 hover:text-stone-200 font-label-lg text-sm transition-colors cursor-pointer"
            title="Hủy thời thiền"
          >
            Hủy
          </button>
        </div>
      </footer>

      {/* Exit / Stop Confirmation Modal */}
      {showExitConfirm && (
        <div className="fixed inset-0 z-60 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-150">
          <div className="bg-[#1c1a17] border border-stone-800 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-5 text-stone-100">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-full bg-amber-900/30 border border-amber-500/40 text-amber-400 flex items-center justify-center shrink-0">
                <span className="material-symbols-outlined text-[22px]">
                  question_mark
                </span>
              </div>
              <div>
                <h3 className="font-headline-md text-lg font-semibold text-stone-100">
                  Dừng thời thiền?
                </h3>
                <p className="font-body-sm text-xs text-stone-400">
                  Đã thiền được {Math.floor(activeDurationSeconds / 60)} phút {activeDurationSeconds % 60} giây
                </p>
              </div>
            </div>

            <p className="font-body-sm text-xs text-stone-300 leading-relaxed">
              Bạn có thể hoàn thành ngay để lưu lại công đức tu tập vào sổ tu học,
              hoặc tiếp tục an trú trong hơi thở.
            </p>

            <div className="flex flex-col gap-2 pt-2">
              <button
                type="button"
                suppressHydrationWarning
                onClick={() => setShowExitConfirm(false)}
                className="w-full py-3 rounded-xl bg-amber-500 text-stone-950 font-label-md text-sm font-semibold hover:bg-amber-400 transition-colors shadow-sm"
              >
                Tiếp tục thiền
              </button>

              <button
                type="button"
                suppressHydrationWarning
                onClick={() => {
                  setShowExitConfirm(false);
                  onComplete();
                }}
                className="w-full py-2.5 rounded-xl bg-stone-800 text-stone-200 border border-stone-700 font-label-md text-xs hover:bg-stone-700 transition-colors"
              >
                Hoàn thành &amp; Lưu thời lượng ({Math.max(1, Math.floor(activeDurationSeconds / 60))} phút)
              </button>

              <button
                type="button"
                suppressHydrationWarning
                onClick={() => {
                  setShowExitConfirm(false);
                  onCancel();
                }}
                className="w-full py-2 text-stone-500 hover:text-red-400 font-label-md text-xs transition-colors"
              >
                Hủy bỏ thời thiền (không lưu)
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
