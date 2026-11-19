'use client';

import React, { useState } from 'react';
import {
  PracticeReminderSettings,
  UpdatePracticeReminderSettingsInput,
} from '@/types/practice-reminder';

interface PracticeReminderSettingsModalProps {
  isOpen: boolean;
  onClose: () => void;
  settings: PracticeReminderSettings;
  onSave: (data: UpdatePracticeReminderSettingsInput) => Promise<void>;
  isSaving: boolean;
}

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

function PracticeReminderSettingsModalContent({
  onClose,
  settings,
  onSave,
  isSaving,
}: {
  onClose: () => void;
  settings: PracticeReminderSettings;
  onSave: (data: UpdatePracticeReminderSettingsInput) => Promise<void>;
  isSaving: boolean;
}) {
  const [remindersEnabled, setRemindersEnabled] = useState(
    settings.practice_reminders_enabled
  );
  const [quietHoursEnabled, setQuietHoursEnabled] = useState(
    settings.quiet_hours_enabled
  );
  const [quietStart, setQuietStart] = useState(
    settings.quiet_start_local_time || '22:00'
  );
  const [quietEnd, setQuietEnd] = useState(
    settings.quiet_end_local_time || '06:00'
  );
  const [quietTz, setQuietTz] = useState(
    settings.quiet_hours_timezone || 'Asia/Ho_Chi_Minh'
  );
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg(null);

    try {
      await onSave({
        practice_reminders_enabled: remindersEnabled,
        quiet_hours_enabled: quietHoursEnabled,
        quiet_start_local_time: quietStart,
        quiet_end_local_time: quietEnd,
        quiet_hours_timezone: quietTz,
      });
      onClose();
    } catch (err: unknown) {
      if (err instanceof Error) {
        setErrorMsg(err.message);
      } else {
        setErrorMsg('Không thể cập nhật cài đặt. Vui lòng thử lại.');
      }
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs animate-in fade-in duration-200">
      <div
        className="w-full max-w-md rounded-3xl bg-surface-container-lowest border border-outline-variant/30 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="settings-modal-title"
      >
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-outline-variant/20 bg-surface-container-low/50">
          <div className="flex items-center gap-2.5">
            <span className="material-symbols-outlined text-[22px] text-primary">
              bedtime
            </span>
            <h2
              id="settings-modal-title"
              className="font-serif text-lg font-semibold text-on-surface"
            >
              Cài đặt & Giờ yên tĩnh
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

        {/* Form Body */}
        <form onSubmit={handleSubmit} className="flex-1 overflow-y-auto p-6 space-y-6">
          {errorMsg && (
            <div className="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 text-xs font-label-md flex items-center gap-2">
              <span className="material-symbols-outlined text-[18px]">error</span>
              <span>{errorMsg}</span>
            </div>
          )}

          {/* Toggle: Master Practice Reminders */}
          <div className="flex items-start justify-between gap-4 p-4 rounded-2xl bg-surface-container-low border border-outline-variant/20">
            <div>
              <h4 className="text-sm font-label-md font-semibold text-on-surface">
                Bật thông báo nhắc tu tập
              </h4>
              <p className="text-xs text-outline mt-1 font-body-sm leading-relaxed">
                Cho phép ứng dụng gửi thông báo nhắc thời khóa tu tập vào hộp thư cá nhân của bạn.
              </p>
            </div>
            <label className="relative inline-flex items-center cursor-pointer shrink-0 mt-1">
              <input
                type="checkbox"
                checked={remindersEnabled}
                onChange={(e) => setRemindersEnabled(e.target.checked)}
                className="sr-only peer"
                aria-label="Bật thông báo nhắc tu tập"
              />
              <div className="w-11 h-6 bg-surface-container-highest peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary" />
            </label>
          </div>

          {/* Toggle: Quiet Hours */}
          <div className="flex items-start justify-between gap-4 p-4 rounded-2xl bg-surface-container-low border border-outline-variant/20">
            <div>
              <h4 className="text-sm font-label-md font-semibold text-on-surface">
                Giờ yên tĩnh (Quiet Hours)
              </h4>
              <p className="text-xs text-outline mt-1 font-body-sm leading-relaxed">
                Tự động bỏ qua các lời nhắc rơi vào khung giờ nghỉ ngơi để không làm gián đoạn an lạc.
              </p>
            </div>
            <label className="relative inline-flex items-center cursor-pointer shrink-0 mt-1">
              <input
                type="checkbox"
                checked={quietHoursEnabled}
                onChange={(e) => setQuietHoursEnabled(e.target.checked)}
                className="sr-only peer"
                aria-label="Bật giờ yên tĩnh"
              />
              <div className="w-11 h-6 bg-surface-container-highest peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary" />
            </label>
          </div>

          {/* Quiet Hours details when enabled */}
          {quietHoursEnabled && (
            <div className="space-y-4 p-4 rounded-2xl bg-surface-container-lowest border border-outline-variant/30 animate-in fade-in duration-150">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label
                    htmlFor="quiet-start"
                    className="block text-xs font-label-md font-semibold text-on-surface mb-1.5"
                  >
                    Bắt đầu yên tĩnh
                  </label>
                  <input
                    id="quiet-start"
                    type="time"
                    required
                    value={quietStart}
                    onChange={(e) => setQuietStart(e.target.value)}
                    className="w-full px-3 py-2 rounded-xl border border-outline-variant/40 bg-surface text-on-surface text-xs font-body-md focus:outline-none focus:border-primary"
                  />
                </div>
                <div>
                  <label
                    htmlFor="quiet-end"
                    className="block text-xs font-label-md font-semibold text-on-surface mb-1.5"
                  >
                    Kết thúc yên tĩnh
                  </label>
                  <input
                    id="quiet-end"
                    type="time"
                    required
                    value={quietEnd}
                    onChange={(e) => setQuietEnd(e.target.value)}
                    className="w-full px-3 py-2 rounded-xl border border-outline-variant/40 bg-surface text-on-surface text-xs font-body-md focus:outline-none focus:border-primary"
                  />
                </div>
              </div>

              <div>
                <label
                  htmlFor="quiet-tz"
                  className="block text-xs font-label-md font-semibold text-on-surface mb-1.5"
                >
                  Múi giờ của giờ yên tĩnh
                </label>
                <select
                  id="quiet-tz"
                  value={quietTz}
                  onChange={(e) => setQuietTz(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl border border-outline-variant/40 bg-surface text-on-surface text-xs font-body-md focus:outline-none focus:border-primary"
                >
                  {COMMON_TIMEZONES.map((tz) => (
                    <option key={tz.value} value={tz.value}>
                      {tz.label}
                    </option>
                  ))}
                  {!COMMON_TIMEZONES.some((tz) => tz.value === quietTz) && (
                    <option value={quietTz}>{quietTz}</option>
                  )}
                </select>
              </div>

              <p className="text-[11px] text-outline leading-relaxed italic">
                * Khung giờ yên tĩnh có thể kéo dài qua đêm (ví dụ: {quietStart} tối hôm trước đến {quietEnd} sáng hôm sau).
              </p>
            </div>
          )}

          {/* Modal Actions */}
          <div className="pt-2 flex items-center justify-end gap-3 border-t border-outline-variant/20">
            <button
              type="button"
              onClick={onClose}
              disabled={isSaving}
              className="px-4 py-2 rounded-2xl border border-outline-variant/40 text-xs font-label-md font-semibold text-on-surface hover:bg-surface-container transition-colors cursor-pointer"
            >
              Đóng
            </button>
            <button
              type="submit"
              disabled={isSaving}
              className="px-5 py-2 rounded-2xl bg-primary text-on-primary text-xs font-label-md font-bold shadow-xs hover:bg-primary-variant transition-colors flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
            >
              {isSaving ? (
                <>
                  <span className="w-3.5 h-3.5 border-2 border-on-primary border-t-transparent rounded-full animate-spin" />
                  <span>Đang lưu...</span>
                </>
              ) : (
                <>
                  <span className="material-symbols-outlined text-[16px]">check</span>
                  <span>Lưu cài đặt</span>
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export function PracticeReminderSettingsModal(
  props: PracticeReminderSettingsModalProps
) {
  if (!props.isOpen) return null;

  return (
    <PracticeReminderSettingsModalContent
      key={props.settings ? `${props.settings.practice_reminders_enabled}-${props.settings.quiet_hours_enabled}` : 'default'}
      {...props}
    />
  );
}
