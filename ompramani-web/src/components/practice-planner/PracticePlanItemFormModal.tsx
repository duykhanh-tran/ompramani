'use client';

import React, { useState } from 'react';
import {
  PracticePlanItem,
  CreatePracticePlanItemInput,
  UpdatePracticePlanItemInput,
  PracticePlanScheduleType,
} from '@/types/practice-plan';
import { PracticeType } from '@/types/practice';

interface PracticePlanItemFormModalProps {
  isOpen: boolean;
  onClose: () => void;
  planId: number;
  itemToEdit: PracticePlanItem | null;
  onSave: (
    data: CreatePracticePlanItemInput | UpdatePracticePlanItemInput
  ) => Promise<void>;
  isSubmitting: boolean;
}

const PRACTICE_TYPES: { value: PracticeType; label: string; icon: string }[] = [
  { value: 'meditation', label: 'Thiền định', icon: 'self_improvement' },
  { value: 'mala', label: 'Tràng hạt niệm Phật', icon: 'adjust' },
  { value: 'bell_wooden_fish', label: 'Chuông & Mõ', icon: 'notifications' },
  { value: 'buddha_name_recitation', label: 'Niệm Phật', icon: 'record_voice_over' },
  { value: 'mantra_recitation', label: 'Trì chú', icon: 'menu_book' },
  { value: 'sutra_reading', label: 'Đọc kinh', icon: 'auto_stories' },
  { value: 'sutra_recitation', label: 'Tụng kinh', icon: 'import_contacts' },
];

const WEEKDAY_OPTIONS = [
  { day: 1, label: 'T2', name: 'Thứ Hai' },
  { day: 2, label: 'T3', name: 'Thứ Ba' },
  { day: 3, label: 'T4', name: 'Thứ Tư' },
  { day: 4, label: 'T5', name: 'Thứ Năm' },
  { day: 5, label: 'T6', name: 'Thứ Sáu' },
  { day: 6, label: 'T7', name: 'Thứ Bảy' },
  { day: 7, label: 'CN', name: 'Chủ Nhật' },
];

