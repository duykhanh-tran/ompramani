"use client";

import React, { useState, useEffect } from "react";
import Link from "next/link";
import { PracticeSession } from "@/types/practice";
import { getPracticeSessions } from "@/lib/api/practice-sessions";
import { formatActiveDuration, formatArticleDate, formatNumberVi } from "@/lib/utils/sacred";

export function JourneyRecentSessions() {
  const [sessions, setSessions] = useState<PracticeSession[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(true);

  useEffect(() => {
    let ignore = false;
    getPracticeSessions({ status: "completed", per_page: 5 })
      .then((res) => {
        if (!ignore) {
          setSessions(res.data || []);
          setIsLoading(false);
        }
      })
      .catch(() => {
        if (!ignore) setIsLoading(false);
      });

    return () => {
      ignore = true;
    };
  }, []);

  const getTypeLabel = (type: string) => {
    switch (type) {
      case "meditation":
        return "Thiền Định";
      case "mala":
        return "Tràng Hạt";
      case "mantra_recitation":
        return "Trì Chú";
      case "buddha_name_recitation":
        return "Niệm Phật";
      case "sutra_reading":
        return "Đọc Kinh";
      case "sutra_recitation":
        return "Tụng Kinh";
      case "bell_wooden_fish":
        return "Chuông & Mõ";
      default:
        return "Tu Tập";
    }
  };

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
      <div className="flex items-center justify-between">
        <div>
          <div className="flex items-center gap-2 mb-1">
            <span className="w-2 h-2 rounded-full bg-primary" />
            <span className="font-label-caps text-xs text-primary uppercase font-semibold">
              Ghi Dấu Tu Tập
            </span>
          </div>
          <h2 className="font-headline-xl text-xl sm:text-2xl font-bold text-on-surface">
            Thời Khóa Gần Đây
          </h2>
        </div>

        <Link
          href="/tai-khoan"
          className="text-xs font-label-md text-primary hover:underline flex items-center gap-1"
        >
          <span>Xem tất cả</span>
          <span className="material-symbols-outlined text-[16px]">
            chevron_right
          </span>
        </Link>
      </div>

      {isLoading ? (
        <div className="space-y-3">
          {[1, 2, 3].map((n) => (
            <div
              key={n}
              className="h-16 rounded-2xl bg-surface-container-low animate-pulse"
            />
          ))}
        </div>
      ) : sessions.length === 0 ? (
        <div className="py-8 text-center text-outline text-xs font-label-md">
          Chưa có thời khóa hoàn thành nào.
        </div>
      ) : (
        <div className="space-y-3">
          {sessions.map((session) => (
            <div
              key={session.id}
              className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-2xl bg-surface-container-low border border-outline-variant/30 hover:border-outline-variant transition-colors"
            >
              <div className="flex items-center gap-3 min-w-0">
                <div className="w-10 h-10 rounded-xl bg-surface-container-lowest flex items-center justify-center text-primary shrink-0 shadow-xs">
                  <span className="material-symbols-outlined text-[20px]">
                    {getTypeIcon(session.practice_type)}
                  </span>
                </div>

                <div className="min-w-0">
                  <div className="flex items-center gap-2">
                    <span className="font-headline-md text-sm font-semibold text-on-surface truncate">
                      {getTypeLabel(session.practice_type)}
                    </span>
                    {session.subject?.title && (
                      <span className="text-xs text-outline truncate hidden sm:inline">
                        — {session.subject.title}
                      </span>
                    )}
                  </div>
                  <div className="flex items-center gap-2 text-xs text-outline font-label-md mt-0.5">
                    <span>{formatArticleDate(session.ended_at || session.started_at)}</span>
                    <span>•</span>
                    <span>{formatActiveDuration(session.active_duration_seconds)}</span>
                  </div>
                </div>
              </div>

              {/* Metric tag */}
              <div className="shrink-0 flex items-center gap-2 self-end sm:self-auto">
                {(session.derived_metrics?.bead_count !== undefined || session.progress_data?.bead_count !== undefined) && (
                  <span className="px-3 py-1 rounded-full bg-primary/10 text-primary font-label-md text-xs font-medium">
                    {formatNumberVi(session.derived_metrics?.bead_count ?? session.progress_data?.bead_count)} hạt
                    {session.derived_metrics?.completed_rounds ? ` (${session.derived_metrics.completed_rounds} vòng)` : ""}
                  </span>
                )}
                {(session.derived_metrics?.repetition_count !== undefined || session.progress_data?.repetition_count !== undefined) && (
                  <span className="px-3 py-1 rounded-full bg-primary/10 text-primary font-label-md text-xs font-medium">
                    {formatNumberVi(session.derived_metrics?.repetition_count ?? session.progress_data?.repetition_count)} biến
                  </span>
                )}
                <span className="px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-700 font-label-md text-[11px] font-semibold flex items-center gap-1">
                  <span className="material-symbols-outlined text-[13px]">
                    check
                  </span>
                  <span>Hoàn thành</span>
                </span>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
