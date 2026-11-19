'use client';

import React, { useState, useEffect, useRef, useCallback } from 'react';
import Link from 'next/link';
import { useAuth } from '@/context/AuthContext';
import {
  PrayerRoomMessage,
  PrayerRoomMessagesMeta,
} from '@/types/prayer-room';
import {
  getPrayerRoomMessages,
  sendPrayerRoomMessage,
  deletePrayerRoomMessage,
  reportPrayerRoomMessage,
} from '@/lib/api/prayer-room';

interface PrayerChatRoomProps {
  initialMessages: PrayerRoomMessage[];
  initialMeta: PrayerRoomMessagesMeta;
}

export function PrayerChatRoom({
  initialMessages,
  initialMeta,
}: PrayerChatRoomProps) {
  const { user, isAuthenticated } = useAuth();

  const [messages, setMessages] = useState<PrayerRoomMessage[]>(initialMessages);
  const [hasMoreOlder, setHasMoreOlder] = useState<boolean>(initialMeta.has_more_older);
  const [isLoadingOlder, setIsLoadingOlder] = useState<boolean>(false);
  const [isRefreshing, setIsRefreshing] = useState<boolean>(false);

  // Composer states
  const [inputText, setInputText] = useState<string>('');
  const [consentChecked, setConsentChecked] = useState<boolean>(false);
  const [replyingTo, setReplyingTo] = useState<PrayerRoomMessage | null>(null);
  const [isSending, setIsSending] = useState<boolean>(false);

  // Moderation / Action states
  const [reportingMessage, setReportingMessage] = useState<PrayerRoomMessage | null>(null);
  const [reportReason, setReportReason] = useState<
    'spam' | 'harassment' | 'personal_information' | 'fraud_or_solicitation' | 'misinformation' | 'unsafe_content' | 'other'
  >('spam');
  const [reportDetails, setReportDetails] = useState<string>('');
  const [isSubmittingReport, setIsSubmittingReport] = useState<boolean>(false);

  const [deleteConfirmTarget, setDeleteConfirmTarget] = useState<PrayerRoomMessage | null>(null);
  const [isDeleting, setIsDeleting] = useState<boolean>(false);

  // Notification Toast
  const [notification, setNotification] = useState<{
    type: 'success' | 'error';
    message: string;
  } | null>(null);

  // Scroll management
  const scrollContainerRef = useRef<HTMLDivElement>(null);
  const isNearBottomRef = useRef<boolean>(true);
  const textareaRef = useRef<HTMLTextAreaElement>(null);

  const showNotification = useCallback((type: 'success' | 'error', message: string) => {
    setNotification({ type, message });
    setTimeout(() => {
      setNotification((prev) => (prev?.message === message ? null : prev));
    }, 4500);
  }, []);

  const scrollToBottom = useCallback((smooth = true) => {
    if (!scrollContainerRef.current) return;
    scrollContainerRef.current.scrollTo({
      top: scrollContainerRef.current.scrollHeight,
      behavior: smooth ? 'smooth' : 'auto',
    });
  }, []);

  // Check scroll position
  const handleScroll = () => {
    if (!scrollContainerRef.current) return;
    const { scrollTop, scrollHeight, clientHeight } = scrollContainerRef.current;
    isNearBottomRef.current = scrollHeight - scrollTop - clientHeight < 120;
  };

  // Scroll to bottom on initial render
  useEffect(() => {
    scrollToBottom(false);
  }, [scrollToBottom]);

  // Polling for newer messages every 20 seconds
  useEffect(() => {
    const pollInterval = setInterval(async () => {
      if (document.hidden) return;
      if (messages.length === 0) return;

      const newestId = messages[messages.length - 1]?.public_id;
      if (!newestId) return;

      try {
        const res = await getPrayerRoomMessages({ after_id: newestId });
        if (res.data && res.data.length > 0) {
          setMessages((prev) => {
            const existingIds = new Set(prev.map((m) => m.public_id));
            const newItems = res.data.filter((m) => !existingIds.has(m.public_id));
            if (newItems.length === 0) return prev;
            return [...prev, ...newItems];
          });

          if (isNearBottomRef.current) {
            setTimeout(() => scrollToBottom(true), 100);
          }
        }
      } catch (err) {
        console.warn('Poll prayer room messages error:', err);
      }
    }, 20000);

    return () => clearInterval(pollInterval);
  }, [messages, scrollToBottom]);

  // Manual Refresh Handler
  const handleManualRefresh = async () => {
    setIsRefreshing(true);
    try {
      const res = await getPrayerRoomMessages({ limit: 30 });
      if (res.data) {
        setMessages(res.data);
        setHasMoreOlder(res.meta.has_more_older);
        setTimeout(() => scrollToBottom(false), 50);
        showNotification('success', 'Đã cập nhật tin nhắn mới nhất trong phòng.');
      }
    } catch {
      showNotification('error', 'Không thể làm mới phòng lời nguyện. Vui lòng thử lại.');
    } finally {
      setIsRefreshing(false);
    }
  };

  // Load older messages while preserving scroll position
  const handleLoadOlder = async () => {
    if (isLoadingOlder || !hasMoreOlder || messages.length === 0) return;

    const oldestId = messages[0]?.public_id;
    if (!oldestId) return;

    setIsLoadingOlder(true);
    const container = scrollContainerRef.current;
    const previousScrollHeight = container ? container.scrollHeight : 0;
    const previousScrollTop = container ? container.scrollTop : 0;

    try {
      const res = await getPrayerRoomMessages({ before_id: oldestId, limit: 25 });
      if (res.data && res.data.length > 0) {
        setMessages((prev) => {
          const existingIds = new Set(prev.map((m) => m.public_id));
          const olderItems = res.data.filter((m) => !existingIds.has(m.public_id));
          return [...olderItems, ...prev];
        });
        setHasMoreOlder(res.meta.has_more_older);

        // Restore scroll position so content does not jump
        requestAnimationFrame(() => {
          if (container) {
            const newScrollHeight = container.scrollHeight;
            container.scrollTop = previousScrollTop + (newScrollHeight - previousScrollHeight);
          }
        });
      } else {
        setHasMoreOlder(false);
      }
    } catch {
      showNotification('error', 'Không thể tải thêm lời nguyện cũ.');
    } finally {
      setIsLoadingOlder(false);
    }
  };

  // Submit new message
  const handleSendMessage = async (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    if (!isAuthenticated) return;

    const cleanText = inputText.trim();
    if (!cleanText || cleanText.length < 2) {
      showNotification('error', 'Nội dung lời nguyện quá ngắn (tối thiểu 2 ký tự).');
      return;
    }

    if (!consentChecked) {
      showNotification('error', 'Vui lòng xác nhận đồng ý chia sẻ công khai trước khi gửi.');
      return;
    }

    setIsSending(true);
    try {
      const res = await sendPrayerRoomMessage({
        body: cleanText,
        consent_public_sharing: true,
        reply_to_public_id: replyingTo ? replyingTo.public_id : null,
      });

      if (res.data) {
        setMessages((prev) => [...prev, res.data]);
        setInputText('');
        setConsentChecked(false);
        setReplyingTo(null);
        setTimeout(() => scrollToBottom(true), 100);
        showNotification(
          'success',
          'Lời nguyện của bạn đã được gửi thành công và đang chờ duyệt hiển thị.'
        );
      }
    } catch (err: unknown) {
      const errorMsg =
        err && typeof err === 'object' && 'message' in err
          ? String(err.message)
          : 'Không thể gửi lời nguyện. Vui lòng kiểm tra lại kết nối.';
      showNotification('error', errorMsg);
    } finally {
      setIsSending(false);
    }
  };

  // Enter to send, Shift+Enter for newline
  const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSendMessage();
    }
  };

  // Handle Delete / Recall message
  const handleConfirmDelete = async () => {
    if (!deleteConfirmTarget) return;

    setIsDeleting(true);
    try {
      await deletePrayerRoomMessage(deleteConfirmTarget.public_id);
      setMessages((prev) => prev.filter((m) => m.public_id !== deleteConfirmTarget.public_id));
      showNotification('success', 'Đã thu hồi / xóa tin nhắn thành công.');
      setDeleteConfirmTarget(null);
    } catch {
      showNotification('error', 'Không thể xóa tin nhắn hoặc bạn không có quyền.');
    } finally {
      setIsDeleting(false);
    }
  };

  // Handle Report Submit
  const handleConfirmReport = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!reportingMessage) return;

    setIsSubmittingReport(true);
    try {
      await reportPrayerRoomMessage(reportingMessage.public_id, {
        reason: reportReason,
        details: reportDetails.trim() || undefined,
      });
      showNotification('success', 'Báo cáo vi phạm đã được ghi nhận và gửi đến Ban Điều Hành.');
      setReportingMessage(null);
      setReportDetails('');
    } catch {
      showNotification('error', 'Không thể gửi báo cáo vi phạm hoặc đã báo cáo trước đó.');
    } finally {
      setIsSubmittingReport(false);
    }
  };

  // Format date helper for chat dividers
  const formatMessageDate = (isoString: string): string => {
    try {
      const d = new Date(isoString);
      const now = new Date();
      const isToday =
        d.getDate() === now.getDate() &&
        d.getMonth() === now.getMonth() &&
        d.getFullYear() === now.getFullYear();

      const yesterday = new Date(now);
      yesterday.setDate(now.getDate() - 1);
      const isYesterday =
        d.getDate() === yesterday.getDate() &&
        d.getMonth() === yesterday.getMonth() &&
        d.getFullYear() === yesterday.getFullYear();

      if (isToday) return 'Hôm nay';
      if (isYesterday) return 'Hôm qua';

      return `${d.getDate().toString().padStart(2, '0')}/${(d.getMonth() + 1)
        .toString()
        .padStart(2, '0')}/${d.getFullYear()}`;
    } catch {
      return '';
    }
  };

  // Format message time (HH:mm)
  const formatMessageTime = (isoString: string): string => {
    try {
      const d = new Date(isoString);
      return `${d.getHours().toString().padStart(2, '0')}:${d
        .getMinutes()
        .toString()
        .padStart(2, '0')}`;
    } catch {
      return '';
    }
  };

  return (
    <div className="w-full max-w-[1000px] mx-auto flex flex-col h-[calc(100vh-130px)] min-h-[580px] max-h-[880px] bg-surface-container-lowest rounded-3xl border border-outline-variant/40 shadow-sm overflow-hidden">
      {/* 1. Header of Prayer Room */}
      <div className="px-5 py-4 border-b border-outline-variant/30 bg-gradient-to-r from-surface-container-low via-surface-container-lowest to-surface-container-low flex items-center justify-between gap-4 shrink-0">
        <div className="flex items-center gap-3 min-w-0">
          <div className="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0 shadow-2xs">
            <span className="material-symbols-outlined text-[22px]">favorite</span>
          </div>
          <div className="min-w-0">
            <h1 className="font-serif font-bold text-lg sm:text-xl text-on-surface truncate">
              Phòng Lời Nguyện
            </h1>
            <p className="font-body-xs text-xs text-on-surface-variant truncate">
              Nơi chia sẻ lời nguyện thiện lành và cùng nhau hướng về sự an lạc
            </p>
          </div>
        </div>

        <div className="flex items-center gap-2 shrink-0">
          <button
            type="button"
            onClick={handleManualRefresh}
            disabled={isRefreshing}
            title="Làm mới phòng lời nguyện"
            className="p-2 rounded-xl text-on-surface-variant hover:text-primary hover:bg-surface-container-high transition-colors disabled:opacity-50"
            aria-label="Làm mới tin nhắn"
          >
            <span
              className={`material-symbols-outlined text-[20px] ${
                isRefreshing ? 'animate-spin' : ''
              }`}
            >
              refresh
            </span>
          </button>
        </div>
      </div>

      {/* Toast Notification Alert */}
      {notification && (
        <div
          role="status"
          className={`mx-4 mt-3 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-medium flex items-center justify-between gap-3 shadow-sm transition-all ${
            notification.type === 'success'
              ? 'bg-emerald-50 text-emerald-900 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-200 dark:border-emerald-800/40'
              : 'bg-rose-50 text-rose-900 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-200 dark:border-rose-800/40'
          }`}
        >
          <div className="flex items-center gap-2">
            <span className="material-symbols-outlined text-[18px]">
              {notification.type === 'success' ? 'check_circle' : 'error'}
            </span>
            <span>{notification.message}</span>
          </div>
          <button
            type="button"
            onClick={() => setNotification(null)}
            className="text-current opacity-70 hover:opacity-100"
          >
            <span className="material-symbols-outlined text-[16px]">close</span>
          </button>
        </div>
      )}

      {/* 2. Message Area (Chat Feed) */}
      <div
        ref={scrollContainerRef}
        onScroll={handleScroll}
        className="flex-1 p-4 sm:p-6 overflow-y-auto space-y-4 scrollbar-thin bg-surface/40"
      >
        {/* Load older messages button */}
        {hasMoreOlder && (
          <div className="text-center pb-2">
            <button
              type="button"
              onClick={handleLoadOlder}
              disabled={isLoadingOlder}
              className="px-4 py-1.5 rounded-full text-xs font-medium text-primary bg-primary/10 hover:bg-primary/20 transition-colors inline-flex items-center gap-1.5 disabled:opacity-50"
            >
              {isLoadingOlder && (
                <span className="material-symbols-outlined text-[16px] animate-spin">
                  progress_activity
                </span>
              )}
              <span>Tải các lời nguyện trước đó</span>
            </button>
          </div>
        )}

        {/* Empty state */}
        {messages.length === 0 && (
          <div className="h-full flex flex-col items-center justify-center text-center p-8 space-y-3">
            <div className="w-16 h-16 rounded-full bg-primary/10 text-primary flex items-center justify-center">
              <span className="material-symbols-outlined text-[32px]">spa</span>
            </div>
            <h2 className="font-serif font-bold text-lg text-on-surface">
              Chưa có lời nguyện nào được chia sẻ trong phòng
            </h2>
            <p className="font-body-sm text-sm text-on-surface-variant max-w-sm">
              Hãy là người đầu tiên gửi một lời nguyện bình an, hồi hướng thiện lành cho mọi người.
            </p>
          </div>
        )}

        {/* Messages Stream */}
        {messages.map((msg, index) => {
          const isOwn = msg.is_own || (user && false);
          const currentDate = formatMessageDate(msg.created_at);
          const prevDate =
            index > 0 ? formatMessageDate(messages[index - 1].created_at) : null;
          const showDateDivider = currentDate && currentDate !== prevDate;

          return (
            <React.Fragment key={msg.public_id}>
              {/* Date Divider */}
              {showDateDivider && (
                <div className="flex items-center justify-center my-4">
                  <span className="px-3 py-1 rounded-full bg-surface-container text-on-surface-variant/80 font-label-xs text-[11px] font-medium tracking-wide shadow-2xs">
                    {currentDate}
                  </span>
                </div>
              )}

              {/* Chat Bubble Row */}
              <div
                className={`flex items-end gap-2.5 group ${
                  isOwn ? 'justify-end' : 'justify-start'
                }`}
              >
                {/* Avatar for other members */}
                {!isOwn && (
                  <div
                    className="w-8 h-8 rounded-full bg-surface-container-high text-primary flex items-center justify-center shrink-0 mb-1 border border-outline-variant/30"
                    title={msg.display_name}
                  >
                    <span className="material-symbols-outlined text-[16px]">person</span>
                  </div>
                )}

                {/* Bubble Container */}
                <div
                  className={`max-w-[85%] sm:max-w-[70%] space-y-1 ${
                    isOwn ? 'items-end text-right' : 'items-start text-left'
                  }`}
                >
                  {/* Sender Name and Time */}
                  <div
                    className={`flex items-center gap-2 text-xs font-medium ${
                      isOwn
                        ? 'justify-end text-on-surface-variant'
                        : 'justify-start text-on-surface-variant'
                    }`}
                  >
                    <span className="text-[12px] font-medium">
                      {isOwn ? 'Bạn' : msg.display_name}
                    </span>
                    <span className="text-[10px] text-outline">
                      {formatMessageTime(msg.created_at)}
                    </span>

                    {/* Status Badge for Own Message */}
                    {isOwn && msg.moderation_status === 'pending' && (
                      <span className="px-2 py-0.5 rounded-full text-[10px] font-label-caps bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 flex items-center gap-1">
                        <span className="material-symbols-outlined text-[12px]">schedule</span>
                        Chờ duyệt
                      </span>
                    )}

                    {isOwn && msg.moderation_status === 'approved' && (
                      <span className="px-2 py-0.5 rounded-full text-[10px] font-label-caps bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 flex items-center gap-1">
                        <span className="material-symbols-outlined text-[12px]">check</span>
                        Công khai
                      </span>
                    )}
                  </div>

                  {/* Message Bubble Card */}
                  <div
                    className={`rounded-2xl p-3.5 sm:p-4 border text-sm sm:text-base leading-relaxed break-words whitespace-pre-wrap shadow-2xs ${
                      isOwn
                        ? 'bg-amber-50/80 dark:bg-amber-950/40 text-on-surface border-amber-800/20 rounded-br-xs'
                        : 'bg-surface-container-low text-on-surface border-outline-variant/40 rounded-bl-xs'
                    }`}
                  >
                    {/* Quoted Reply Context */}
                    {msg.reply_to && (
                      <div className="mb-2 p-2 rounded-lg bg-surface-container-high/60 border-l-3 border-primary/60 text-xs text-on-surface-variant space-y-0.5 text-left">
                        <span className="font-semibold block text-[11px] text-primary">
                          Trả lời {msg.reply_to.display_name}:
                        </span>
                        <p className="line-clamp-2 italic opacity-90">
                          {msg.reply_to.body_preview}
                        </p>
                      </div>
                    )}

                    {/* Body text */}
                    <div>{msg.body}</div>
                  </div>

                  {/* Bubble Actions (Hover or always accessible) */}
                  <div
                    className={`flex items-center gap-2 pt-0.5 text-xs text-on-surface-variant ${
                      isOwn ? 'justify-end' : 'justify-start'
                    }`}
                  >
                    {/* Reply Action */}
                    {isAuthenticated && !isOwn && (
                      <button
                        type="button"
                        onClick={() => {
                          setReplyingTo(msg);
                          textareaRef.current?.focus();
                        }}
                        className="opacity-70 hover:opacity-100 hover:text-primary inline-flex items-center gap-1 transition-opacity"
                        title="Trả lời lời nguyện này"
                      >
                        <span className="material-symbols-outlined text-[14px]">reply</span>
                        <span>Trả lời</span>
                      </button>
                    )}

                    {/* Report Action */}
                    {isAuthenticated && msg.can_report && (
                      <button
                        type="button"
                        onClick={() => setReportingMessage(msg)}
                        className="opacity-60 hover:opacity-100 hover:text-rose-600 inline-flex items-center gap-1 transition-opacity"
                        title="Báo cáo vi phạm"
                      >
                        <span className="material-symbols-outlined text-[14px]">flag</span>
                        <span>Báo cáo</span>
                      </button>
                    )}

                    {/* Delete / Recall Action */}
                    {msg.can_delete && (
                      <button
                        type="button"
                        onClick={() => setDeleteConfirmTarget(msg)}
                        className="opacity-60 hover:opacity-100 hover:text-rose-600 inline-flex items-center gap-1 transition-opacity"
                        title="Thu hồi lời nguyện"
                      >
                        <span className="material-symbols-outlined text-[14px]">delete</span>
                        <span>Thu hồi</span>
                      </button>
                    )}
                  </div>
                </div>
              </div>
            </React.Fragment>
          );
        })}
      </div>

      {/* 3. Composer Area */}
      <div className="p-4 sm:p-5 border-t border-outline-variant/30 bg-surface-container-low shrink-0 space-y-3">
        {/* Reply Context Banner */}
        {replyingTo && (
          <div className="px-3.5 py-2 rounded-xl bg-surface-container-high/70 border border-outline-variant/40 flex items-center justify-between gap-3 text-xs text-on-surface-variant animate-in fade-in duration-200">
            <div className="flex items-center gap-2 min-w-0">
              <span className="material-symbols-outlined text-[16px] text-primary shrink-0">
                reply
              </span>
              <span className="truncate">
                Đang trả lời <strong>{replyingTo.display_name}</strong>: &ldquo;
                {replyingTo.body.slice(0, 50)}
                {replyingTo.body.length > 50 ? '...' : ''}&rdquo;
              </span>
            </div>
            <button
              type="button"
              onClick={() => setReplyingTo(null)}
              className="text-on-surface-variant hover:text-on-surface p-1 rounded-full shrink-0"
              aria-label="Hủy trả lời"
            >
              <span className="material-symbols-outlined text-[16px]">close</span>
            </button>
          </div>
        )}

        {/* Authenticated User Composer */}
        {isAuthenticated ? (
          <form onSubmit={handleSendMessage} className="space-y-3">
            <div className="relative">
              <textarea
                ref={textareaRef}
                value={inputText}
                onChange={(e) => setInputText(e.target.value)}
                onKeyDown={handleKeyDown}
                placeholder={
                  replyingTo
                    ? `Viết lời nhắn đồng hành gửi ${replyingTo.display_name}...`
                    : 'Viết lời nguyện hoặc lời nhắn thiện lành gửi vào phòng...'
                }
                rows={2}
                maxLength={1000}
                className="w-full px-4 py-3 rounded-2xl bg-surface-container-lowest border border-outline-variant/60 focus:border-primary focus:ring-1 focus:ring-primary text-sm sm:text-base text-on-surface placeholder:text-outline resize-none transition-colors"
              />
              <div className="absolute right-3 bottom-2.5 text-[10px] text-outline font-label-xs">
                {inputText.length}/1000
              </div>
            </div>

            {/* Public Consent Checkbox */}
            <div className="flex items-center justify-between gap-3 flex-wrap text-xs text-on-surface-variant">
              <label className="flex items-center gap-2 cursor-pointer select-none">
                <input
                  type="checkbox"
                  checked={consentChecked}
                  onChange={(e) => setConsentChecked(e.target.checked)}
                  className="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary cursor-pointer"
                />
                <span className="leading-tight">
                  Tôi đồng ý hiển thị công khai lời nguyện trong phòng cộng đồng
                </span>
              </label>

              {/* Submit Button */}
              <button
                type="submit"
                disabled={isSending || !inputText.trim() || !consentChecked}
                className="px-5 py-2.5 rounded-full bg-primary text-on-primary hover:bg-on-primary-container font-label-md text-sm font-semibold inline-flex items-center gap-2 shadow-xs transition-all disabled:opacity-50 disabled:cursor-not-allowed shrink-0"
              >
                {isSending ? (
                  <>
                    <span className="material-symbols-outlined text-[18px] animate-spin">
                      progress_activity
                    </span>
                    <span>Đang gửi...</span>
                  </>
                ) : (
                  <>
                    <span className="material-symbols-outlined text-[18px]">send</span>
                    <span>Gửi công khai</span>
                  </>
                )}
              </button>
            </div>
          </form>
        ) : (
          /* Guest CTA */
          <div className="rounded-2xl p-4 bg-surface-container-lowest border border-outline-variant/40 text-center space-y-2">
            <p className="font-body-sm text-sm text-on-surface-variant">
              Đăng nhập để gửi lời nguyện hoặc gửi lời nhắn đồng hành cùng cộng đồng.
            </p>
            <div className="flex items-center justify-center gap-3">
              <Link
                href="/dang-nhap?redirect=/cau-nguyen"
                className="px-5 py-2 rounded-full bg-primary text-on-primary hover:bg-on-primary-container font-label-md text-xs font-semibold shadow-2xs transition-all"
              >
                Đăng nhập
              </Link>
              <Link
                href="/dang-ky"
                className="px-5 py-2 rounded-full bg-surface-container-high text-on-surface hover:bg-surface-container-highest font-label-md text-xs font-medium transition-all"
              >
                Đăng ký tài khoản
              </Link>
            </div>
          </div>
        )}
      </div>

      {/* 4. Modal: Report Message */}
      {reportingMessage && (
        <div
          role="dialog"
          aria-modal="true"
          className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 animate-in fade-in"
        >
          <div className="w-full max-w-md bg-surface-container-lowest rounded-3xl border border-outline-variant/50 p-6 space-y-4 shadow-xl">
            <div className="flex items-center justify-between border-b border-outline-variant/30 pb-3">
              <h3 className="font-serif font-bold text-lg text-on-surface flex items-center gap-2">
                <span className="material-symbols-outlined text-rose-600 text-[20px]">flag</span>
                Báo Cáo Nội Dung
              </h3>
              <button
                type="button"
                onClick={() => setReportingMessage(null)}
                className="text-on-surface-variant hover:text-on-surface"
              >
                <span className="material-symbols-outlined text-[20px]">close</span>
              </button>
            </div>

            <form onSubmit={handleConfirmReport} className="space-y-4">
              <div className="space-y-1.5">
                <label className="font-label-md text-xs font-semibold text-on-surface block">
                  Lý do vi phạm:
                </label>
                <select
                  value={reportReason}
                  onChange={(e) => setReportReason(e.target.value as typeof reportReason)}
                  className="w-full px-3 py-2 rounded-xl bg-surface-container-low border border-outline-variant/60 text-sm text-on-surface focus:ring-1 focus:ring-primary"
                >
                  <option value="spam">Spam / Quảng cáo / Trùng lặp</option>
                  <option value="harassment">Ngôn từ công kích / Thiếu hòa nhã</option>
                  <option value="personal_information">Tiết lộ thông tin cá nhân (PII)</option>
                  <option value="fraud_or_solicitation">Kêu gọi tiền bạc / Dấu hiệu gian lận</option>
                  <option value="misinformation">Thông tin sai lệch / Gây hiểu lầm</option>
                  <option value="unsafe_content">Nội dung nguy hiểm / Khủng hoảng</option>
                  <option value="other">Lý do khác</option>
                </select>
              </div>

              <div className="space-y-1.5">
                <label className="font-label-md text-xs font-semibold text-on-surface block">
                  Chi tiết thêm (tùy chọn):
                </label>
                <textarea
                  value={reportDetails}
                  onChange={(e) => setReportDetails(e.target.value)}
                  placeholder="Mô tả lý do bạn báo cáo nội dung này..."
                  rows={3}
                  maxLength={500}
                  className="w-full px-3 py-2 rounded-xl bg-surface-container-low border border-outline-variant/60 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary resize-none"
                />
              </div>

              <div className="flex items-center justify-end gap-3 pt-2">
                <button
                  type="button"
                  onClick={() => setReportingMessage(null)}
                  className="px-4 py-2 rounded-full text-xs font-medium text-on-surface-variant hover:bg-surface-container"
                >
                  Hủy bỏ
                </button>
                <button
                  type="submit"
                  disabled={isSubmittingReport}
                  className="px-5 py-2 rounded-full bg-rose-600 text-white hover:bg-rose-700 font-label-md text-xs font-semibold shadow-xs disabled:opacity-50"
                >
                  {isSubmittingReport ? 'Đang gửi...' : 'Gửi báo cáo'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* 5. Modal: Confirm Delete */}
      {deleteConfirmTarget && (
        <div
          role="dialog"
          aria-modal="true"
          className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 animate-in fade-in"
        >
          <div className="w-full max-w-sm bg-surface-container-lowest rounded-3xl border border-outline-variant/50 p-6 space-y-4 shadow-xl">
            <h3 className="font-serif font-bold text-lg text-on-surface">
              Xác Nhận Thu Hồi Tin Nhắn
            </h3>
            <p className="font-body-sm text-sm text-on-surface-variant">
              Bạn có chắc chắn muốn thu hồi lời nguyện này? Sau khi thu hồi, tin nhắn sẽ không còn
              hiển thị trong phòng cộng đồng.
            </p>
            <div className="flex items-center justify-end gap-3 pt-2">
              <button
                type="button"
                onClick={() => setDeleteConfirmTarget(null)}
                className="px-4 py-2 rounded-full text-xs font-medium text-on-surface-variant hover:bg-surface-container"
              >
                Hủy
              </button>
              <button
                type="button"
                onClick={handleConfirmDelete}
                disabled={isDeleting}
                className="px-5 py-2 rounded-full bg-rose-600 text-white hover:bg-rose-700 font-label-md text-xs font-semibold shadow-xs disabled:opacity-50"
              >
                {isDeleting ? 'Đang thu hồi...' : 'Thu hồi ngay'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
