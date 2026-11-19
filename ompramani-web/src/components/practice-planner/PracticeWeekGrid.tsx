'use client';

import React, { useState } from 'react';
import { PracticePlanOccurrence } from '@/types/practice-plan';
import { PracticeDayColumn } from './PracticeDayColumn';

interface PracticeWeekGridProps {
  occurrences: PracticePlanOccurrence[];
  week: { from: string; to: string; today: string };
  onPrevWeek: () => void;
  onNextWeek: () => void;
  onTodayWeek: () => void;
  onLinkSession: (occurrence: PracticePlanOccurrence) => void;
  onUnlinkSession: (occurrence: PracticePlanOccurrence) => void;
  onSkip: (occurrence: PracticePlanOccurrence) => void;
  onRestore: (occurrence: PracticePlanOccurrence) => void;
  isOperating?: boolean;
}

export function PracticeWeekGrid({
  occurrences,
  week,
  onPrevWeek,
  onNextWeek,
  onTodayWeek,
  onLinkSession,
  onUnlinkSession,
  onSkip,
  onRestore,
  isOperating = false,
}: PracticeWeekGridProps) {
  // Generate the 7 calendar days of the week from `week.from` to `week.to`
  const days: string[] = [];
  const start = new Date(`${week.from}T00:00:00`);
  for (let i = 0; i < 7; i++) {
    const cur = new Date(start);
    cur.setDate(start.getDate() + i);
    days.push(cur.toISOString().split('T')[0]);
  }

  // Mobile selected day state
  const [selectedMobileDay, setSelectedMobileDay] = useState<string>(() => {
    return days.includes(week.today) ? week.today : days[0];
  });

  // Group occurrences by local_date
  const occurrencesByDate = occurrences.reduce<
    Record<string, PracticePlanOccurrence[]>
  >((acc, occ) => {
    if (!acc[occ.local_date]) {
      acc[occ.local_date] = [];
    }
    acc[occ.local_date].push(occ);
    return acc;
  }, {});

  const formatShortDate = (dateStr: string): string => {
    const [y, m, d] = dateStr.split('-');
    return `${d}/${m}/${y}`;
  };

  const weekdayPills = [
    { label: 'T2', index: 0 },
    { label: 'T3', index: 1 },
    { label: 'T4', index: 2 },
    { label: 'T5', index: 3 },
    { label: 'T6', index: 4 },
    { label: 'T7', index: 5 },
    { label: 'CN', index: 6 },
  ];

  return (
    <div className="space-y-4">
      {/* Navigation header for the week */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-3xl bg-surface-container-lowest border border-outline-variant/30">
        <div className="flex items-center gap-2">
          <span className="material-symbols-outlined text-[20px] text-primary">
            date_range
          </span>
          <span className="font-serif text-sm sm:text-base font-semibold text-on-surface">
            Thời khóa: {formatShortDate(week.from)} – {formatShortDate(week.to)}
          </span>
        </div>

        <div className="flex items-center gap-2 self-end sm:self-auto">
          <button
            type="button"
            onClick={onPrevWeek}
            disabled={isOperating}
            className="p-1.5 rounded-xl border border-outline-variant/40 hover:bg-surface-container text-on-surface transition-colors cursor-pointer"
            title="Tuần trước"
            aria-label="Tuần trước"
          >
            <span className="material-symbols-outlined text-[18px]">chevron_left</span>
          </button>

          <button
            type="button"
            onClick={onTodayWeek}
            disabled={isOperating}
            className="px-3 py-1 rounded-xl text-xs font-label-md font-semibold bg-surface-container-high hover:bg-surface-container-highest text-on-surface transition-colors cursor-pointer"
          >
            Tuần này
          </button>

          <button
            type="button"
            onClick={onNextWeek}
            disabled={isOperating}
            className="p-1.5 rounded-xl border border-outline-variant/40 hover:bg-surface-container text-on-surface transition-colors cursor-pointer"
            title="Tuần sau"
            aria-label="Tuần sau"
          >
            <span className="material-symbols-outlined text-[18px]">chevron_right</span>
          </button>
        </div>
      </div>

      {/* Mobile: Weekday Selector Pills */}
      <div className="lg:hidden flex items-center justify-between gap-1 p-2 rounded-2xl bg-surface-container-low border border-outline-variant/30 overflow-x-auto">
        {weekdayPills.map((p) => {
          const dStr = days[p.index] || '';
          const isSelected = selectedMobileDay === dStr;
          const isDayToday = dStr === week.today;
          const count = occurrencesByDate[dStr]?.length || 0;

          return (
            <button
              key={p.label}
              type="button"
              onClick={() => setSelectedMobileDay(dStr)}
              className={`flex-1 py-2 px-1 rounded-xl text-center transition-all cursor-pointer ${
                isSelected
                  ? 'bg-primary text-on-primary font-bold shadow-2xs'
                  : 'bg-transparent text-on-surface-variant hover:bg-surface-container'
              }`}
            >
              <div className="text-xs font-label-md">{p.label}</div>
              <div className="text-[10px] mt-0.5 opacity-80">
                {count > 0 ? `${count} khóa` : '-'}
              </div>
              {isDayToday && (
                <div
                  className={`w-1.5 h-1.5 rounded-full mx-auto mt-1 ${
                    isSelected ? 'bg-white' : 'bg-primary'
                  }`}
                />
              )}
            </button>
          );
        })}
      </div>

      {/* Mobile: Single Selected Day Column */}
      <div className="lg:hidden">
        {selectedMobileDay && (
          <PracticeDayColumn
            dateStr={selectedMobileDay}
            isToday={selectedMobileDay === week.today}
            occurrences={occurrencesByDate[selectedMobileDay] || []}
            onLinkSession={onLinkSession}
            onUnlinkSession={onUnlinkSession}
            onSkip={onSkip}
            onRestore={onRestore}
            isOperating={isOperating}
          />
        )}
      </div>

      {/* Desktop: 7 Days Grid */}
      <div className="hidden lg:grid lg:grid-cols-7 gap-3">
        {days.map((dStr) => (
          <PracticeDayColumn
            key={dStr}
            dateStr={dStr}
            isToday={dStr === week.today}
            occurrences={occurrencesByDate[dStr] || []}
            onLinkSession={onLinkSession}
            onUnlinkSession={onUnlinkSession}
            onSkip={onSkip}
            onRestore={onRestore}
            isOperating={isOperating}
          />
        ))}
      </div>
    </div>
  );
}
