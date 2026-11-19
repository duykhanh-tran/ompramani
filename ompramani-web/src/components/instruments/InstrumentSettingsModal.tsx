"use client";

import React from "react";
import Link from "next/link";
import { InstrumentPreferences } from "@/lib/practice/instruments";
import { PracticeSession } from "@/types/practice";

interface InstrumentSettingsModalProps {
  isOpen: boolean;
  onClose: () => void;
  preferences: InstrumentPreferences;
  onUpdatePreferences: (partial: Partial<InstrumentPreferences>) => void;
  // Session integration
  isAuthenticated: boolean;
  activeSession: PracticeSession | null;
  isStandaloneActive: boolean;
  isSupportMode: boolean;
  onStartStandaloneSession: () => void;
  onCompleteStandaloneSession: () => void;
  onCancelStandaloneSession: () => void;
  isOperating: boolean;
  // Counts
  bellCount: number;
  woodenFishCount: number;
  onResetCounts: () => void;
}

export function InstrumentSettingsModal({
  isOpen,
  onClose,
  preferences,
  onUpdatePreferences,
  isAuthenticated,
  activeSession,
  isStandaloneActive,
  isSupportMode,
  onStartStandaloneSession,
  onCompleteStandaloneSession,
  onCancelStandaloneSession,
  isOperating,
  bellCount,
  woodenFishCount,
  onResetCounts,
}: InstrumentSettingsModalProps) {
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs animate-in fade-in duration-200">
      <div
        className="w-full max-w-md rounded-3xl bg-surface-container-lowest border border-outline-variant/30 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-title"
      >
        {/* Modal Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-outline-variant/20 bg-surface-container-low/50">
          <div className="flex items-center gap-2">
            <span className="material-symbols-outlined text-[22px] text-primary">
              tune
            </span>
            <h2
              id="modal-title"
              className="font-serif text-lg font-semibold text-on-surface"
            >
              Cài đặt & Thời khóa
            </h2>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="w-8 h-8 rounded-full flex items-center justify-center text-outline hover:text-on-surface hover:bg-surface-container-high transition-colors"
            title="Đóng"
          >
            <span className="material-symbols-outlined text-[20px]">close</span>
          </button>
        </div>

        {/* Modal Body */}
        <div className="p-6 overflow-y-auto space-y-6">
          {/* 1. Volume & Audio Controls */}
          <div>
            <h3 className="text-xs font-label-caps uppercase tracking-wider text-outline font-semibold mb-3">
              Âm lượng nhạc cụ
            </h3>

            <div className="space-y-4">
              {/* Master Volume */}
              <div>
                <div className="flex items-center justify-between text-xs font-label-md text-on-surface mb-1.5">
                  <span className="flex items-center gap-1.5 font-medium">
                    <span className="material-symbols-outlined text-[18px] text-outline">
                      {preferences.isMuted
                        ? "volume_off"
                        : preferences.masterVolume === 0
                        ? "volume_mute"
                        : "volume_up"}
                    </span>
                    Âm lượng tổng
                  </span>
                  <span className="font-mono text-outline">
                    {preferences.isMuted
                      ? "Tắt tiếng"
                      : `${Math.round(preferences.masterVolume * 100)}%`}
                  </span>
                </div>
                <input
                  type="range"
                  min="0"
                  max="1"
                  step="0.05"
                  value={preferences.isMuted ? 0 : preferences.masterVolume}
                  disabled={preferences.isMuted}
                  onChange={(e) =>
                    onUpdatePreferences({ masterVolume: parseFloat(e.target.value) })
                  }
                  className="w-full accent-primary h-1.5 bg-surface-container-high rounded-lg cursor-pointer disabled:opacity-40"
                />
              </div>

              {/* Bell Volume */}
              <div>
                <div className="flex items-center justify-between text-xs font-label-md text-on-surface mb-1.5">
                  <span className="font-medium">Tiếng chuông</span>
                  <span className="font-mono text-outline">
                    {Math.round(preferences.bellVolume * 100)}%
                  </span>
                </div>
                <input
                  type="range"
                  min="0"
                  max="1"
                  step="0.05"
                  value={preferences.bellVolume}
                  disabled={preferences.isMuted}
                  onChange={(e) =>
                    onUpdatePreferences({ bellVolume: parseFloat(e.target.value) })
                  }
                  className="w-full accent-primary h-1.5 bg-surface-container-high rounded-lg cursor-pointer disabled:opacity-40"
                />
              </div>

              {/* Wooden Fish Volume */}
              <div>
                <div className="flex items-center justify-between text-xs font-label-md text-on-surface mb-1.5">
                  <span className="font-medium">Tiếng mõ</span>
                  <span className="font-mono text-outline">
                    {Math.round(preferences.woodenFishVolume * 100)}%
                  </span>
                </div>
                <input
                  type="range"
                  min="0"
                  max="1"
                  step="0.05"
                  value={preferences.woodenFishVolume}
                  disabled={preferences.isMuted}
                  onChange={(e) =>
                    onUpdatePreferences({
                      woodenFishVolume: parseFloat(e.target.value),
                    })
                  }
                  className="w-full accent-primary h-1.5 bg-surface-container-high rounded-lg cursor-pointer disabled:opacity-40"
                />
              </div>

              {/* Mute and Haptic Toggles */}
              <div className="pt-2 flex flex-col gap-2.5">
                <label className="flex items-center justify-between cursor-pointer py-1">
                  <span className="text-xs font-label-md text-on-surface font-medium">
                    Tắt toàn bộ âm thanh
                  </span>
                  <input
                    type="checkbox"
                    checked={preferences.isMuted}
                    onChange={(e) =>
                      onUpdatePreferences({ isMuted: e.target.checked })
                    }
                    className="w-4 h-4 accent-primary rounded cursor-pointer"
                  />
                </label>

                <label className="flex items-center justify-between cursor-pointer py-1">
                  <span className="text-xs font-label-md text-on-surface font-medium">
                    Rung xúc giác khi gõ (Mobile)
                  </span>
                  <input
                    type="checkbox"
                    checked={preferences.hapticEnabled}
                    onChange={(e) =>
                      onUpdatePreferences({ hapticEnabled: e.target.checked })
                    }
                    className="w-4 h-4 accent-primary rounded cursor-pointer"
                  />
                </label>
              </div>
            </div>
          </div>

          {/* 2. Practice Session Integration */}
          <div className="pt-4 border-t border-outline-variant/20">
            <h3 className="text-xs font-label-caps uppercase tracking-wider text-outline font-semibold mb-3">
              Thời khóa tu tập
            </h3>

            {isAuthenticated ? (
              <div className="space-y-3">
                {isStandaloneActive ? (
                  <div className="p-3.5 rounded-2xl bg-primary-container/20 border border-primary/30 flex flex-col gap-3">
                    <div className="flex items-center justify-between">
                      <span className="text-xs font-label-md text-primary font-bold flex items-center gap-1.5">
                        <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                        Thời khóa đang ghi nhận
                      </span>
                      <span className="text-xs font-mono text-outline">
                        {activeSession ? `${activeSession.active_duration_seconds}s` : ""}
                      </span>
                    </div>

                    <div className="flex gap-2">
                      <button
                        type="button"
                        onClick={onCompleteStandaloneSession}
                        disabled={isOperating}
                        className="flex-1 py-2 px-3 rounded-xl bg-primary text-on-primary font-label-md text-xs font-semibold shadow-xs hover:bg-primary-hover transition-colors disabled:opacity-50"
                      >
                        {isOperating ? "Đang lưu..." : "Hoàn thành thời khóa"}
                      </button>
                      <button
                        type="button"
                        onClick={onCancelStandaloneSession}
                        disabled={isOperating}
                        className="py-2 px-3 rounded-xl bg-surface-container-high text-error hover:bg-error/10 font-label-md text-xs font-medium transition-colors disabled:opacity-50"
                      >
                        Hủy
                      </button>
                    </div>
                  </div>
                ) : isSupportMode ? (
                  <div className="p-3.5 rounded-2xl bg-secondary-container/40 border border-secondary/30">
                    <div className="flex items-start gap-2">
                      <span className="material-symbols-outlined text-[18px] text-primary shrink-0 mt-0.5">
                        info
                      </span>
                      <div className="text-xs font-label-md text-on-secondary-container">
                        <p className="font-semibold text-primary">
                          Chế độ hỗ trợ thời khóa
                        </p>
                        <p className="mt-0.5 text-on-surface-variant">
                          Bạn đang có phiên tu tập{" "}
                          <strong>{activeSession?.practice_type_label}</strong> hoạt động. Chuông & Mõ đang giữ nhịp bổ trợ mà không làm gián đoạn thời khóa chính.
                        </p>
                      </div>
                    </div>
                  </div>
                ) : (
                  <div className="flex flex-col gap-2">
                    <p className="text-xs font-body-sm text-outline">
                      Lưu số lần thỉnh chuông, gõ mõ và thời lượng vào hồ sơ tu tập cá nhân của bạn.
                    </p>
                    <button
                      type="button"
                      onClick={onStartStandaloneSession}
                      disabled={isOperating}
                      className="w-full py-2.5 px-4 rounded-xl bg-primary text-on-primary font-label-md text-xs font-semibold shadow-xs hover:bg-primary-hover transition-colors flex items-center justify-center gap-1.5 disabled:opacity-50"
                    >
                      <span className="material-symbols-outlined text-[18px]">
                        play_circle
                      </span>
                      <span>Bắt đầu lưu thời khóa Chuông & Mõ</span>
                    </button>
                  </div>
                )}
              </div>
            ) : (
              <div className="p-3.5 rounded-2xl bg-surface-container-low border border-outline-variant/30 text-xs">
                <p className="text-on-surface-variant font-body-sm">
                  Bạn đang sử dụng ở chế độ <strong>Khách</strong>. Đăng nhập để tự động lưu số lần gõ và thời gian vào Nhật ký tu tập.
                </p>
                <Link
                  href="/dang-nhap?next=/chuong-mo"
                  className="mt-2.5 inline-flex items-center gap-1 text-primary font-semibold hover:underline"
                >
                  <span>Đăng nhập ngay</span>
                  <span className="material-symbols-outlined text-[14px]">
                    arrow_forward
                  </span>
                </Link>
              </div>
            )}
          </div>

          {/* 3. Strike Counts Summary */}
          <div className="pt-4 border-t border-outline-variant/20">
            <div className="flex items-center justify-between mb-2">
              <h3 className="text-xs font-label-caps uppercase tracking-wider text-outline font-semibold">
                Thống kê lần này
              </h3>
              {(bellCount > 0 || woodenFishCount > 0) && (
                <button
                  type="button"
                  onClick={onResetCounts}
                  className="text-[11px] font-label-md text-error hover:underline"
                >
                  Đặt lại số đếm
                </button>
              )}
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div className="p-3 rounded-2xl bg-surface-container-low text-center">
                <span className="text-[11px] text-outline font-label-md block">
                  Tiếng chuông
                </span>
                <span className="text-xl font-serif font-bold text-primary mt-0.5 block">
                  {bellCount}
                </span>
              </div>
              <div className="p-3 rounded-2xl bg-surface-container-low text-center">
                <span className="text-[11px] text-outline font-label-md block">
                  Tiếng mõ
                </span>
                <span className="text-xl font-serif font-bold text-amber-700 mt-0.5 block">
                  {woodenFishCount}
                </span>
              </div>
            </div>
          </div>
        </div>

        {/* Modal Footer */}
        <div className="p-4 border-t border-outline-variant/20 bg-surface-container-low/40 flex justify-end">
          <button
            type="button"
            onClick={onClose}
            className="px-5 py-2 rounded-xl bg-surface-container-high text-on-surface font-label-md text-xs font-semibold hover:bg-surface-container-highest transition-colors"
          >
            Đóng
          </button>
        </div>
      </div>
    </div>
  );
}
