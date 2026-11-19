'use client';

import React, { useState, useEffect, useCallback } from 'react';
import Link from 'next/link';
import { useAuth } from '@/context/AuthContext';
import {
  getMyNotifications,
  markNotificationRead,
  markAllNotificationsRead,
  dismissNotification,
} from '@/lib/api/notifications';
import { AppNotification } from '@/types/notification';
import { NotificationCard } from './NotificationCard';

type NotificationFilter = 'all' | 'unread';

export function NotificationCenter() {
  const { isAuthenticated, isLoading: isAuthLoading } = useAuth();
  const [filter, setFilter] = useState<NotificationFilter>('all');
  const [page, setPage] = useState<number>(1);
  const [notifications, setNotifications] = useState<AppNotification[]>([]);
  const [totalCount, setTotalCount] = useState<number>(0);
  const [lastPage, setLastPage] = useState<number>(1);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);
  const [actionMessage, setActionMessage] = useState<string | null>(null);

  const fetchNotifications = useCallback(
    async (targetFilter: NotificationFilter, targetPage: number) => {
      setIsLoading(true);
      setError(null);
      try {
        const res = await getMyNotifications(targetFilter, targetPage, 15);
        setNotifications(res.data || []);
        setPage(res.meta?.current_page || targetPage);
        setLastPage(res.meta?.last_page || 1);
        setTotalCount(res.meta?.total || 0);
      } catch (err: unknown) {
        setError(
          err instanceof Error
            ? err.message
            : 'Không thể tải danh sách thông báo. Vui lòng thử lại.'
        );
      } finally {
        setIsLoading(false);
      }
    },
    []
  );

  // Initial load & when filter/page changes
  useEffect(() => {
    let ignore = false;
    const load = async () => {
      if (!isAuthenticated) return;
      try {
        const res = await getMyNotifications(filter, page, 15);
        if (!ignore) {
          setNotifications(res.data || []);
          setPage(res.meta?.current_page || page);
          setLastPage(res.meta?.last_page || 1);
          setTotalCount(res.meta?.total || 0);
        }
      } catch (err: unknown) {
        if (!ignore) {
          setError(
            err instanceof Error
              ? err.message
              : 'Không thể tải danh sách thông báo. Vui lòng thử lại.'
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
  }, [isAuthenticated, filter, page]);

  const handleFilterChange = (newFilter: NotificationFilter) => {
    setFilter(newFilter);
    setPage(1);
    fetchNotifications(newFilter, 1);
  };

  const handleMarkRead = async (id: string) => {
    try {
      await markNotificationRead(id);
      setNotifications((prev) =>
        prev.map((n) =>
          n.id === id ? { ...n, is_read: true, read_at: new Date().toISOString() } : n
        )
      );
    } catch {
      // Ignore
    }
  };

  const handleMarkAllRead = async () => {
    try {
      await markAllNotificationsRead();
      setNotifications((prev) =>
        prev.map((n) => ({ ...n, is_read: true, read_at: new Date().toISOString() }))
      );
      setActionMessage('Đã đánh dấu tất cả thông báo là đã đọc.');
      setTimeout(() => setActionMessage(null), 3000);
      if (filter === 'unread') {
        fetchNotifications('unread', 1);
      }
    } catch (err: unknown) {
      setError(
        err instanceof Error ? err.message : 'Không thể cập nhật trạng thái đã đọc.'
      );
    }
  };

  const handleDismiss = async (id: string) => {
    try {
      await dismissNotification(id);
      setNotifications((prev) => prev.filter((n) => n.id !== id));
      setTotalCount((prev) => Math.max(0, prev - 1));
      setActionMessage('Đã xóa thông báo khỏi danh sách.');
      setTimeout(() => setActionMessage(null), 3000);
    } catch (err: unknown) {
      setError(
        err instanceof Error ? err.message : 'Không thể xóa thông báo lúc này.'
      );
    }
  };

  if (isAuthLoading) {
    return (
      <div className="w-full max-w-4xl mx-auto p-6 space-y-4 animate-pulse">
        <div className="h-10 bg-surface-container-low rounded-2xl w-48" />
        <div className="h-20 bg-surface-container-low rounded-2xl" />
        <div className="h-20 bg-surface-container-low rounded-2xl" />
      </div>
    );
  }

  if (!isAuthenticated) {
    return (
      <div className="w-full max-w-xl mx-auto py-16 px-4 text-center space-y-4">
        <span className="material-symbols-outlined text-[48px] text-outline">
          lock
        </span>
        <h2 className="font-headline-sm text-xl font-bold text-on-surface">
          Yêu Cầu Đăng Nhập
        </h2>
        <p className="font-body-md text-sm text-on-surface-variant">
          Vui lòng đăng nhập để xem thông báo cá nhân của bạn.
        </p>
        <Link
          href="/dang-nhap?redirect=/tai-khoan/thong-bao"
          className="inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-primary text-on-primary font-label-md text-sm font-semibold hover:bg-primary/90 transition-all shadow-xs"
        >
          <span>Đăng Nhập</span>
        </Link>
      </div>
    );
  }

  return (
    <div className="w-full max-w-4xl mx-auto space-y-6">
      {/* Page Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-outline-variant/30 pb-4">
        <div>
          <div className="flex items-center gap-2">
            <span className="material-symbols-outlined text-primary text-[28px]">
              notifications
            </span>
            <h1 className="font-headline-sm text-2xl font-bold text-on-surface">
              Thông Báo
            </h1>
          </div>
          <p className="font-body-sm text-xs text-on-surface-variant mt-1">
            Cập nhật trạng thái duyệt nội dung và hoạt động đồng hành của bạn.
          </p>
        </div>

        <div className="flex items-center gap-2.5">
          <button
            type="button"
            onClick={handleMarkAllRead}
            disabled={isLoading || notifications.length === 0}
            className="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-outline-variant/40 hover:bg-surface-container-low text-on-surface font-label-md text-xs font-medium transition-all disabled:opacity-40 cursor-pointer"
          >
            <span className="material-symbols-outlined text-[16px] text-primary">
              done_all
            </span>
            <span>Đã đọc tất cả</span>
          </button>

          <Link
            href="/tai-khoan/cai-dat-thong-bao"
            className="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-outline-variant/40 hover:bg-surface-container-low text-on-surface font-label-md text-xs font-medium transition-all"
          >
            <span className="material-symbols-outlined text-[16px] text-outline">
              settings
            </span>
            <span>Cài đặt</span>
          </Link>
        </div>
      </div>

      {/* Action / Error Banner */}
      {actionMessage && (
        <div className="p-3 rounded-xl bg-primary/10 border border-primary/20 text-primary font-label-md text-xs flex items-center gap-2">
          <span className="material-symbols-outlined text-[18px]">check_circle</span>
          <span>{actionMessage}</span>
        </div>
      )}

      {error && (
        <div className="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 font-label-md text-xs flex items-center justify-between gap-2">
          <div className="flex items-center gap-2">
            <span className="material-symbols-outlined text-[18px]">error</span>
            <span>{error}</span>
          </div>
          <button
            type="button"
            onClick={() => fetchNotifications(filter, page)}
            className="underline font-semibold hover:opacity-80 cursor-pointer"
          >
            Thử lại
          </button>
        </div>
      )}

      {/* Filter Tabs */}
      <div className="flex items-center gap-2">
        <button
          type="button"
          onClick={() => handleFilterChange('all')}
          className={`px-4 py-2 rounded-xl font-label-md text-xs font-semibold transition-all cursor-pointer ${
            filter === 'all'
              ? 'bg-primary text-on-primary shadow-2xs'
              : 'bg-surface-container-low text-on-surface hover:bg-surface-container'
          }`}
        >
          <span>Tất cả</span>
          {filter === 'all' && totalCount > 0 && (
            <span className="ml-1.5 text-[11px] opacity-90">({totalCount})</span>
          )}
        </button>

        <button
          type="button"
          onClick={() => handleFilterChange('unread')}
          className={`px-4 py-2 rounded-xl font-label-md text-xs font-semibold transition-all cursor-pointer ${
            filter === 'unread'
              ? 'bg-primary text-on-primary shadow-2xs'
              : 'bg-surface-container-low text-on-surface hover:bg-surface-container'
          }`}
        >
          <span>Chưa đọc</span>
          {filter === 'unread' && totalCount > 0 && (
            <span className="ml-1.5 text-[11px] opacity-90">({totalCount})</span>
          )}
        </button>
      </div>

      {/* Notifications List */}
      <div className="space-y-3">
        {isLoading ? (
          <div className="space-y-3">
            {[1, 2, 3, 4].map((i) => (
              <div
                key={i}
                className="h-24 rounded-2xl bg-surface-container-low animate-pulse"
              />
            ))}
          </div>
        ) : notifications.length === 0 ? (
          <div className="py-16 px-4 text-center bg-surface-container-lowest rounded-3xl border border-outline-variant/30 space-y-3">
            <span className="material-symbols-outlined text-[48px] text-outline/50">
              {filter === 'unread' ? 'mark_email_read' : 'notifications_none'}
            </span>
            <h3 className="font-label-lg text-base font-semibold text-on-surface">
              {filter === 'unread'
                ? 'Bạn đã xem tất cả thông báo.'
                : 'Bạn chưa có thông báo nào.'}
            </h3>
            <p className="font-body-sm text-xs text-on-surface-variant max-w-sm mx-auto">
              {filter === 'unread'
                ? 'Không còn thông báo chưa đọc. Hãy chuyển sang tab "Tất cả" để xem lại các thông báo trước đó.'
                : 'Các thông báo về trạng thái duyệt lời nguyện và lời nhắn đồng hành sẽ hiển thị tại đây.'}
            </p>
          </div>
        ) : (
          notifications.map((item) => (
            <NotificationCard
              key={item.id}
              notification={item}
              onMarkRead={handleMarkRead}
              onDismiss={handleDismiss}
            />
          ))
        )}
      </div>

      {/* Pagination Controls */}
      {lastPage > 1 && (
        <div className="flex items-center justify-between pt-4 border-t border-outline-variant/30 text-xs font-label-md">
          <span className="text-on-surface-variant">
            Trang {page} / {lastPage} (Tổng {totalCount} thông báo)
          </span>

          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => {
                if (page > 1) {
                  const nextP = page - 1;
                  setPage(nextP);
                  fetchNotifications(filter, nextP);
                }
              }}
              disabled={page <= 1 || isLoading}
              className="px-3 py-1.5 rounded-lg border border-outline-variant/40 hover:bg-surface-container-low disabled:opacity-40 transition-colors cursor-pointer"
            >
              Trang trước
            </button>

            <button
              type="button"
              onClick={() => {
                if (page < lastPage) {
                  const nextP = page + 1;
                  setPage(nextP);
                  fetchNotifications(filter, nextP);
                }
              }}
              disabled={page >= lastPage || isLoading}
              className="px-3 py-1.5 rounded-lg border border-outline-variant/40 hover:bg-surface-container-low disabled:opacity-40 transition-colors cursor-pointer"
            >
              Trang sau
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
