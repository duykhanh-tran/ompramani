'use client';

import React from 'react';
import Link from 'next/link';
import { AppNotification } from '@/types/notification';
import { NotificationCard } from './NotificationCard';

interface NotificationDropdownProps {
  notifications: AppNotification[];
  isLoading: boolean;
  unreadCount: number;
  onMarkRead: (id: string) => void;
  onMarkAllRead: () => void;
  onDismiss: (id: string) => void;
  onClose: () => void;
}

export function NotificationDropdown({
  notifications,
  isLoading,
  unreadCount,
  onMarkRead,
  onMarkAllRead,
  onDismiss,
  onClose,
}: NotificationDropdownProps) {
  return (
    <div className="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-surface-container-lowest border border-outline-variant/50 shadow-xl z-50 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
      {/* Header */}
      <div className="flex items-center justify-between px-4 py-3 border-b border-outline-variant/30 bg-surface-container-low/40">
        <div className="flex items-center gap-2">
          <span className="font-label-md font-semibold text-on-surface text-sm">
            Thông Báo
          </span>
          {unreadCount > 0 && (
            <span className="px-2 py-0.5 rounded-full bg-primary/10 text-primary font-bold text-[11px]">
              {unreadCount} mới
            </span>
          )}
        </div>

        {unreadCount > 0 && (
          <button
            type="button"
            onClick={onMarkAllRead}
            className="text-[11px] font-medium text-primary hover:underline cursor-pointer"
          >
            Đã đọc tất cả
          </button>
        )}
      </div>

      {/* Notifications List */}
      <div className="max-h-[380px] overflow-y-auto divide-y divide-outline-variant/20 p-1">
        {isLoading ? (
          <div className="space-y-2 p-3">
            {[1, 2, 3].map((i) => (
              <div
                key={i}
                className="flex items-start gap-3 p-2 rounded-xl animate-pulse"
              >
                <div className="w-8 h-8 rounded-lg bg-surface-container-low shrink-0" />
                <div className="flex-1 space-y-1.5">
                  <div className="h-3 bg-surface-container-low rounded w-3/4" />
                  <div className="h-2.5 bg-surface-container-low rounded w-full" />
                </div>
              </div>
            ))}
          </div>
        ) : notifications.length === 0 ? (
          <div className="py-8 px-4 text-center">
            <span className="material-symbols-outlined text-[32px] text-outline/60 mb-2">
              notifications_none
            </span>
            <p className="font-body-sm text-xs text-on-surface-variant">
              Bạn chưa có thông báo nào.
            </p>
          </div>
        ) : (
          notifications.slice(0, 5).map((item) => (
            <div key={item.id} onClick={onClose}>
              <NotificationCard
                notification={item}
                onMarkRead={onMarkRead}
                onDismiss={onDismiss}
                isCompact
              />
            </div>
          ))
        )}
      </div>

      {/* Footer Actions */}
      <div className="flex items-center justify-between px-4 py-2.5 border-t border-outline-variant/30 bg-surface-container-low/30 text-xs font-label-md">
        <Link
          href="/tai-khoan/thong-bao"
          onClick={onClose}
          className="text-primary hover:underline font-medium flex items-center gap-1"
        >
          <span>Xem tất cả</span>
          <span className="material-symbols-outlined text-[14px]">
            arrow_forward
          </span>
        </Link>

        <Link
          href="/tai-khoan/cai-dat-thong-bao"
          onClick={onClose}
          className="text-outline hover:text-on-surface transition-colors flex items-center gap-1"
        >
          <span className="material-symbols-outlined text-[14px]">settings</span>
          <span>Cài đặt</span>
        </Link>
      </div>
    </div>
  );
}