function PracticePlanItemFormContent({
  onClose,
  itemToEdit,
  onSave,
  isSubmitting,
}: {
  onClose: () => void;
  itemToEdit: PracticePlanItem | null;
  onSave: (
    data: CreatePracticePlanItemInput | UpdatePracticePlanItemInput
  ) => Promise<void>;
  isSubmitting: boolean;
}) {
  const [practiceType, setPracticeType] = useState<PracticeType>(
    itemToEdit?.practice_type || 'meditation'
  );
  const [title, setTitle] = useState(itemToEdit?.title || '');
  const [scheduleType, setScheduleType] = useState<PracticePlanScheduleType>(
    itemToEdit?.schedule_type || 'weekly'
  );
  const [localTime, setLocalTime] = useState(itemToEdit?.local_time || '06:00');
  const [hasSpecificTime, setHasSpecificTime] = useState(
    itemToEdit ? itemToEdit.local_time !== null : true
  );

  const [localDate, setLocalDate] = useState(() => {
    if (itemToEdit?.local_date) return itemToEdit.local_date;
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    return tomorrow.toISOString().split('T')[0];
  });

  const [weekdays, setWeekdays] = useState<number[]>(
    itemToEdit?.weekdays?.length ? itemToEdit.weekdays : [1, 2, 3, 4, 5, 6, 7]
  );

  // Targets
  const [targetMinutes, setTargetMinutes] = useState(() => {
    if (itemToEdit?.target_duration_seconds) {
      return Math.round(itemToEdit.target_duration_seconds / 60).toString();
    }
    return '20';
  });

  const [targetReps, setTargetReps] = useState(() => {
    if (itemToEdit?.target_repetitions) {
      return itemToEdit.target_repetitions.toString();
    }
    return '108';
  });

  // Opt-in reminder checkbox
  const [createReminder, setCreateReminder] = useState(
    itemToEdit ? itemToEdit.has_reminder : false
  );

  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  const toggleWeekday = (day: number) => {
    if (weekdays.includes(day)) {
      if (weekdays.length === 1) return;
      setWeekdays(weekdays.filter((d) => d !== day));
    } else {
      setWeekdays([...weekdays, day].sort());
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg(null);

    if (scheduleType === 'once' && !localDate) {
      setErrorMsg('Vui lòng chọn ngày thực hành.');
      return;
    }

    if (scheduleType === 'weekly' && weekdays.length === 0) {
      setErrorMsg('Vui lòng chọn ít nhất một thứ trong tuần.');
      return;
    }

    const timeValue = hasSpecificTime && localTime ? localTime : null;

    if (createReminder && !timeValue) {
      setErrorMsg('Để tạo lời nhắc thông báo, bạn cần chọn giờ thực hành cụ thể.');
      return;
    }

    let targetDurationSeconds: number | null = null;
    let targetRepetitions: number | null = null;

    if (practiceType === 'meditation' && targetMinutes) {
      const mins = parseInt(targetMinutes, 10);
      if (mins > 0) {
        targetDurationSeconds = mins * 60;
      }
    }

    if (
      ['mala', 'mantra_recitation', 'buddha_name_recitation'].includes(
        practiceType
      ) &&
      targetReps
    ) {
      const reps = parseInt(targetReps, 10);
      if (reps > 0) {
        targetRepetitions = reps;
      }
    }

    try {
      const payload: CreatePracticePlanItemInput = {
        practice_type: practiceType,
        title: title.trim() || null,
        schedule_type: scheduleType,
        local_date: scheduleType === 'once' ? localDate : null,
        local_time: timeValue,
        weekdays: scheduleType === 'weekly' ? weekdays : [],
        target_duration_seconds: targetDurationSeconds,
        target_repetitions: targetRepetitions,
        create_reminder: createReminder,
      };

      await onSave(payload);
      onClose();
    } catch (err: unknown) {
      if (err instanceof Error) {
        setErrorMsg(err.message);
      } else {
        setErrorMsg('Đã có lỗi xảy ra khi lưu hoạt động tu tập.');
      }
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs animate-in fade-in duration-200">
      <div
        className="w-full max-w-lg rounded-3xl bg-surface-container-lowest border border-outline-variant/30 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="plan-item-modal-title"
      >
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-outline-variant/20 bg-surface-container-low/50">
          <div className="flex items-center gap-2.5">
            <span className="material-symbols-outlined text-[22px] text-primary">
              playlist_add
            </span>
            <h2
              id="plan-item-modal-title"
              className="font-serif text-lg font-semibold text-on-surface"
            >
              {itemToEdit ? 'Chỉnh sửa hoạt động tu tập' : 'Thêm hoạt động tu tập mới'}
            </h2>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="p-1 rounded-full text-outline hover:text-on-surface hover:bg-surface-container transition-colors cursor-pointer"
            aria-label="Đóng"
          >
            <span className="material-symbols-outlined text-[20px]">close</span>
          </button>
        </div>

        {/* Scrollable Form Body */}
        <form onSubmit={handleSubmit} className="flex-1 overflow-y-auto p-6 space-y-5">
          {errorMsg && (
            <div className="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 text-xs font-label-md flex items-center gap-2">
              <span className="material-symbols-outlined text-[18px]">error</span>
              <span>{errorMsg}</span>
            </div>
          )}

          {/* Practice Type */}
          <div>
            <label className="block text-xs font-label-md font-semibold text-on-surface mb-2">
              Chọn pháp môn / công cụ thực hành
            </label>
            <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
              {PRACTICE_TYPES.map((pt) => {
                const isSelected = practiceType === pt.value;
                return (
                  <button
                    key={pt.value}
                    type="button"
                    onClick={() => setPracticeType(pt.value)}
                    className={`flex items-center gap-2 p-2.5 rounded-2xl border text-left text-xs font-label-md transition-all cursor-pointer ${
                      isSelected
                        ? 'border-primary bg-primary/10 text-primary font-bold shadow-2xs'
                        : 'border-outline-variant/30 bg-surface-container-low text-on-surface hover:bg-surface-container'
                    }`}
                  >
                    <span className="material-symbols-outlined text-[18px]">
                      {pt.icon}
                    </span>
                    <span className="truncate">{pt.label}</span>
                  </button>
                );
              })}
            </div>
          </div>

          {/* Title */}
          <div>
            <label
              htmlFor="item-title"
              className="block text-xs font-label-md font-semibold text-on-surface mb-1.5"
            >
              Tên hoạt động <span className="font-normal text-outline">(tùy chọn)</span>
            </label>
            <input
              id="item-title"
              type="text"
              maxLength={150}
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              placeholder="VD: Thời thiền an lạc sáng, Niệm Phật 108 biến..."
              className="w-full px-3.5 py-2.5 rounded-2xl border border-outline-variant/40 bg-surface text-on-surface text-xs font-body-md focus:outline-none focus:border-primary transition-colors"
            />
          </div>

          {/* Schedule Type */}
          <div>
            <label className="block text-xs font-label-md font-semibold text-on-surface mb-2">
              Tần suất thực hiện
            </label>
            <div className="grid grid-cols-2 gap-2">
              <button
                type="button"
                onClick={() => setScheduleType('weekly')}
                className={`py-2 px-3 rounded-2xl border text-center text-xs font-label-md transition-all cursor-pointer ${
                  scheduleType === 'weekly'
                    ? 'border-primary bg-primary text-on-primary font-bold shadow-2xs'
                    : 'border-outline-variant/40 bg-surface-container-low text-on-surface hover:bg-surface-container'
                }`}
              >
                Hằng tuần
              </button>
              <button
                type="button"
                onClick={() => setScheduleType('once')}
                className={`py-2 px-3 rounded-2xl border text-center text-xs font-label-md transition-all cursor-pointer ${
                  scheduleType === 'once'
                    ? 'border-primary bg-primary text-on-primary font-bold shadow-2xs'
                    : 'border-outline-variant/40 bg-surface-container-low text-on-surface hover:bg-surface-container'
                }`}
              >
                Một lần
              </button>
            </div>
          </div>

          {/* If Weekly: Weekday Pills */}
          {scheduleType === 'weekly' && (
            <div>
              <label className="block text-xs font-label-md font-semibold text-on-surface mb-2">
                Các thứ trong tuần
              </label>
              <div className="flex items-center gap-1.5 flex-wrap">
                {WEEKDAY_OPTIONS.map((w) => {
                  const isChecked = weekdays.includes(w.day);
                  return (
                    <button
                      key={w.day}
                      type="button"
                      onClick={() => toggleWeekday(w.day)}
                      className={`w-9 h-9 rounded-xl text-xs font-label-md font-bold transition-all cursor-pointer ${
                        isChecked
                          ? 'bg-primary text-on-primary shadow-2xs'
                          : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high'
                      }`}
                      title={w.name}
                    >
                      {w.label}
                    </button>
                  );
                })}
              </div>
            </div>
          )}

          {/* If Once: Date Picker */}
          {scheduleType === 'once' && (
            <div>
              <label
                htmlFor="item-date"
                className="block text-xs font-label-md font-semibold text-on-surface mb-1.5"
              >
                Ngày thực hành
              </label>
              <input
                id="item-date"
                type="date"
                required
                value={localDate}
                onChange={(e) => setLocalDate(e.target.value)}
                className="w-full px-3.5 py-2.5 rounded-2xl border border-outline-variant/40 bg-surface text-on-surface text-xs font-body-md focus:outline-none focus:border-primary transition-colors"
              />
            </div>
          )}

          {/* Time Picker & Flexible Time Toggle */}
          <div className="p-4 rounded-2xl bg-surface-container-low border border-outline-variant/20 space-y-3">
            <div className="flex items-center justify-between">
              <div>
                <label className="text-xs font-label-md font-semibold text-on-surface block">
                  Giờ thực hành cụ thể
                </label>
                <span className="text-[11px] text-outline">
                  Tắt nếu muốn thời gian linh hoạt trong ngày
                </span>
              </div>
              <label className="relative inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  checked={hasSpecificTime}
                  onChange={(e) => setHasSpecificTime(e.target.checked)}
                  className="sr-only peer"
                />
                <div className="w-9 h-5 bg-surface-container-highest peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary" />
              </label>
            </div>

            {hasSpecificTime && (
              <div>
                <input
                  type="time"
                  required
                  value={localTime}
                  onChange={(e) => setLocalTime(e.target.value)}
                  className="w-full px-3.5 py-2 rounded-xl border border-outline-variant/40 bg-surface text-on-surface text-xs font-body-md focus:outline-none focus:border-primary"
                />
              </div>
            )}
          </div>

          {/* Targets based on Practice Type */}
          {practiceType === 'meditation' && (
            <div>
              <label
                htmlFor="target-mins"
                className="block text-xs font-label-md font-semibold text-on-surface mb-1.5"
              >
                Mục tiêu thời lượng (phút) <span className="font-normal text-outline">(tùy chọn)</span>
              </label>
              <input
                id="target-mins"
                type="number"
                min={1}
                max={1440}
                value={targetMinutes}
                onChange={(e) => setTargetMinutes(e.target.value)}
                placeholder="VD: 15, 20, 30..."
                className="w-full px-3.5 py-2.5 rounded-2xl border border-outline-variant/40 bg-surface text-on-surface text-xs font-body-md focus:outline-none focus:border-primary transition-colors"
              />
            </div>
          )}

          {['mala', 'mantra_recitation', 'buddha_name_recitation'].includes(
            practiceType
          ) && (
            <div>
              <label
                htmlFor="target-reps"
                className="block text-xs font-label-md font-semibold text-on-surface mb-1.5"
              >
                Mục tiêu số lần / biến niệm <span className="font-normal text-outline">(tùy chọn)</span>
              </label>
              <input
                id="target-reps"
                type="number"
                min={1}
                max={100000}
                value={targetReps}
                onChange={(e) => setTargetReps(e.target.value)}
                placeholder="VD: 108, 300, 1000..."
                className="w-full px-3.5 py-2.5 rounded-2xl border border-outline-variant/40 bg-surface text-on-surface text-xs font-body-md focus:outline-none focus:border-primary transition-colors"
              />
            </div>
          )}

          {/* Opt-in Reminder Checkbox (Task 36) */}
          {hasSpecificTime && (
            <div className="flex items-start gap-3 p-3.5 rounded-2xl bg-surface-container-low border border-outline-variant/20">
              <input
                id="opt-reminder"
                type="checkbox"
                checked={createReminder}
                onChange={(e) => setCreateReminder(e.target.checked)}
                className="mt-0.5 w-4 h-4 text-primary rounded focus:ring-primary"
              />
              <label htmlFor="opt-reminder" className="text-xs leading-relaxed cursor-pointer">
                <span className="font-semibold text-on-surface block">
                  Bật lời nhắc thông báo cho thời khóa này
                </span>
                <span className="text-outline text-[11px] block mt-0.5">
                  Tự động tích hợp vào hệ thống Nhắc Nhở Tu Tập (Task 36) và gửi thông báo an tĩnh vào Hộp thư cá nhân.
                </span>
              </label>
            </div>
          )}

          {/* Footer Actions */}
          <div className="pt-2 flex items-center justify-end gap-3 border-t border-outline-variant/20">
            <button
              type="button"
              onClick={onClose}
              disabled={isSubmitting}
              className="px-4 py-2 rounded-2xl border border-outline-variant/40 text-xs font-label-md font-semibold text-on-surface hover:bg-surface-container transition-colors cursor-pointer"
            >
              Hủy
            </button>
            <button
              type="submit"
              disabled={isSubmitting}
              className="px-5 py-2 rounded-2xl bg-primary text-on-primary text-xs font-label-md font-bold shadow-xs hover:bg-primary-variant transition-colors flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
            >
              {isSubmitting ? (
                <>
                  <span className="w-3.5 h-3.5 border-2 border-on-primary border-t-transparent rounded-full animate-spin" />
                  <span>Đang lưu...</span>
                </>
              ) : (
                <>
                  <span className="material-symbols-outlined text-[16px]">check</span>
                  <span>{itemToEdit ? 'Lưu thay đổi' : 'Thêm hoạt động'}</span>
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export function PracticePlanItemFormModal(
  props: PracticePlanItemFormModalProps
) {
  if (!props.isOpen) return null;

  return (
    <PracticePlanItemFormContent
      key={props.itemToEdit ? `edit-item-${props.itemToEdit.id}` : 'create-item'}
      {...props}
    />
  );
}
