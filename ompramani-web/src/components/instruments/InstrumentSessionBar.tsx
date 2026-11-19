"use client";

import React from "react";
import { formatTimer } from "@/lib/practice/instruments";

interface InstrumentSessionBarProps {
  bellCount: number;
  woodenFishCount: number;
  isStandaloneActive: boolean;
  isSupportMode: boolean;
  durationSeconds: number;
  syncStatus: "saved" | "syncing" | "unsynced" | "guest";
  onComplete: () => void;
  onOpenSettings: () => void;
  isOperating: boolean;
}

export function InstrumentSessionBar({
  bellCount,
  woodenFishCount,
  isStandaloneActive,
  isSupportMode,
  durationSeconds,
  syncStatus,
  onComplete,
  onOpenSettings,
  isOperating,
}: InstrumentSessionBarProps) {
  // Show bar if session is active or user has performed strikes
  const hasStrikes = bellCount > 0 || woodenFishCount > 0;
  if (!isStandaloneActive && !isSupportMode && !hasStrikes) {
    return null;
  }

  return (
    <div className="fixed bottom-6 left-1/2 -translate-x-1/2 z-30 w-full max-w-sm px-4 select-none">
      <div className="flex items-center justify-between rounded-full bg-surface-container-lowest/90 backdrop-blur-md border border-outline-variant/40 shadow-xl px-4 py-2.5 transition-all">
        {/* Left: Strike Counters */}
        <div className="flex items-center gap-3 text-xs font-label-md">
          <div className="flex items-center gap-1 text-primary font-semibold">
            <span>🔔</span>
            <span className="font-mono">{bellCount}</span>
          </div>
          <span className="w-px h-3.5 bg-outline-variant/50" />
          <div className="flex items-center gap-1 text-amber-700 font-semibold">
            <span>🪵</span>
            <span className="font-mono">{woodenFishCount}</span>
          </div>
        </div>

        {/* Center / Right: Session state or Action */}
        <div className="flex items-center gap-2">
          {isStandaloneActive ? (
            <>
              {/* Duration Timer & Sync indicator */}
              <div className="flex items-center gap-1.5 text-xs font-mono text-outline font-medium px-2 py-0.5 rounded-full bg-surface-container-low">
                <span
                  className={`w-1.5 h-1.5 rounded-full ${
                    syncStatus === "syncing"
                      ? "bg-amber-500 animate-pulse"
                      : "bg-emerald-500"
                  }`}
                  title={syncStatus === "syncing" ? "Đang đồng bộ..." : "Đã đồng bộ"}
                />
                <span>⏱</span>
                <span>{formatTimer(durationSeconds)}</span>
              </div>

              {/* Complete button */}
              <button
                type="button"
                onClick={onComplete}
                disabled={isOperating}
                className="py-1 px-3 rounded-full bg-primary text-on-primary text-xs font-label-md font-semibold shadow-xs hover:bg-primary-hover transition-colors disabled:opacity-50"
              >
                {isOperating ? "Lưu..." : "Hoàn thành"}
              </button>
            </>
          ) : isSupportMode ? (
            <div className="flex items-center gap-1 text-[11px] font-label-caps text-secondary-container bg-secondary px-2.5 py-0.5 rounded-full font-medium">
              <span>Hỗ trợ thời khóa</span>
            </div>
          ) : (
            <button
              type="button"
              onClick={onOpenSettings}
              className="text-[11px] font-label-md text-primary hover:underline font-medium"
            >
              Lưu thời khóa
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
