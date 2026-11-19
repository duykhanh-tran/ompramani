"use client";

import React from "react";
import { DharmaJourneyRange } from "@/types/dharma-journey";

interface JourneyRangeSelectorProps {
  currentRange: DharmaJourneyRange;
  onRangeChange: (range: DharmaJourneyRange) => void;
  isLoading?: boolean;
}

export function JourneyRangeSelector({
  currentRange,
  onRangeChange,
  isLoading = false,
}: JourneyRangeSelectorProps) {
  const options: { id: DharmaJourneyRange; label: string }[] = [
    { id: "7d", label: "7 ngày qua" },
    { id: "30d", label: "30 ngày qua" },
    { id: "90d", label: "90 ngày qua" },
    { id: "all", label: "Tất cả thời gian" },
  ];

  return (
    <div className="flex flex-wrap items-center justify-between gap-4 p-2 bg-surface-container-low rounded-2xl border border-outline-variant/40">
      <div className="flex items-center gap-1.5 overflow-x-auto py-0.5">
        <span className="text-xs font-label-md text-outline px-2 hidden sm:inline">
          Khoảng thời gian:
        </span>
        {options.map((opt) => {
          const isActive = currentRange === opt.id;
          return (
            <button
              key={opt.id}
              type="button"
              disabled={isLoading}
              onClick={() => onRangeChange(opt.id)}
              className={`px-3.5 py-1.5 rounded-xl font-label-md text-xs font-semibold transition-all cursor-pointer whitespace-nowrap ${
                isActive
                  ? "bg-primary text-on-primary shadow-xs"
                  : "bg-surface-container-lowest text-on-surface hover:bg-surface-container hover:text-primary"
              } ${isLoading ? "opacity-60 cursor-not-allowed" : ""}`}
            >
              {opt.label}
            </button>
          );
        })}
      </div>

      {isLoading && (
        <div className="flex items-center gap-2 pr-3 text-xs text-primary font-label-md animate-in fade-in">
          <span className="w-3.5 h-3.5 border-2 border-primary border-t-transparent rounded-full animate-spin" />
          <span>Đang tổng hợp số liệu...</span>
        </div>
      )}
    </div>
  );
}
