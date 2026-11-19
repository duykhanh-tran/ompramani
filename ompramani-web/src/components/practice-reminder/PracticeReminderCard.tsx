'use client';

import React from 'react';
import Link from 'next/link';
import { PracticeReminder } from '@/types/practice-reminder';
import { PracticeType } from '@/types/practice';

interface PracticeReminderCardProps {
  reminder: PracticeReminder;
  onEdit: (reminder: PracticeReminder) => void;
  onDelete: (id: number) => void;
  onTogglePause: (reminder: PracticeReminder) => void;
  isToggling?: boolean;
}

export function PracticeReminderCard({
  reminder,
  onEdit,
  onDelete,
  onTogglePause,
  isToggling = false,
}: PracticeReminderCardProps) {
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
        return 'alarm';
    }
  };

  const isEnabled = reminder.is_enabled;

  return (
    <div
      className={`group relative p-5 rounded-3xl border transition-all ${
        isEnabled
          ? 'bg-surface-container-lowest border-outline-variant/40 hover:border-primary/40 hover:shadow-xs'
          : 'bg-surface-container-low/40 border-dashed border-outline-variant/60 opacity-80'
      }`}
    >
      <div className="flex items-start justify-between gap-4">
        {/* Practice Type & Title Info */}
        <div className="flex items-start gap-3.5 flex-1 min-w-0">
          <div
            className={`w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 ${
              isEnabled
                ? 'bg-primary/10 text-primary'
                : 'bg-surface-container text-outline'
            }`}
          >
            <span className="material-symbols-outlined text-[24px]">
              {getPracticeIcon(reminder.practice_type)}
            </span>
          </div>

          <div className="flex-1 min-w-0">
            <div className="flex items-center gap-2 flex-wrap">
              <h3 className="font-serif text-base sm:text-lg font-semibold text-on-surface truncate">
                {reminder.title || reminder.practice_label}
              </h3>
              <span
                className={`text-[11px] font-label-sm font-semibold px-2 py-0.5 rounded-full ${
                  isEnabled
                    ? 'bg-primary/15 text-primary'
                    : 'bg-surface-container-high text-outline'
                }`}
              >
                {isEnabled ? 'Đang bật' : 'Tạm dừng'}
              </span>
            </div>

            <p className="text-xs text-on-surface-variant mt-0.5 font-label-md">
              {reminder.practice_label} • {reminder.schedule_description}
            </p>

            {/* Next occurrence */}
            <div className="flex items-center gap-1.5 mt-2 text-xs font-body-sm text-outline">
              <span className="material-symbols-outlined text-[15px] text-tertiary">
                schedule
              </span>
              <span>
                {isEnabled && reminder.next_due_local
                  ? `Nhắc kế tiếp: ${reminder.next_due_local} (${reminder.timezone})`
                  : isEnabled && !reminder.next_due_local
                  ? 'Lời nhắc đã kết thúc'
                  : 'Đang tạm dừng — không gửi thông báo'}
              </span>
            </div>
          </div>
        </div>

        {/* Actions Dropdown / Quick buttons */}
        <div className="flex items-center gap-1 shrink-0">
          <button
            type="button"
            onClick={() => onTogglePause(reminder)}
            disabled={isToggling}
            className={`p-2 rounded-xl transition-colors cursor-pointer ${
              isEnabled
                ? 'text-outline hover:text-amber-700 hover:bg-amber-500/10'
                : 'text-primary hover:text-primary hover:bg-primary/10'
            }`}
            title={isEnabled ? 'Tạm dừng lời nhắc' : 'Kích hoạt lại'}
            aria-label={isEnabled ? 'Tạm dừng lời nhắc' : 'Kích hoạt lại'}
          >
            <span className="material-symbols-outlined text-[20px]">
              {isEnabled ? 'pause_circle' : 'play_circle'}
            </span>
          </button>

          <button
            type="button"
            onClick={() => onEdit(reminder)}
            className="p-2 rounded-xl text-outline hover:text-primary hover:bg-surface-container-high transition-colors cursor-pointer"
            title="Chỉnh sửa lời nhắc"
            aria-label="Chỉnh sửa lời nhắc"
          >
            <span className="material-symbols-outlined text-[20px]">edit</span>
          </button>

          <button
            type="button"
            onClick={() => onDelete(reminder.id)}
            className="p-2 rounded-xl text-outline hover:text-rose-600 hover:bg-rose-500/10 transition-colors cursor-pointer"
            title="Xóa lời nhắc"
            aria-label="Xóa lời nhắc"
          >
            <span className="material-symbols-outlined text-[20px]">delete</span>
          </button>
        </div>
      </div>

      {/* Bottom bar: Go to Practice */}
      <div className="mt-4 pt-3 border-t border-outline-variant/20 flex items-center justify-between">
        <span className="text-[11px] font-label-sm text-outline">
          Múi giờ: {reminder.timezone}
        </span>
        <Link
          href={reminder.destination_url}
          className="inline-flex items-center gap-1.5 text-xs font-label-md font-semibold text-primary hover:text-primary-variant hover:underline"
        >
          <span>Vào thời khóa thực hành</span>
          <span className="material-symbols-outlined text-[16px]">
            arrow_forward
          </span>
        </Link>
      </div>
    </div>
  );
}
