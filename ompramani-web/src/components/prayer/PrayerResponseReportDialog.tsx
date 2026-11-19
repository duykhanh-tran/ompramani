'use client';

import React, { useState } from 'react';
import {
  PrayerResponseReportReason,
  PRAYER_RESPONSE_REPORT_REASONS,
} from '@/types/prayer-response';
import { reportPrayerResponse } from '@/lib/api/prayer-responses';
import { useAuth } from '@/context/AuthContext';
import { useRouter } from 'next/navigation';

interface PrayerResponseReportDialogProps {
  responsePublicId: string;
  isOpen: boolean;
  onClose: () => void;
}

export function PrayerResponseReportDialog({
  responsePublicId,
  isOpen,
  onClose,
}: PrayerResponseReportDialogProps) {
  const router = useRouter();
  const { isAuthenticated } = useAuth();

  const [selectedReason, setSelectedReason] =
    useState<PrayerResponseReportReason>('spam');
  const [details, setDetails] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [isSuccess, setIsSuccess] = useState(false);

  if (!isOpen) {
    return null;
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!isAuthenticated) {
      router.push(`/dang-nhap?redirect=${encodeURIComponent(window.location.pathname)}`);
      return;
    }

    setIsSubmitting(true);
    setErrorMessage(null);

    try {
      await reportPrayerResponse(responsePublicId, {
        reason: selectedReason,
        details: details.trim() || undefined,
      });

      setIsSuccess(true);
      setTimeout(() => {
        onClose();
        setIsSuccess(false);
        setDetails('');
      }, 2000);
    } catch (err: unknown) {
      const message =
        err instanceof Error
          ? err.message
          : 'Không thể gửi báo cáo lúc này. Vui lòng thử lại sau.';
      setErrorMessage(message);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="report-dialog-title"
      className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200"
    >
      <div className="w-full max-w-md rounded-3xl bg-surface-container-lowest border border-outline-variant/60 shadow-xl p-6 sm:p-7 relative text-on-surface">
        {/* Close Button */}
        <button
          type="button"
          onClick={onClose}
          className="absolute top-5 right-5 p-1.5 rounded-full text-outline hover:text-on-surface hover:bg-surface-container transition-colors"
          aria-label="Đóng"
        >
          <span className="material-symbols-outlined text-[20px]">close</span>
        </button>

        <div className="flex items-center gap-2.5 mb-4">
          <div className="w-9 h-9 rounded-full bg-error-container/60 text-error flex items-center justify-center shrink-0">
            <span className="material-symbols-outlined text-[20px]">flag</span>
          </div>
          <div>
            <h3
              id="report-dialog-title"
              className="font-headline-md text-lg font-bold text-on-surface leading-tight"
            >
              Báo Cáo Nội Dung
            </h3>
            <p className="font-body-sm text-xs text-outline">
              Chung tay giữ gìn không gian chia sẻ thanh tịnh
            </p>
          </div>
        </div>

        {isSuccess ? (
          <div className="py-6 text-center space-y-2">
            <div className="w-12 h-12 rounded-full bg-primary/10 text-primary mx-auto flex items-center justify-center">
              <span className="material-symbols-outlined text-2xl">check_circle</span>
            </div>
            <p className="font-label-md text-sm font-semibold text-on-surface">
              Đã ghi nhận báo cáo
            </p>
            <p className="font-body-sm text-xs text-outline">
              Ban quản trị sẽ xem xét và xử lý theo chuẩn mực cộng đồng.
            </p>
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-4">
            {errorMessage && (
              <div className="p-3 rounded-2xl bg-error-container/40 border border-error/20 text-error font-body-sm text-xs flex items-center gap-2">
                <span className="material-symbols-outlined text-[16px] shrink-0">
                  error
                </span>
                <span>{errorMessage}</span>
              </div>
            )}

            <div>
              <label
                htmlFor="report-reason"
                className="font-label-md text-xs font-semibold text-on-surface block mb-1.5"
              >
                Lý do báo cáo <span className="text-error">*</span>
              </label>
              <select
                id="report-reason"
                value={selectedReason}
                onChange={(e) =>
                  setSelectedReason(e.target.value as PrayerResponseReportReason)
                }
                className="w-full px-3.5 py-2.5 rounded-2xl bg-surface-container-low border border-outline-variant/60 font-body-md text-sm text-on-surface focus:outline-hidden focus:border-primary focus:ring-1 focus:ring-primary transition-colors"
              >
                {PRAYER_RESPONSE_REPORT_REASONS.map((opt) => (
                  <option key={opt.key} value={opt.key}>
                    {opt.label}
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label
                htmlFor="report-details"
                className="font-label-md text-xs font-semibold text-on-surface flex items-center justify-between mb-1.5"
              >
                <span>Chi tiết mô tả thêm (không bắt buộc)</span>
                <span className="font-body-sm text-[11px] text-outline font-normal">
                  {details.length}/500
                </span>
              </label>
              <textarea
                id="report-details"
                rows={3}
                maxLength={500}
                value={details}
                onChange={(e) => setDetails(e.target.value)}
                placeholder="Mô tả cụ thể vấn đề nếu cần..."
                className="w-full px-3.5 py-2.5 rounded-2xl bg-surface-container-low border border-outline-variant/60 font-body-md text-sm text-on-surface placeholder:text-outline/60 focus:outline-hidden focus:border-primary focus:ring-1 focus:ring-primary transition-colors resize-none"
              />
            </div>

            <div className="pt-2 flex items-center justify-end gap-2.5">
              <button
                type="button"
                onClick={onClose}
                disabled={isSubmitting}
                className="px-4 py-2 rounded-xl border border-outline-variant/50 text-outline hover:text-on-surface hover:bg-surface-container font-label-md text-xs font-medium transition-colors"
              >
                Hủy bỏ
              </button>
              <button
                type="submit"
                disabled={isSubmitting}
                className="px-4 py-2 rounded-xl bg-primary text-on-primary font-label-md text-xs font-semibold hover:bg-primary-hover transition-colors shadow-xs flex items-center gap-1.5 disabled:opacity-50"
              >
                {isSubmitting ? (
                  <>
                    <span className="w-3.5 h-3.5 rounded-full border-2 border-on-primary border-t-transparent animate-spin" />
                    <span>Đang gửi...</span>
                  </>
                ) : (
                  <span>Gửi báo cáo</span>
                )}
              </button>
            </div>
          </form>
        )}
      </div>
    </div>
  );
}
