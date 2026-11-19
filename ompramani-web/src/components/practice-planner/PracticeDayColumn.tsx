'use client';

import React from 'react';
import { PracticePlanOccurrence } from '@/types/practice-plan';
import { PracticeActivityCard } from './PracticeActivityCard';

interface PracticeDayColumnProps {
  dateStr: string; // YYYY-MM-DD
  isToday: boolean;
  occurrences: PracticePlanOccurrence[];
  onLinkSession: (occurrence: PracticePlanOccurrence) => void;
  onUnlinkSession: (occurrence: PracticePlanOccurrence) => void;
  onSkip: (occurrence: PracticePlanOccurrence) => void;
  onRestore: (occurrence: PracticePlanOccurrence) => void;
  isOperating?: boolean;
}

export function PracticeDayColumn({
  dateStr,
  isToday,
  occurrences,
  onLinkSession,
  onUnlinkSession,
  onSkip,
  onRestore,
  isOperating = false,
}: PracticeDayColumnProps) {
  const dateObj = new Date(`${dateStr}T00:00:00`);

  const weekdayNames = [
    'Chủ Nhật',
    'Thứ Hai',
    'Thứ Ba',
    'Thứ Tư',
    'Thứ Năm',
    'Thứ Sáu',
    'Thứ Bảy',
  ];
  const weekdayName = weekdayNames[dateObj.getDay()];

  const formattedDate = dateObj.toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
  });

  return (
    <div
      className={`flex flex-col rounded-3xl border transition-all ${
        isToday
          ? 'bg-primary/5 border-primary/40 shadow-2xs'
          : 'bg-surface-container-low/40 border-outline-variant/30'
      }`}
    >
      {/* Column Date Header */}
      <div
        className={`px-4 py-3 rounded-t-3xl flex items-center justify-between border-b ${
          isToday
            ? 'bg-primary/10 border-primary/30 text-primary'
            : 'bg-surface-container-low border-outline-variant/20 text-on-surface'
        }`}
      >
        <div className="flex items-center gap-2">
          <span className="font-serif text-sm font-bold">{weekdayName}</span>
          <span className="text-xs text-outline font-label-md">
            ({formattedDate})
          </span>
        </div>

        {isToday && (
          <span className="px-2 py-0.5 rounded-full bg-primary text-on-primary text-[10px] font-label-sm font-bold uppercase tracking-wider">
            Hôm nay
          </span>
        )}
      </div>

      {/* Occurrences List */}
      <div className="p-3 flex-1 flex flex-col gap-3 min-h-[140px]">
        {occurrences.length === 0 ? (
          <div className="flex-1 flex flex-col items-center justify-center p-4 text-center text-outline">
            <span className="material-symbols-outlined text-[20px] opacity-40">
              spa
            </span>
            <span className="text-[11px] font-body-sm mt-1 opacity-60">
              Không có thời khóa
            </span>
          </div>
        ) : (
          occurrences.map((occ) => (
            <PracticeActivityCard
              key={occ.occurrence_id}
              occurrence={occ}
              onLinkSession={onLinkSession}
              onUnlinkSession={onUnlinkSession}
              onSkip={onSkip}
              onRestore={onRestore}
              isOperating={isOperating}
            />
          ))
        )}
      </div>
    </div>
  );
}
