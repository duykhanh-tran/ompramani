'use client';

import React, { useState } from 'react';
import {
  PracticePlan,
  CreatePracticePlanInput,
  UpdatePracticePlanInput,
} from '@/types/practice-plan';

interface PracticePlanFormModalProps {
  isOpen: boolean;
  onClose: () => void;
  planToEdit: PracticePlan | null;
  onSave: (data: CreatePracticePlanInput | UpdatePracticePlanInput) => Promise<void>;
  isSubmitting: boolean;
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

function PracticePlanFormContent({
  onClose,
  planToEdit,
  onSave,
  isSubmitting,
}: {
  onClose: () => void;
  planToEdit: PracticePlan | null;
  onSave: (data: CreatePracticePlanInput | UpdatePracticePlanInput) => Promise<void>;
  isSubmitting: boolean;
}) {
  const [name, setName] = useState(planToEdit?.name || '');
  const [description, setDescription] = useState(planToEdit?.description || '');
  const [timezone, setTimezone] = useState(
    planToEdit?.timezone ||
      (() => {
        try {
          return Intl.DateTimeFormat().resolvedOptions().timeZone || 'Asia/Ho_Chi_Minh';
        } catch {
          return 'Asia/Ho_Chi_Minh';
        }
      })()
  );
  const [startsOn, setStartsOn] = useState(planToEdit?.starts_on || '');
  const [endsOn, setEndsOn] = useState(planToEdit?.ends_on || '');
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg(null);

    if (!name.trim()) {
      setErrorMsg('Vui lòng nhập tên kế hoạch tu tập.');
      return;
    }

    try {
      await onSave({
        name: name.trim(),
        description: description.trim() || null,
        timezone,
        starts_on: startsOn || null,
        ends_on: endsOn || null,
      });
      onClose();
    } catch (err: unknown) {
      if (err instanceof Error) {
        setErrorMsg(err.message);
      } else {
        setErrorMsg('Đã có lỗi xảy ra khi lưu kế hoạch tu tập.');
      }
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs animate-in fade-in duration-200">
      <div
        className="w-full max-w-md rounded-3xl bg-surface-container-lowest border border-outline-variant/30 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="plan-form-modal-title"
      >
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-outline-variant/20 bg-surface-container-low/50">
          <div className="flex items-center gap-2.5">
            <span className="material-symbols-outlined text-[22px] text-primary">
              calendar_month
            </span>
            <h2
              id="plan-form-modal-title"
              className="font-serif text-lg font-semibold text-on-surface"
            >
              {planToEdit ? 'Chỉnh sửa kế hoạch tu tập' : 'Tạo kế hoạch tu tập mới'}
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
        <form onSubmit={handleSubmit} className="flex-1 overflow-y-auto p-6 space-y-4">
          {errorMsg && (
            <div className="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 text-xs font-label-md flex items-center gap-2">
              <span className="material-symbols-outlined text-[18px]">error</span>
              <span>{errorMsg}</span>
            </div>
          )}

          {/* Name */}
          <div>
            <label
              htmlFor="plan-name"
              className="block text-xs font-label-md font-semibold text-on-surface mb-1.5"
            >
              Tên kế hoạch <span className="text-rose-600">*</span>
            </label>
            <input
              id="plan-name"
              type="text"
              required
              maxLength={150}
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="VD: Thời khóa buổi sáng, Khóa tu an cư..."
              className="w-full px-3.5 py-2.5 rounded-2xl border border-outline-variant/40 bg-surface text-on-surface text-xs font-body-md focus:outline-none focus:border-primary transition-colors"
            />
          </div>

          {/* Description */}
          <div>
            <label
              htmlFor="plan-desc"
              className="block text-xs font-label-md font-semibold text-on-surface mb-1.5"
            >
              Mô tả ngắn <span className="font-normal text-outline">(tùy chọn)</span>
            </label>
            <textarea
              id="plan-desc"
              rows={2}
              maxLength={1000}
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="Mục đích, ý nguyện hoặc ghi chú tu học..."
              className="w-full px-3.5 py-2 rounded-2xl border border-outline-variant/40 bg-surface text-on-surface text-xs font-body-md focus:outline-none focus:border-primary transition-colors"
            />
          </div>

          {/* Timezone */}
          <div>
            <label
              htmlFor="plan-tz"
              className="block text-xs font-label-md font-semibold text-on-surface mb-1.5"
            >
              Múi giờ kế hoạch
            </label>
            <select
              id="plan-tz"
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

          {/* Start and End dates */}
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label
                htmlFor="plan-starts"
                className="block text-xs font-label-md font-semibold text-on-surface mb-1.5"
              >
                Bắt đầu từ <span className="font-normal text-outline">(tùy chọn)</span>
              </label>
              <input
                id="plan-starts"
                type="date"
                value={startsOn}
                onChange={(e) => setStartsOn(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-outline-variant/40 bg-surface text-on-surface text-xs font-body-md focus:outline-none focus:border-primary"
              />
            </div>
            <div>
              <label
                htmlFor="plan-ends"
                className="block text-xs font-label-md font-semibold text-on-surface mb-1.5"
              >
                Kết thúc <span className="font-normal text-outline">(tùy chọn)</span>
              </label>
              <input
                id="plan-ends"
                type="date"
                value={endsOn}
                onChange={(e) => setEndsOn(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-outline-variant/40 bg-surface text-on-surface text-xs font-body-md focus:outline-none focus:border-primary"
              />
            </div>
          </div>

          {/* Footer actions */}
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
                  <span>{planToEdit ? 'Lưu thay đổi' : 'Tạo kế hoạch'}</span>
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export function PracticePlanFormModal(props: PracticePlanFormModalProps) {
  if (!props.isOpen) return null;

  return (
    <PracticePlanFormContent
      key={props.planToEdit ? `edit-plan-${props.planToEdit.id}` : 'create-plan'}
      {...props}
    />
  );
}
