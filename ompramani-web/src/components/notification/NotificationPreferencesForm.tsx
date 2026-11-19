'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { useAuth } from '@/context/AuthContext';
import {
  getNotificationPreferences,
  updateNotificationPreferences,
} from '@/lib/api/notifications';
import { NotificationPreferences } from '@/types/notification';

export function NotificationPreferencesForm() {
  const { isAuthenticated, isLoading: isAuthLoading } = useAuth();
  const [preferences, setPreferences] = useState<NotificationPreferences>({
    moderation_updates_enabled: true,
    new_response_updates_enabled: false,
    participation_digest_enabled: false,
  });
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [isSaving, setIsSaving] = useState<boolean>(false);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  useEffect(() => {
    let ignore = false;
    const load = async () => {
      if (!isAuthenticated) return;
      try {
        const data = await getNotificationPreferences();
        if (!ignore) {
          setPreferences(data);
        }
      } catch (err: unknown) {
        if (!ignore) {
          setErrorMessage(
            err instanceof Error
              ? err.message
              : 'Không thể tải cài đặt thông báo.'
          );
        }
      } finally {
        if (!ignore) {
          setIsLoading(false);
        }
      }
    };

    load();
    return () => {
      ignore = true;
    };
  }, [isAuthenticated]);

  const handleToggle = (key: keyof NotificationPreferences) => {
    setPreferences((prev) => ({
      ...prev,
      [key]: !prev[key],
    }));
    setSuccessMessage(null);
    setErrorMessage(null);
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSaving(true);
    setSuccessMessage(null);
    setErrorMessage(null);

    try {
      const updated = await updateNotificationPreferences(preferences);
      setPreferences(updated);
      setSuccessMessage('Cập nhật cài đặt thông báo thành công.');
      setTimeout(() => setSuccessMessage(null), 4000);
    } catch (err: unknown) {
      setErrorMessage(
        err instanceof Error
          ? err.message
          : 'Không thể lưu cài đặt thông báo. Vui lòng thử lại.'
      );
    } finally {
      setIsSaving(false);
    }
  };

  if (isAuthLoading || isLoading) {
    return (
      <div className="w-full max-w-2xl mx-auto p-6 space-y-4 animate-pulse">
        <div className="h-8 bg-surface-container-low rounded-xl w-48" />
        <div className="h-32 bg-surface-container-low rounded-2xl" />
        <div className="h-32 bg-surface-container-low rounded-2xl" />
      </div>
    );
  }

  if (!isAuthenticated) {
    return (
      <div className="w-full max-w-md mx-auto py-16 px-4 text-center space-y-4">
        <span className="material-symbols-outlined text-[48px] text-outline">
          lock
        </span>
        <h2 className="font-headline-sm text-xl font-bold text-on-surface">
          Yêu Cầu Đăng Nhập
        </h2>
        <p className="font-body-md text-sm text-on-surface-variant">
          Vui lòng đăng nhập để tùy chỉnh cài đặt thông báo của bạn.
        </p>
        <Link
          href="/dang-nhap?redirect=/tai-khoan/cai-dat-thong-bao"
          className="inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-primary text-on-primary font-label-md text-sm font-semibold hover:bg-primary/90 transition-all shadow-xs"
        >
          <span>Đăng Nhập</span>
        </Link>
      </div>
    );
  }

  return (
    <div className="w-full max-w-2xl mx-auto space-y-6">
      {/* Header */}
      <div className="border-b border-outline-variant/30 pb-4">
        <div className="flex items-center gap-2.5">
          <Link
            href="/tai-khoan/thong-bao"
            className="p-1.5 rounded-xl hover:bg-surface-container-low text-outline hover:text-on-surface transition-colors"
            title="Quay lại danh sách thông báo"
          >
            <span className="material-symbols-outlined text-[20px]">
              arrow_back
            </span>
          </Link>
          <div>
            <h1 className="font-headline-sm text-2xl font-bold text-on-surface">
              Cài Đặt Thông Báo
            </h1>
            <p className="font-body-sm text-xs text-on-surface-variant mt-0.5">
              Tùy chỉnh các loại thông báo bạn muốn nhận trong trang cá nhân.
            </p>
          </div>
        </div>
      </div>

      {/* Notifications form */}
      <form onSubmit={handleSave} className="space-y-6">
        {/* Banner */}
        {successMessage && (
          <div className="p-3 rounded-xl bg-primary/10 border border-primary/20 text-primary font-label-md text-xs flex items-center gap-2">
            <span className="material-symbols-outlined text-[18px]">
              check_circle
            </span>
            <span>{successMessage}</span>
          </div>
        )}

        {errorMessage && (
          <div className="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 font-label-md text-xs flex items-center gap-2">
            <span className="material-symbols-outlined text-[18px]">error</span>
            <span>{errorMessage}</span>
          </div>
        )}

        {/* Options */}
        <div className="bg-surface-container-lowest rounded-3xl border border-outline-variant/30 divide-y divide-outline-variant/20 overflow-hidden shadow-2xs">
          {/* Option 1: Moderation updates */}
          <div className="p-4 sm:p-5 flex items-start justify-between gap-4">
            <div className="space-y-1">
              <label
                htmlFor="moderation-updates-toggle"
                className="font-label-md text-sm font-semibold text-on-surface block cursor-pointer"
              >
                Trạng thái kiểm duyệt
              </label>
              <p className="font-body-sm text-xs text-on-surface-variant leading-relaxed">
                Nhận thông báo khi lời nguyện hoặc lời nhắn đồng hành của bạn được
                ban quản trị phê duyệt, từ chối hoặc cần chỉnh sửa.
              </p>
            </div>
            <button
              id="moderation-updates-toggle"
              type="button"
              role="switch"
              aria-checked={preferences.moderation_updates_enabled}
              onClick={() => handleToggle('moderation_updates_enabled')}
              className={`w-12 h-6 rounded-full transition-colors relative cursor-pointer shrink-0 focus:outline-none focus:ring-2 focus:ring-primary/40 ${
                preferences.moderation_updates_enabled
                  ? 'bg-primary'
                  : 'bg-surface-container-high'
              }`}
            >
              <span
                className={`w-5 h-5 rounded-full bg-white transition-transform block shadow-xs ${
                  preferences.moderation_updates_enabled
                    ? 'translate-x-6'
                    : 'translate-x-0.5'
                }`}
              />
            </button>
          </div>

          {/* Option 2: New response updates */}
          <div className="p-4 sm:p-5 flex items-start justify-between gap-4">
            <div className="space-y-1">
              <label
                htmlFor="new-response-toggle"
                className="font-label-md text-sm font-semibold text-on-surface block cursor-pointer"
              >
                Lời nhắn đồng hành mới
              </label>
              <p className="font-body-sm text-xs text-on-surface-variant leading-relaxed">
                Nhận thông báo khi có người gửi lời nhắn động viên đã được duyệt
                dưới lời nguyện bạn chia sẻ với cộng đồng.
              </p>
            </div>
            <button
              id="new-response-toggle"
              type="button"
              role="switch"
              aria-checked={preferences.new_response_updates_enabled}
              onClick={() => handleToggle('new_response_updates_enabled')}
              className={`w-12 h-6 rounded-full transition-colors relative cursor-pointer shrink-0 focus:outline-none focus:ring-2 focus:ring-primary/40 ${
                preferences.new_response_updates_enabled
                  ? 'bg-primary'
                  : 'bg-surface-container-high'
              }`}
            >
              <span
                className={`w-5 h-5 rounded-full bg-white transition-transform block shadow-xs ${
                  preferences.new_response_updates_enabled
                    ? 'translate-x-6'
                    : 'translate-x-0.5'
                }`}
              />
            </button>
          </div>

          {/* Option 3: Participation digest */}
          <div className="p-4 sm:p-5 flex items-start justify-between gap-4">
            <div className="space-y-1">
              <label
                htmlFor="participation-digest-toggle"
                className="font-label-md text-sm font-semibold text-on-surface block cursor-pointer"
              >
                Tổng hợp lượt đồng hành hàng ngày
              </label>
              <p className="font-body-sm text-xs text-on-surface-variant leading-relaxed">
                Nhận thông báo tổng kết định kỳ nếu có Phật tử cùng tham gia cầu
                nguyện với lời nguyện công khai của bạn.
              </p>
            </div>
            <button
              id="participation-digest-toggle"
              type="button"
              role="switch"
              aria-checked={preferences.participation_digest_enabled}
              onClick={() => handleToggle('participation_digest_enabled')}
              className={`w-12 h-6 rounded-full transition-colors relative cursor-pointer shrink-0 focus:outline-none focus:ring-2 focus:ring-primary/40 ${
                preferences.participation_digest_enabled
                  ? 'bg-primary'
                  : 'bg-surface-container-high'
              }`}
            >
              <span
                className={`w-5 h-5 rounded-full bg-white transition-transform block shadow-xs ${
                  preferences.participation_digest_enabled
                    ? 'translate-x-6'
                    : 'translate-x-0.5'
                }`}
              />
            </button>
          </div>
        </div>

        {/* Privacy Note */}
        <div className="p-4 rounded-2xl bg-surface-container-low/50 border border-outline-variant/30 flex items-start gap-3">
          <span className="material-symbols-outlined text-outline text-[20px] shrink-0 mt-0.5">
            lock
          </span>
          <p className="font-body-sm text-xs text-on-surface-variant leading-relaxed">
            <strong>Bảo vệ sự thanh tịnh:</strong> Tất cả thông báo chỉ hiển thị
            trong hộp thư nội bộ của bạn trên website. OmPraMani tuyệt đối không
            gửi email quảng cáo, tin nhắn SMS, hay tiết lộ danh tính người tham
            gia đồng hành.
          </p>
        </div>

        {/* Submit Button */}
        <div className="flex justify-end gap-3 pt-2">
          <Link
            href="/tai-khoan/thong-bao"
            className="px-5 py-2.5 rounded-full border border-outline-variant/40 hover:bg-surface-container-low font-label-md text-xs font-medium text-on-surface transition-all"
          >
            Hủy
          </Link>
          <button
            type="submit"
            disabled={isSaving}
            className="px-6 py-2.5 rounded-full bg-primary text-on-primary font-label-md text-xs font-semibold hover:bg-primary/90 disabled:opacity-50 transition-all shadow-xs cursor-pointer"
          >
            {isSaving ? 'Đang lưu...' : 'Lưu Cài Đặt'}
          </button>
        </div>
      </form>
    </div>
  );
}
