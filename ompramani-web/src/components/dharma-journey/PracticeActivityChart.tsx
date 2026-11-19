"use client";

import React, { useState } from "react";
import { DailyActivityPoint, DharmaJourneyActivity } from "@/types/dharma-journey";
import { formatActiveDuration, formatNumberVi } from "@/lib/utils/sacred";

interface PracticeActivityChartProps {
  activity: DharmaJourneyActivity;
}

type MetricMode = "sessions" | "duration";

export function PracticeActivityChart({ activity }: PracticeActivityChartProps) {
  const [metricMode, setMetricMode] = useState<MetricMode>("sessions");
  const [hoveredPoint, setHoveredPoint] = useState<DailyActivityPoint | null>(null);

  const points = activity.points || [];
  const hasData = points.some((p) => p.completed_sessions > 0);

  // Calculate max values for chart scale
  const maxSessions = Math.max(1, ...points.map((p) => p.completed_sessions));
  const maxDurationMinutes = Math.max(
    1,
    ...points.map((p) => Math.ceil(p.active_duration_seconds / 60))
  );

  const maxValue = metricMode === "sessions" ? maxSessions : maxDurationMinutes;

  return (
    <div className="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-outline-variant/40 shadow-xs space-y-6">
      {/* Chart Header & Controls */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2 mb-1">
            <span className="w-2 h-2 rounded-full bg-primary" />
            <span className="font-label-caps text-xs text-primary uppercase font-semibold">
              Biểu Đồ Tu Học
            </span>
          </div>
          <h2 className="font-headline-xl text-xl sm:text-2xl font-bold text-on-surface">
            Nhịp Điệu Hành Trì
          </h2>
          <p className="font-body-sm text-xs text-on-surface-variant mt-0.5">
            {activity.granularity === "month"
              ? "Hoạt động tu tập theo từng tháng"
              : "Hoạt động tu tập theo từng ngày trong chuỗi thời gian liên tục"}
          </p>
        </div>

        {/* Metric Mode Switcher */}
        <div className="flex items-center p-1 bg-surface-container-low rounded-xl border border-outline-variant/30 self-start sm:self-auto">
          <button
            type="button"
            onClick={() => setMetricMode("sessions")}
            className={`px-3 py-1.5 rounded-lg font-label-md text-xs font-semibold transition-all cursor-pointer ${
              metricMode === "sessions"
                ? "bg-surface-container-lowest text-primary shadow-xs"
                : "text-outline hover:text-on-surface"
            }`}
          >
            Số phiên
          </button>
          <button
            type="button"
            onClick={() => setMetricMode("duration")}
            className={`px-3 py-1.5 rounded-lg font-label-md text-xs font-semibold transition-all cursor-pointer ${
              metricMode === "duration"
                ? "bg-surface-container-lowest text-primary shadow-xs"
                : "text-outline hover:text-on-surface"
            }`}
          >
            Thời lượng (phút)
          </button>
        </div>
      </div>

      {!hasData ? (
        <div className="py-14 text-center border border-dashed border-outline-variant/50 rounded-2xl p-6 space-y-3 bg-surface-container-low/30">
          <span className="material-symbols-outlined text-outline text-[40px]">
            calendar_today
          </span>
          <p className="font-headline-md text-sm font-semibold text-on-surface">
            Chưa có thời khóa tu tập trong khoảng thời gian này
          </p>
          <p className="font-body-sm text-xs text-outline max-w-md mx-auto">
            Khi bạn hoàn thành các thời khóa thiền định, lần hạt hoặc tụng niệm, biểu đồ sẽ ghi nhận từng bước tiến tu học mỗi ngày.
          </p>
        </div>
      ) : (
        <div className="space-y-4">
          {/* Active Hover Detail Banner */}
          <div className="min-h-[28px] flex items-center justify-between text-xs font-label-md px-1">
            {hoveredPoint ? (
              <div className="flex items-center gap-3 text-primary animate-in fade-in">
                <span className="font-bold text-on-surface">
                  Ngày {hoveredPoint.label}:
                </span>
                <span>
                  {formatNumberVi(hoveredPoint.completed_sessions)} phiên tu tập
                </span>
                <span>•</span>
                <span>{formatActiveDuration(hoveredPoint.active_duration_seconds)}</span>
              </div>
            ) : (
              <span className="text-outline text-[11px]">
                Di chuột hoặc chạm vào cột để xem chi tiết từng ngày
              </span>
            )}
          </div>

          {/* SVG / HTML Bar Chart */}
          <div className="w-full overflow-x-auto pb-2">
            <div
              className="min-w-[320px] flex items-end gap-1 sm:gap-2 h-48 sm:h-56 pt-6 px-1 border-b border-outline-variant/30"
              role="region"
              aria-label="Biểu đồ cột hoạt động tu tập"
            >
              {points.map((p, idx) => {
                const value =
                  metricMode === "sessions"
                    ? p.completed_sessions
                    : Math.ceil(p.active_duration_seconds / 60);

                const heightPercent =
                  value > 0
                    ? Math.max(8, Math.round((value / maxValue) * 100))
                    : 2;

                const isHovered = hoveredPoint?.date === p.date;

                return (
                  <div
                    key={p.date}
                    className="flex-1 flex flex-col items-center justify-end h-full group relative cursor-pointer"
                    onMouseEnter={() => setHoveredPoint(p)}
                    onMouseLeave={() => setHoveredPoint(null)}
                    onFocus={() => setHoveredPoint(p)}
                    onBlur={() => setHoveredPoint(null)}
                    tabIndex={0}
                    aria-label={`${p.label}: ${p.completed_sessions} phiên, ${formatActiveDuration(p.active_duration_seconds)}`}
                  >
                    {/* Tooltip on Hover */}
                    {isHovered && (
                      <div className="absolute -top-10 z-10 px-2 py-1 rounded-lg bg-on-surface text-surface text-[10px] font-label-md whitespace-nowrap shadow-md pointer-events-none">
                        {value > 0 ? (
                          metricMode === "sessions" ? (
                            `${value} phiên`
                          ) : (
                            `${value} phút`
                          )
                        ) : (
                          "0"
                        )}
                      </div>
                    )}

                    {/* Bar */}
                    <div
                      style={{ height: `${heightPercent}%` }}
                      className={`w-full max-w-[28px] rounded-t-md transition-all duration-300 ${
                        value > 0
                          ? isHovered
                            ? "bg-primary-hover shadow-sm"
                            : "bg-primary"
                          : "bg-surface-container"
                      }`}
                    />

                    {/* X-axis label (sampled on mobile if many points) */}
                    <span
                      className={`text-[10px] font-label-md text-outline mt-2 truncate max-w-full ${
                        points.length > 31 && idx % 4 !== 0
                          ? "hidden sm:inline"
                          : ""
                      } ${
                        points.length > 15 && idx % 2 !== 0 ? "hidden md:inline" : ""
                      }`}
                    >
                      {p.label}
                    </span>
                  </div>
                );
              })}
            </div>
          </div>

          {/* Accessible Table for Screen Readers */}
          <details className="text-xs text-outline cursor-pointer pt-2">
            <summary className="font-label-md hover:text-primary transition-colors">
              Xem bảng dữ liệu chi tiết
            </summary>
            <div className="mt-2 max-h-48 overflow-y-auto border border-outline-variant/30 rounded-xl p-2">
              <table className="w-full text-left text-xs">
                <thead>
                  <tr className="border-b border-outline-variant/20 text-on-surface font-semibold">
                    <th className="py-1">Thời gian</th>
                    <th className="py-1">Số phiên</th>
                    <th className="py-1">Thời gian</th>
                  </tr>
                </thead>
                <tbody>
                  {points.map((p) => (
                    <tr key={p.date} className="border-b border-outline-variant/10">
                      <td className="py-1">{p.date}</td>
                      <td className="py-1">{p.completed_sessions}</td>
                      <td className="py-1">
                        {formatActiveDuration(p.active_duration_seconds)}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </details>
        </div>
      )}
    </div>
  );
}
