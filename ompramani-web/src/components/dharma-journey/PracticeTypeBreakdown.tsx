import React from "react";
import { DharmaJourneyBreakdown } from "@/types/dharma-journey";
import { formatActiveDuration, formatNumberVi } from "@/lib/utils/sacred";

interface PracticeTypeBreakdownProps {
  breakdown: DharmaJourneyBreakdown;
}

export function PracticeTypeBreakdown({ breakdown }: PracticeTypeBreakdownProps) {
  const items = breakdown.items || [];

  const getTypeIcon = (type: string) => {
    switch (type) {
      case "meditation":
        return "self_improvement";
      case "mala":
        return "adjust";
      case "mantra_recitation":
        return "record_voice_over";
      case "buddha_name_recitation":
        return "spa";
      case "sutra_reading":
      case "sutra_recitation":
        return "auto_stories";
      case "bell_wooden_fish":
        return "notifications";
      default:
        return "spa";
    }
  };

  return (
    <div className="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-outline-variant/40 shadow-xs space-y-6">
      <div>
        <div className="flex items-center gap-2 mb-1">
          <span className="w-2 h-2 rounded-full bg-primary" />
          <span className="font-label-caps text-xs text-primary uppercase font-semibold">
            Phân Loại Pháp Môn
          </span>
        </div>
        <h2 className="font-headline-xl text-xl sm:text-2xl font-bold text-on-surface">
          Chi Tiết Từng Hình Thức Tu Tập
        </h2>
        <p className="font-body-sm text-xs text-on-surface-variant mt-0.5">
          Tổng hợp theo từng thời khóa và chỉ số ghi nhận thực tế
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {items.map((item) => {
          const hasActivity = item.completed_sessions > 0;

          return (
            <div
              key={item.practice_type}
              className={`rounded-2xl p-5 border transition-all flex flex-col justify-between space-y-4 ${
                hasActivity
                  ? "bg-surface-container-lowest border-outline-variant/40 hover:border-primary/40 shadow-xs"
                  : "bg-surface-container-low/40 border-outline-variant/20 opacity-70"
              }`}
            >
              <div className="flex items-center justify-between gap-3">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center text-primary shrink-0">
                    <span className="material-symbols-outlined text-[22px]">
                      {getTypeIcon(item.practice_type)}
                    </span>
                  </div>
                  <div>
                    <h3 className="font-headline-md text-base font-bold text-on-surface">
                      {item.label}
                    </h3>
                    <span className="font-label-md text-xs text-outline">
                      {item.completed_sessions} phiên hoàn thành
                    </span>
                  </div>
                </div>
              </div>

              {/* Metrics & Duration */}
              <div className="space-y-2 pt-2 border-t border-outline-variant/20">
                <div className="flex items-center justify-between text-xs font-label-md">
                  <span className="text-outline">Thời gian hành trì:</span>
                  <span className="font-semibold text-on-surface">
                    {formatActiveDuration(item.active_duration_seconds)}
                  </span>
                </div>

                {item.metrics?.bead_count !== undefined && item.metrics.bead_count > 0 && (
                  <div className="flex items-center justify-between text-xs font-label-md">
                    <span className="text-outline">Số hạt đã lần:</span>
                    <span className="font-semibold text-primary">
                      {formatNumberVi(item.metrics.bead_count)} hạt
                    </span>
                  </div>
                )}

                {item.metrics?.completed_rounds !== undefined &&
                  item.metrics.completed_rounds > 0 && (
                    <div className="flex items-center justify-between text-xs font-label-md">
                      <span className="text-outline">Số vòng 108:</span>
                      <span className="font-semibold text-primary">
                        {formatNumberVi(item.metrics.completed_rounds)} vòng
                      </span>
                    </div>
                  )}

                {item.metrics?.repetition_count !== undefined &&
                  item.metrics.repetition_count > 0 && (
                    <div className="flex items-center justify-between text-xs font-label-md">
                      <span className="text-outline">Số biến tự ghi nhận:</span>
                      <span className="font-semibold text-primary">
                        {formatNumberVi(item.metrics.repetition_count)} biến
                      </span>
                    </div>
                  )}

                {item.metrics?.bell_count !== undefined && item.metrics.bell_count > 0 && (
                  <div className="flex items-center justify-between text-xs font-label-md">
                    <span className="text-outline">Tiếng chuông:</span>
                    <span className="font-semibold text-primary">
                      {formatNumberVi(item.metrics.bell_count)}
                    </span>
                  </div>
                )}

                {item.metrics?.wooden_fish_count !== undefined &&
                  item.metrics.wooden_fish_count > 0 && (
                    <div className="flex items-center justify-between text-xs font-label-md">
                      <span className="text-outline">Tiếng mõ:</span>
                      <span className="font-semibold text-primary">
                        {formatNumberVi(item.metrics.wooden_fish_count)}
                      </span>
                    </div>
                  )}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
