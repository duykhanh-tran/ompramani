'use client';

import React, { useState } from 'react';
import {
  PracticeReminder,
  CreatePracticeReminderInput,
  UpdatePracticeReminderInput,
  PracticeReminderScheduleType,
} from '@/types/practice-reminder';
import { PracticeType } from '@/types/practice';

interface PracticeReminderFormModalProps {
  isOpen: boolean;
  onClose: () => void;
  reminderToEdit: PracticeReminder | null;
  onSave: (
    data: CreatePracticeReminderInput | UpdatePracticeReminderInput
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

const COMMON_TIMEZONES = [
  { value: 'Asia/Ho_Chi_Minh', label: 'Việt Nam (GMT+7 - Asia/Ho_Chi_Minh)' },
  { value: 'Asia/Bangkok', label: 'Bangkok (GMT+7)' },
  { value: 'Asia/Singapore', label: 'Singapore (GMT+8)' },
  { value: 'Asia/Tokyo', label: 'Tokyo (GMT+9)' },
  { value: 'Asia/Seoul', label: 'Seoul (GMT+9)' },
  { value: 'Asia/Taipei', label: 'Đài Bắc (GMT+8)' },
  { value: 'Australia/Sydney', label: 'Sydney (Australia/Sydney)' },
  { value: 'Europe/Paris', label: 'Paris (Europe/Paris)' },
  { value: 'Europe/London', label: 'London (Europe/London)' },
  { value: 'America/New_York', label: 'New York (America/New_York)' },
  { value: 'America/Los_Angeles', label: 'Los Angeles (America/Los_Angeles)' },
  { value: 'America/Chicago', label: 'Chicago (America/Chicago)' },
];

function getInitialValues(reminderToEdit: PracticeReminder | null) {
  if (reminderToEdit) {
    return {
      practiceType: reminderToEdit.practice_type,
      title: reminderToEdit.title || '',
      scheduleType: reminderToEdit.schedule_type,
      localTime: reminderToEdit.local_time,
      localDate: reminderToEdit.local_date || '',
      weekdays: reminderToEdit.weekdays?.length ? reminderToEdit.weekdays : [1, 2, 3, 4, 5, 6, 7],
      timezone: reminderToEdit.timezone || 'Asia/Ho_Chi_Minh',
    };
  }

  let defaultTz = 'Asia/Ho_Chi_Minh';
  try {
    const detectedTz = Intl.DateTimeFormat().resolvedOptions().timeZone;
    if (detectedTz) defaultTz = detectedTz;
  } catch {
    // fallback
  }

  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  const tomorrowStr = tomorrow.toISOString().split('T')[0];

  return {
    practiceType: 'meditation' as PracticeType,
    title: '',
    scheduleType: 'daily' as PracticeReminderScheduleType,
    localTime: '06:00',
    localDate: tomorrowStr,
    weekdays: [1, 2, 3, 4, 5, 6, 7],
    timezone: defaultTz,
  };
}

function PracticeReminderFormModalContent({
  onClose,
  reminderToEdit,
  onSave,
  isSubmitting,
}: {
  onClose: () => void;
  reminderToEdit: PracticeReminder | null;
  onSave: (
    data: CreatePracticeReminderInput | UpdatePracticeReminderInput
  ) => Promise<void>;
  isSubmitting: boolean;
}) {
  const initial = getInitialValues(reminderToEdit);
  const [practiceType, setPracticeType] = useState<PracticeType>(initial.practiceType);
  const [title, setTitle] = useState(initial.title);
  const [scheduleType, setScheduleType] =
    useState<PracticeReminderScheduleType>(initial.scheduleType);
  const [localTime, setLocalTime] = useState(initial.localTime);
  const [localDate, setLocalDate] = useState(initial.localDate);
  const [weekdays, setWeekdays] = useState<number[]>(initial.weekdays);
  const [timezone, setTimezone] = useState(initial.timezone);
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

    if (!localTime || !/^\d{2}:\d{2}$/.test(localTime)) {
      setErrorMsg('Vui lòng chọn thời gian hợp lệ (giờ:phút).');
      return;
    }

    if (scheduleType === 'once' && !localDate) {
      setErrorMsg('Vui lòng chọn ngày nhắc cho lịch một lần.');
      return;
    }

    if (scheduleType === 'weekly' && weekdays.length === 0) {
      setErrorMsg('Vui lòng chọn ít nhất một thứ trong tuần.');
      return;
    }

    try {
      const payload: CreatePracticeReminderInput = {
        practice_type: practiceType,
        title: title.trim() || null,
        schedule_type: scheduleType,
        local_time: localTime,
        timezone,
        ...(scheduleType === 'once' ? { local_date: localDate } : { local_date: null }),
        ...(scheduleType === 'weekly' ? { weekdays } : { weekdays: [] }),
      };

      await onSave(payload);
      onClose();
    } catch (err: unknown) {
      if (err instanceof Error) {
        setErrorMsg(err.message);
      } else {
        setErrorMsg('Đã có lỗi xảy ra khi lưu lời nhắc tu tập.');
      }
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs animate-in fade-in duration-200">
      <div
        className="w-full max-w-lg rounded-3xl bg-surface-container-lowest border border-outline-variant/30 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="reminder-modal-title"
      >
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-outline-variant/20 bg-surface-container-low/50">
          <div className="flex items-center gap-2.5">
            <span className="material-symbols-outlined text-[22px] text-primary">
              alarm_add
            </span>
            <h2
              id="reminder-modal-title"
              className="font-serif text-lg font-semibold text-on-surface"
            >
              {reminderToEdit ? 'Chỉnh sửa lời nhắc tu tập' : 'Thiết lập lời nhắc tu tập mới'}
            </h2>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="p-1 rounded-full text-outline hover:text-on-surface hover:bg-surface-container transition-colors cursor-pointer"
            aria-label="Đóng cửa sổ"
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

          {/* Title (Optional) */}
          <div>
            <label
              htmlFor="reminder-title"
              className="block text-xs font-label-md font-semibold text-on-surface mb-1.5"
            >
              Tên lời nhắc <span className="font-normal text-outline">(tùy chọn)</span>
            </label>
            <input
              id="reminder-title"
              type="text"
              maxLength={100}
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              placeholder="VD: Thời thiền sáng an lạc, Khóa lễ tối..."
              className="w-full px-3.5 py-2.5 rounded-2xl border border-outline-variant/40 bg-surface text-on-surface text-xs font-body-md focus:outline-none focus:border-primary transition-colors"
            />
          </div>

          {/* Schedule Type Selection */}
          <div>
            <label className="block text-xs font-label-md font-semibold text-on-surface mb-2">
              Tần suất lặp lại
            </label>
            <div className="grid grid-cols-3 gap-2">
              <button
                type="button"
                onClick={() => setScheduleType('daily')}
                className={`py-2 px-3 rounded-2xl border text-center text-xs font-label-md transition-all cursor-pointer ${
                  scheduleType === 'daily'
                    ? 'border-primary bg-primary text-on-primary font-bold shadow-2xs'
                    : 'border-outline-variant/40 bg-surface-container-low text-on-surface hover:bg-surface-container'
                }`}
              >
                Hằng ngày
              </button>
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

          {/* Time Picker */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label
                htmlFor="reminder-time"
                className="block text-xs font-label-md font-semibold text-on-surface mb-1.5"
              >
                Giờ nhắc (Giờ:Phút)
              </label>
              <input
                id="reminder-time"
                type="time"
                required
                value={localTime}
                onChange={(e) => setLocalTime(e.target.value)}
                className="w-full px-3.5 py-2.5 rounded-2xl border border-outline-variant/40 bg-surface text-on-surface text-xs font-body-md focus:outline-none focus:border-primary transition-colors"
              />
            </div>

            {/* If Once: Date Picker */}
            {scheduleType === 'once' && (
              <div>
                <label
                  htmlFor="reminder-date"
                  className="block text-xs font-label-md font-semibold text-on-surface mb-1.5"
                >
                  Ngày nhắc
                </label>
                <input
                  id="reminder-date"
                  type="date"
                  required
                  value={localDate}
                  onChange={(e) => setLocalDate(e.target.value)}
                  className="w-full px-3.5 py-2.5 rounded-2xl border border-outline-variant/40 bg-surface text-on-surface text-xs font-body-md focus:outline-none focus:border-primary transition-colors"
                />
              </div>
            )}
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

          {/* Timezone Selection */}
          <div>
            <label
              htmlFor="reminder-timezone"
              className="block text-xs font-label-md font-semibold text-on-surface mb-1.5"
            >
              Múi giờ địa phương
            </label>
            <select
              id="reminder-timezone"
              value={timezone}
              onChange={(e) => setTimezone(e.target.value)}
              className="w-full px-3.5 py-2.5 rounded-2xl border border-outline-variant/40 bg-surface text-on-surface text-xs font-body-md focus:outline-none focus:border-primary transition-colors"
            >
              {COMMON_TIMEZONES.map((tz) => (
                <option key={tz.value} value={tz.value}>
                  {tz.label}
                </option>
              ))}
              {!COMMON_TIMEZONES.some((tz) => tz.value === timezone) && (
                <option value={timezone}>{timezone}</option>
              )}
            </select>
          </div>

          {/* Buddhist Note & Boundary Reassurance */}
          <div className="p-3.5 rounded-2xl bg-surface-container-low border border-outline-variant/20 text-outline text-[11px] font-body-sm leading-relaxed flex items-start gap-2.5">
            <span className="material-symbols-outlined text-[16px] text-primary shrink-0 mt-0.5">
              spa
            </span>
            <span>
              Thông báo nhắc tu tập được gửi an tĩnh vào Hộp thư thông báo trong ứng dụng. Hệ thống tôn trọng tự do thực hành: không tự động mở phiên tu, không can thiệp Streak hay số lượng biến niệm.
            </span>
          </div>

          {/* Modal Actions */}
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
                  <span>{reminderToEdit ? 'Lưu thay đổi' : 'Tạo lời nhắc'}</span>
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export function PracticeReminderFormModal(props: PracticeReminderFormModalProps) {
  if (!props.isOpen) return null;

  return (
    <PracticeReminderFormModalContent
      key={props.reminderToEdit ? `edit-${props.reminderToEdit.id}` : 'create'}
      {...props}
    />
  );
}
