'use client';

import React, { useState, useEffect, useRef, useCallback } from 'react';
import { useAuth } from '@/context/AuthContext';
import {
  getUnreadNotificationCount,
  getMyNotifications,
  markNotificationRead,
  markAllNotificationsRead,
  dismissNotification,
} from '@/lib/api/notifications';
import { AppNotification } from '@/types/notification';
import { NotificationDropdown } from './NotificationDropdown';

export function NotificationBell() {
  const { isAuthenticated, user } = useAuth();
  const [unreadCount, setUnreadCount] = useState<number>(0);
  const [isOpen, setIsOpen] = useState<boolean>(false);
  const [recentNotifications, setRecentNotifications] = useState<AppNotification[]>([]);
  const [isLoadingList, setIsLoadingList] = useState<boolean>(false);
  const bellContainerRef = useRef<HTMLDivElement>(null);

  // Fetch unread count
  const refreshUnreadCount = useCallback(async () => {
    if (!isAuthenticated) return;
    try {
      const res = await getUnreadNotificationCount();
      setUnreadCount(res.unread_count || 0);
    } catch {
      // Graceful fallback: do not interrupt user experience
    }
  }, [isAuthenticated]);

  // Initial fetch & window focus refetch
  useEffect(() => {
    if (!isAuthenticated) return;

    let ignore = false;
    getUnreadNotificationCount()
      .then((res) => {
        if (!ignore) {
          setUnreadCount(res.unread_count || 0);
        }
      })
      .catch(() => {});

    const handleFocus = () => {
      refreshUnreadCount();
    };

    window.addEventListener('focus', handleFocus);
    return () => {
      ignore = true;
      window.removeEventListener('focus', handleFocus);
    };
  }, [isAuthenticated, user?.id, refreshUnreadCount]);

  // Fetch recent notifications when dropdown opens
  const fetchRecent = useCallback(async () => {
    setIsLoadingList(true);
    try {
      const res = await getMyNotifications('all', 1, 5);
      setRecentNotifications(res.data || []);
    } catch {
      // Ignore
    } finally {
      setIsLoadingList(false);
    }
  }, []);

  const handleToggle = () => {
    if (!isOpen) {
      setIsOpen(true);
      fetchRecent();
      refreshUnreadCount();
    } else {
      setIsOpen(false);
    }
  };

  // Close on outside click or Escape
  useEffect(() => {
    if (!isOpen) return;

    const handleClickOutside = (e: MouseEvent) => {
      if (
        bellContainerRef.current &&
        !bellContainerRef.current.contains(e.target as Node)
      ) {
        setIsOpen(false);
      }
    };

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        setIsOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      document.removeEventListener('keydown', handleKeyDown);
    };
  }, [isOpen]);

  const handleMarkRead = async (id: string) => {
    try {
      await markNotificationRead(id);
      setRecentNotifications((prev) =>
        prev.map((n) => (n.id === id ? { ...n, is_read: true, read_at: new Date().toISOString() } : n))
      );
      setUnreadCount((prev) => Math.max(0, prev - 1));
    } catch {
      // Ignore
    }
  };

  const handleMarkAllRead = async () => {
    try {
      await markAllNotificationsRead();
      setRecentNotifications((prev) =>
        prev.map((n) => ({ ...n, is_read: true, read_at: new Date().toISOString() }))
      );
      setUnreadCount(0);
    } catch {
      // Ignore
    }
  };

  const handleDismiss = async (id: string) => {
    try {
      await dismissNotification(id);
      const target = recentNotifications.find((n) => n.id === id);
      setRecentNotifications((prev) => prev.filter((n) => n.id !== id));
      if (target && !target.is_read) {
        setUnreadCount((prev) => Math.max(0, prev - 1));
      }
    } catch {
      // Ignore
    }
  };

  if (!isAuthenticated) {
    return null;
  }

  return (
    <div className="relative inline-block" ref={bellContainerRef}>
      <button
        type="button"
        onClick={handleToggle}
        aria-expanded={isOpen}
        aria-label={`Thông báo${unreadCount > 0 ? `: ${unreadCount} chưa đọc` : ''}`}
        className="relative p-2 rounded-full text-on-surface hover:text-primary hover:bg-surface-container-low transition-colors flex items-center justify-center cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/40"
      >
        <span className="material-symbols-outlined text-[22px]">
          {unreadCount > 0 ? 'notifications_active' : 'notifications'}
        </span>

        {unreadCount > 0 && (
          <span className="absolute top-1 right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-primary text-on-primary font-bold text-[10px] flex items-center justify-center ring-2 ring-surface shadow-xs">
            {unreadCount > 99 ? '99+' : unreadCount}
          </span>
        )}
      </button>

      {isOpen && (
        <NotificationDropdown
          notifications={recentNotifications}
          isLoading={isLoadingList}
          unreadCount={unreadCount}
          onMarkRead={handleMarkRead}
          onMarkAllRead={handleMarkAllRead}
          onDismiss={handleDismiss}
          onClose={() => setIsOpen(false)}
        />
      )}
    </div>
  );
}
