'use client';

import React from 'react';
import Link from 'next/link';
import { AppNotification } from '@/types/notification';

interface NotificationCardProps {
  notification: AppNotification;
  onMarkRead?: (id: string) => void;
  onDismiss?: (id: string) => void;
  isCompact?: boolean;
}

export function NotificationCard({
  notification,
  onMarkRead,
  onDismiss,
  isCompact = false,
}: NotificationCardProps) {
  const getIconInfo = () => {
    switch (notification.type) {
      case 'prayer_intention_approved':
      case 'prayer_response_approved':
        return {
          icon: 'check_circle',
          colorClass: 'text-primary bg-primary/10',
        };
      case 'prayer_intention_rejected':
      case 'prayer_response_rejected':
        return {
          icon: 'info',
          colorClass: 'text-amber-600 bg-amber-500/10',
        };
      case 'prayer_intention_revoked':
      case 'prayer_response_revoked':
        return {
          icon: 'block',
          colorClass: 'text-rose-600 bg-rose-500/10',
        };
      case 'prayer_new_approved_response':
        return {
          icon: 'chat_bubble',
          colorClass: 'text-secondary bg-secondary/10',
        };
      case 'prayer_participation_digest':
        return {
          icon: 'diversity_1',
          colorClass: 'text-tertiary bg-tertiary/10',
        };
      case 'personal_practice_reminder':
        return {
          icon: 'self_improvement',
          colorClass: 'text-primary bg-primary/10',
        };
      default:
        return {
          icon: 'notifications',
          colorClass: 'text-outline bg-surface-container-low',
        };
    }
  };

  const { icon, colorClass } = getIconInfo();

  const formatRelativeTime = (isoDate: string): string => {
    try {
      const date = new Date(isoDate);
      const now = new Date();
      const diffSec = Math.floor((now.getTime() - date.getTime()) / 1000);
      if (diffSec < 60) return 'Vừa xong';
      if (diffSec < 3600) return `${Math.floor(diffSec / 60)} phút trước`;
      if (diffSec < 86400) return `${Math.floor(diffSec / 3600)} giờ trước`;
      if (diffSec < 172800) return 'Hôm qua';
      return date.toLocaleDateString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
      });
    } catch {
      return isoDate;
    }
  };

  const handleCardClick = () => {
    if (!notification.is_read && onMarkRead) {
      onMarkRead(notification.id);
    }
  };

  const content = (
    <div
      onClick={handleCardClick}
      className={`group relative flex items-start gap-3.5 transition-all cursor-pointer ${
        isCompact
          ? 'p-2.5 rounded-xl hover:bg-surface-container-low'
          : 'p-4 rounded-2xl border border-outline-variant/30 hover:border-outline-variant/70 hover:shadow-2xs'
      } ${
        !notification.is_read
          ? 'bg-primary/4'
          : 'bg-surface-container-lowest'
      }`}
    >
      {/* Icon */}
      <div
        className={`w-9 h-9 rounded-xl flex items-center justify-center shrink-0 ${colorClass}`}
      >
        <span className="material-symbols-outlined text-[20px]">{icon}</span>
      </div>

      {/* Main Content */}
      <div className="flex-1 min-w-0 pr-6">
        <div className="flex items-center gap-2">
          <h4
            className={`font-label-md text-xs sm:text-sm text-on-surface truncate ${
              !notification.is_read ? 'font-bold' : 'font-medium'
            }`}
          >
            {notification.title}
          </h4>
          {!notification.is_read && (
            <span
              className="w-2 h-2 rounded-full bg-primary shrink-0"
              title="Chưa đọc"
              aria-label="Thông báo chưa đọc"
            />
          )}
        </div>

        <p
          className={`font-body-sm text-xs text-on-surface-variant mt-1 leading-relaxed ${
            isCompact ? 'line-clamp-2' : ''
          }`}
        >
          {notification.message}
        </p>

        <span className="inline-block font-label-sm text-[11px] text-outline mt-1.5">
          {formatRelativeTime(notification.created_at)}
        </span>
      </div>

      {/* Dismiss / Delete Button */}
      {onDismiss && (
        <button
          type="button"
          onClick={(e) => {
            e.stopPropagation();
            e.preventDefault();
            onDismiss(notification.id);
          }}
          className="absolute top-2.5 right-2.5 p-1 rounded-lg text-outline hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 opacity-70 group-hover:opacity-100 transition-all cursor-pointer"
          title="Xóa thông báo"
          aria-label="Xóa thông báo"
        >
          <span className="material-symbols-outlined text-[16px]">close</span>
        </button>
      )}
    </div>
  );

  if (notification.destination) {
    return (
      <Link href={notification.destination} className="block">
        {content}
      </Link>
    );
  }

  return content;
}
