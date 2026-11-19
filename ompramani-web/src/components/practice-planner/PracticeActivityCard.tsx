'use client';

import React from 'react';
import Link from 'next/link';
import { PracticePlanOccurrence } from '@/types/practice-plan';
import { PracticeType } from '@/types/practice';

interface PracticeActivityCardProps {
  occurrence: PracticePlanOccurrence;
  onLinkSession: (occurrence: PracticePlanOccurrence) => void;
  onUnlinkSession: (occurrence: PracticePlanOccurrence) => void;
  onSkip: (occurrence: PracticePlanOccurrence) => void;
  onRestore: (occurrence: PracticePlanOccurrence) => void;
  isOperating?: boolean;
}

export function PracticeActivityCard({
  occurrence,
  onLinkSession,
  onUnlinkSession,
  onSkip,
  onRestore,
  isOperating = false,
}: PracticeActivityCardProps) {
  const getPracticeIcon = (type: PracticeType): string => {
    switch (type) {
      case 'meditation':
        return 'self_improvement';
      case 'mala':
        return 'adjust';
      case 'bell_wooden_fish':
        return 'notifications';
      case 'buddha_name_recitation':
        return 'record_voice_over';
      case 'mantra_recitation':
        return 'menu_book';
      case 'sutra_reading':
      case 'sutra_recitation':
        return 'auto_stories';
      default:
        return 'self_improvement';
    }
  };

  const isPracticed = occurrence.status === 'practiced';
  const isSkipped = occurrence.status === 'skipped';
  const isPlanned = occurrence.status === 'planned';

  const formatDuration = (seconds: number): string => {
    const mins = Math.floor(seconds / 60);
    if (mins < 60) return `${mins} phút`;
    const hours = Math.floor(mins / 60);
    const remMins = mins % 60;
    return remMins > 0 ? `${hours} giờ ${remMins} phút` : `${hours} giờ`;
  };

  return (
    <div
      className={`group relative p-4 rounded-3xl border transition-all ${
        isPracticed
          ? 'bg-emerald-500/5 border-emerald-500/30'
          : isSkipped
          ? 'bg-surface-container-low/50 border-dashed border-outline-variant/60 opacity-75'
          : 'bg-surface-container-lowest border-outline-variant/30 hover:border-primary/40 hover:shadow-xs'
      }`}
    >
      <div className="flex items-start justify-between gap-3">
        {/* Practice Icon & Titles */}
        <div className="flex items-start gap-3 flex-1 min-w-0">
          <div
            className={`w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 ${
              isPracticed
                ? 'bg-emerald-500/15 text-emerald-700'
                : isSkipped
                ? 'bg-surface-container text-outline'
                : 'bg-primary/10 text-primary'
            }`}
          >
            <span className="material-symbols-outlined text-[22px]">
              {getPracticeIcon(occurrence.practice_type)}
            </span>
          </div>

          <div className="flex-1 min-w-0">
            <div className="flex items-center gap-2 flex-wrap">
              <h4 className="font-serif text-sm sm:text-base font-semibold text-on-surface truncate">
                {occurrence.title}
              </h4>
              <span
                className={`text-[11px] font-label-sm font-semibold px-2 py-0.5 rounded-full ${
                  isPracticed
                    ? 'bg-emerald-500/15 text-emerald-700'
                    : isSkipped
                    ? 'bg-surface-container-high text-outline'
                    : 'bg-primary/15 text-primary'
                }`}
              >
                {occurrence.status_label}
              </span>
            </div>

            <div className="flex items-center gap-2 text-xs text-on-surface-variant mt-1 flex-wrap font-label-md">
              <span className="flex items-center gap-1 text-outline">
                <span className="material-symbols-outlined text-[14px]">
                  schedule
                </span>
                <span>{occurrence.local_time || 'Thời gian linh hoạt'}</span>
              </span>

              {/* Target info */}
              {occurrence.target.duration_seconds && (
                <span className="text-outline">
                  • Dự kiến: {formatDuration(occurrence.target.duration_seconds)}
                </span>
              )}
              {occurrence.target.repetitions && (
                <span className="text-outline">
                  • Mục tiêu: {occurrence.target.repetitions.toLocaleString('vi-VN')} biến
                </span>
              )}
            </div>

            {/* Actual Recorded Practice Info if Practiced */}
            {isPracticed && occurrence.actual && (
              <div className="mt-2.5 p-2 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-900 text-xs font-body-sm flex items-center gap-2">
                <span className="material-symbols-outlined text-[16px] text-emerald-700">
                  verified
                </span>
                <span>
                  Đã thực hành: {formatDuration(occurrence.actual.duration_seconds)}
                  {occurrence.actual.repetition_count !== null &&
                    ` • ${occurrence.actual.repetition_count.toLocaleString('vi-VN')} biến`}
                </span>
              </div>
            )}

            {/* Skipped note */}
            {isSkipped && (
              <p className="mt-2 text-xs text-outline italic">
                Thời khóa này đã được bỏ qua trong an tĩnh, không ảnh hưởng chuỗi tu tập.
              </p>
            )}
          </div>
        </div>
      </div>

      {/* Action Bar */}
      <div className="mt-3 pt-2.5 border-t border-outline-variant/20 flex items-center justify-between gap-2 flex-wrap">
        <Link
          href={occurrence.destination_url}
          className="inline-flex items-center gap-1.5 text-xs font-label-md font-semibold text-primary hover:text-primary-variant hover:underline"
        >
          <span>Vào công cụ thực hành</span>
          <span className="material-symbols-outlined text-[15px]">arrow_forward</span>
        </Link>

        <div className="flex items-center gap-1.5 ml-auto">
          {/* If Planned: Link session OR Skip */}
          {isPlanned && (
            <>
              <button
                type="button"
                onClick={() => onLinkSession(occurrence)}
                disabled={isOperating}
                className="px-2.5 py-1 rounded-xl text-xs font-label-md font-semibold bg-surface-container-high hover:bg-surface-container-highest text-on-surface transition-colors cursor-pointer flex items-center gap-1"
                title="Ghi nhận buổi thực hành đã hoàn thành"
              >
                <span className="material-symbols-outlined text-[15px]">link</span>
                <span className="hidden sm:inline">Liên kết buổi tu tập</span>
                <span className="sm:hidden">Liên kết</span>
              </button>

              <button
                type="button"
                onClick={() => onSkip(occurrence)}
                disabled={isOperating}
                className="px-2 py-1 rounded-xl text-xs font-label-md text-outline hover:text-rose-600 hover:bg-rose-500/10 transition-colors cursor-pointer flex items-center gap-1"
                title="Bỏ qua thời khóa này"
              >
                <span className="material-symbols-outlined text-[15px]">event_busy</span>
                <span className="hidden sm:inline">Bỏ qua</span>
              </button>
            </>
          )}

          {/* If Practiced: Unlink */}
          {isPracticed && (
            <button
              type="button"
              onClick={() => onUnlinkSession(occurrence)}
              disabled={isOperating}
              className="px-2 py-1 rounded-xl text-xs font-label-md text-outline hover:text-amber-700 hover:bg-amber-500/10 transition-colors cursor-pointer flex items-center gap-1"
              title="Gỡ liên kết buổi thực hành này"
            >
              <span className="material-symbols-outlined text-[15px]">link_off</span>
              <span>Gỡ liên kết</span>
            </button>
          )}

          {/* If Skipped: Restore */}
          {isSkipped && (
            <button
              type="button"
              onClick={() => onRestore(occurrence)}
              disabled={isOperating}
              className="px-2.5 py-1 rounded-xl text-xs font-label-md font-semibold bg-primary text-on-primary hover:bg-primary-variant transition-colors cursor-pointer flex items-center gap-1"
              title="Khôi phục lại hoạt động này"
            >
              <span className="material-symbols-outlined text-[15px]">undo</span>
              <span>Khôi phục</span>
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
