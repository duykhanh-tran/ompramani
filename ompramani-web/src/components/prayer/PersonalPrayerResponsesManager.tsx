'use client';

import React, { useState, useEffect, useCallback } from 'react';
import Link from 'next/link';
import {
  MyPrayerResponse,
  PrayerResponseModerationStatus,
} from '@/types/prayer-response';
import {
  getMyPrayerResponses,
  updatePrayerResponse,
  withdrawPrayerResponse,
  deletePrayerResponse,
  resubmitPrayerResponse,
} from '@/lib/api/prayer-responses';

export function PersonalPrayerResponsesManager() {
  const [responses, setResponses] = useState<MyPrayerResponse[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Edit modal state
  const [editingResponse, setEditingResponse] = useState<MyPrayerResponse | null>(null);
  const [editBody, setEditBody] = useState('');
  const [isUpdating, setIsUpdating] = useState(false);
  const [updateError, setUpdateError] = useState<string | null>(null);

  // Delete modal state
  const [deletingId, setDeletingId] = useState<number | null>(null);
  const [isDeleting, setIsDeleting] = useState(false);

  // Action feedback
  const [feedback, setFeedback] = useState<string | null>(null);

  const fetchResponses = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      const res = await getMyPrayerResponses(1, 30);
      setResponses(res.data || []);
    } catch (err: unknown) {
      setError(
        err instanceof Error
          ? err.message
          : 'Không thể tải danh sách lời nhắn đã gửi.'
      );
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    let ignore = false;
    const load = async () => {
      try {
        const res = await getMyPrayerResponses(1, 30);
        if (!ignore) {
          setResponses(res.data || []);
        }
      } catch (err: unknown) {
        if (!ignore) {
          setError(
            err instanceof Error
              ? err.message
              : 'Không thể tải danh sách lời nhắn đã gửi.'
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
  }, []);

  const handleStartEdit = (resp: MyPrayerResponse) => {
    setEditingResponse(resp);
    setEditBody(resp.body);
    setUpdateError(null);
  };

  const handleSaveEdit = async () => {
    if (!editingResponse) return;
    if (editBody.trim().length < 3 || editBody.trim().length > 500) {
      setUpdateError('Nội dung lời nhắn cần từ 3 đến 500 ký tự.');
      return;
    }

    setIsUpdating(true);
    setUpdateError(null);
    try {
      if (editingResponse.moderation_status === 'rejected') {
        await resubmitPrayerResponse(editingResponse.id, editBody.trim());
        setFeedback('Đã gửi lại lời nhắn để kiểm duyệt.');
      } else {
        await updatePrayerResponse(editingResponse.id, editBody.trim());
        setFeedback(
          editingResponse.moderation_status === 'approved'
            ? 'Lời nhắn đã được cập nhật và chuyển về trạng thái chờ duyệt lại.'
            : 'Đã cập nhật lời nhắn thành công.'
        );
      }
      setEditingResponse(null);
      await fetchResponses();
    } catch (err: unknown) {
      setUpdateError(err instanceof Error ? err.message : 'Không thể lưu lời nhắn.');
    } finally {
      setIsUpdating(false);
    }
  };

  const handleWithdraw = async (id: number) => {
    try {
      await withdrawPrayerResponse(id);
      setFeedback('Đã rút lại lời nhắn khỏi hiển thị công khai.');
      await fetchResponses();
    } catch (err: unknown) {
      alert(err instanceof Error ? err.message : 'Không thể rút lại lời nhắn.');
    }
  };

  const handleDelete = async () => {
    if (!deletingId) return;
    setIsDeleting(true);
    try {
      await deletePrayerResponse(deletingId);
      setFeedback('Đã xóa lời nhắn thành công.');
      setDeletingId(null);
      await fetchResponses();
    } catch (err: unknown) {
      alert(err instanceof Error ? err.message : 'Không thể xóa lời nhắn.');
    } finally {
      setIsDeleting(false);
    }
  };

  const getStatusBadge = (status: PrayerResponseModerationStatus, withdrawnAt: string | null) => {
    if (withdrawnAt) {
      return (
        <span className="px-2.5 py-0.5 rounded-full bg-surface-container-high text-outline text-xs font-medium">
          Đã rút lại
        </span>
      );
    }

    switch (status) {
      case 'pending':
        return (
          <span className="px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20 text-xs font-medium">
            Chờ kiểm duyệt
          </span>
        );
      case 'approved':
        return (
          <span className="px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20 text-xs font-medium">
            Đã xuất bản
          </span>
        );
      case 'rejected':
        return (
          <span className="px-2.5 py-0.5 rounded-full bg-error-container/60 text-error border border-error/20 text-xs font-medium">
            Chưa được chấp thuận
          </span>
        );
      case 'revoked':
        return (
          <span className="px-2.5 py-0.5 rounded-full bg-surface-container-high text-outline text-xs font-medium">
            Đã thu hồi
          </span>
        );
      default:
        return null;
    }
  };

  if (isLoading) {
    return (
      <div className="space-y-3 py-4">
        {[1, 2, 3].map((i) => (
          <div key={i} className="h-28 rounded-2xl bg-surface-container-low animate-pulse" />
        ))}
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-4 rounded-2xl bg-error-container/30 border border-error/20 text-error text-xs text-center">
        {error}
      </div>
    );
  }

  if (responses.length === 0) {
    return (
      <div className="py-12 text-center rounded-3xl bg-surface-container-low/40 border border-dashed border-outline-variant/40 p-6 space-y-3">
        <div className="w-12 h-12 rounded-full bg-surface-container-high text-outline mx-auto flex items-center justify-center">
          <span className="material-symbols-outlined text-2xl">chat_bubble_outline</span>
        </div>
        <h4 className="font-headline-md text-base font-semibold text-on-surface">
          Bạn chưa gửi lời nhắn đồng hành nào
        </h4>
        <p className="font-body-sm text-xs text-outline max-w-md mx-auto">
          Mỗi lời nhắn đồng cảm, chúc bình an đều là sự kết nối thiện lành vô giá với người đang cần tiếp thêm niềm tin.
        </p>
        <div className="pt-2">
          <Link
            href="/cau-nguyen"
            className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary text-on-primary font-label-md text-xs font-semibold hover:bg-primary-hover transition-colors shadow-xs"
          >
            <span>Khám phá Lời Nguyện Cộng Đồng</span>
            <span className="material-symbols-outlined text-[16px]">arrow_forward</span>
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {feedback && (
        <div className="p-3.5 rounded-2xl bg-secondary-container/60 border border-secondary/30 text-on-secondary-container font-body-sm text-xs flex items-center justify-between gap-2">
          <div className="flex items-center gap-2">
            <span className="material-symbols-outlined text-[18px] text-primary">check_circle</span>
            <span>{feedback}</span>
          </div>
          <button
            type="button"
            onClick={() => setFeedback(null)}
            className="text-outline hover:text-on-surface"
          >
            <span className="material-symbols-outlined text-[16px]">close</span>
          </button>
        </div>
      )}

      <div className="space-y-3">
        {responses.map((resp) => (
          <div
            key={resp.id}
            className="rounded-2xl bg-surface-container-lowest border border-outline-variant/40 p-4 sm:p-5 shadow-2xs space-y-3"
          >
            <div className="flex items-center justify-between gap-3 flex-wrap">
              <div className="flex items-center gap-2">
                {getStatusBadge(resp.moderation_status, resp.withdrawn_at)}
                <span className="text-[11px] text-outline">
                  {new Date(resp.created_at).toLocaleDateString('vi-VN')}
                </span>
                {resp.submission_revision > 1 && (
                  <span className="text-[10px] text-outline px-1.5 py-0.5 rounded-md bg-surface-container">
                    bản sửa #{resp.submission_revision}
                  </span>
                )}
              </div>

              {resp.prayer_intention_public_id && (
                <Link
                  href={`/cau-nguyen/${resp.prayer_intention_public_id}`}
                  className="font-label-md text-xs text-primary hover:text-on-primary-container font-medium flex items-center gap-1"
                >
                  <span>Xem lời nguyện</span>
                  <span className="material-symbols-outlined text-[14px]">open_in_new</span>
                </Link>
              )}
            </div>

            {resp.prayer_intention_title && (
              <p className="font-label-md text-xs font-semibold text-outline">
                Gửi đến: <span className="text-on-surface">{resp.prayer_intention_title}</span>
              </p>
            )}

            <p className="font-body-md text-sm text-on-surface whitespace-pre-line leading-relaxed">
              {resp.body}
            </p>

            {/* If rejected, show explanation */}
            {resp.moderation_status === 'rejected' && resp.user_rejection_note && (
              <div className="p-3 rounded-xl bg-error-container/30 border border-error/20 text-error font-body-sm text-xs space-y-1">
                <span className="font-semibold block">Lý do chưa chấp thuận:</span>
                <span>{resp.user_rejection_note}</span>
              </div>
            )}

            {/* Actions Toolbar */}
            <div className="pt-2 border-t border-outline-variant/20 flex items-center justify-end gap-2">
              {resp.can_edit && (
                <button
                  type="button"
                  onClick={() => handleStartEdit(resp)}
                  className="px-3 py-1.5 rounded-lg border border-outline-variant/50 text-outline hover:text-primary hover:bg-surface-container font-label-md text-xs transition-colors"
                >
                  {resp.moderation_status === 'rejected' ? 'Sửa & Gửi lại' : 'Chỉnh sửa'}
                </button>
              )}

              {resp.can_withdraw && resp.moderation_status === 'approved' && !resp.withdrawn_at && (
                <button
                  type="button"
                  onClick={() => handleWithdraw(resp.id)}
                  className="px-3 py-1.5 rounded-lg border border-outline-variant/50 text-outline hover:text-on-surface hover:bg-surface-container font-label-md text-xs transition-colors"
                >
                  Rút lại
                </button>
              )}

              {resp.can_delete && (
                <button
                  type="button"
                  onClick={() => setDeletingId(resp.id)}
                  className="px-3 py-1.5 rounded-lg text-outline/70 hover:text-error hover:bg-error-container/20 font-label-md text-xs transition-colors"
                >
                  Xóa
                </button>
              )}
            </div>
          </div>
        ))}
      </div>

      {/* Edit Modal */}
      {editingResponse && (
        <div
          role="dialog"
          aria-modal="true"
          className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in"
        >
          <div className="w-full max-w-lg rounded-3xl bg-surface-container-lowest border border-outline-variant/60 shadow-xl p-6 sm:p-7 space-y-4">
            <h3 className="font-headline-md text-lg font-bold text-on-surface">
              {editingResponse.moderation_status === 'rejected'
                ? 'Chỉnh Sửa & Gửi Duyệt Lại'
                : 'Chỉnh Sửa Lời Nhắn'}
            </h3>

            {editingResponse.moderation_status === 'approved' && (
              <div className="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-300 font-body-sm text-xs flex items-start gap-2">
                <span className="material-symbols-outlined text-[16px] shrink-0 mt-0.5">warning</span>
                <span>
                  Lưu ý: Chỉnh sửa lời nhắn đã xuất bản sẽ tạm gỡ nội dung khỏi trang cộng đồng và chuyển về trạng thái chờ kiểm duyệt lại phiên bản mới.
                </span>
              </div>
            )}

            {updateError && (
              <div className="p-3 rounded-2xl bg-error-container/40 border border-error/20 text-error font-body-sm text-xs">
                {updateError}
              </div>
            )}

            <div>
              <label htmlFor="edit-response-body" className="sr-only">
                Nội dung lời nhắn
              </label>
              <textarea
                id="edit-response-body"
                rows={4}
                maxLength={500}
                value={editBody}
                onChange={(e) => setEditBody(e.target.value)}
                className="w-full px-3.5 py-2.5 rounded-2xl bg-surface-container-low border border-outline-variant/60 font-body-md text-sm text-on-surface focus:outline-hidden focus:border-primary focus:ring-1 focus:ring-primary transition-colors resize-none"
              />
              <div className="mt-1 flex items-center justify-between text-[11px] text-outline px-1">
                <span>3 - 500 ký tự</span>
                <span>{editBody.trim().length}/500</span>
              </div>
            </div>

            <div className="flex items-center justify-end gap-2.5 pt-2">
              <button
                type="button"
                onClick={() => setEditingResponse(null)}
                disabled={isUpdating}
                className="px-4 py-2 rounded-xl border border-outline-variant/50 text-outline hover:text-on-surface hover:bg-surface-container font-label-md text-xs font-medium"
              >
                Hủy bỏ
              </button>
              <button
                type="button"
                onClick={handleSaveEdit}
                disabled={isUpdating || editBody.trim().length < 3 || editBody.trim().length > 500}
                className="px-4 py-2 rounded-xl bg-primary text-on-primary font-label-md text-xs font-semibold hover:bg-primary-hover shadow-xs disabled:opacity-40"
              >
                {isUpdating ? 'Đang lưu...' : 'Lưu & Gửi duyệt'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Delete Confirmation Modal */}
      {deletingId && (
        <div
          role="dialog"
          aria-modal="true"
          className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in"
        >
          <div className="w-full max-w-sm rounded-3xl bg-surface-container-lowest border border-outline-variant/60 shadow-xl p-6 space-y-4 text-center">
            <div className="w-11 h-11 rounded-full bg-error-container/60 text-error mx-auto flex items-center justify-center">
              <span className="material-symbols-outlined text-2xl">delete</span>
            </div>
            <h3 className="font-headline-md text-base font-bold text-on-surface">
              Xóa lời nhắn này?
            </h3>
            <p className="font-body-sm text-xs text-outline">
              Hành động này sẽ xóa lời nhắn khỏi danh sách của bạn và không thể hoàn tác.
            </p>
            <div className="flex items-center justify-center gap-2.5 pt-2">
              <button
                type="button"
                onClick={() => setDeletingId(null)}
                disabled={isDeleting}
                className="px-4 py-2 rounded-xl border border-outline-variant/50 text-outline hover:bg-surface-container font-label-md text-xs font-medium"
              >
                Hủy
              </button>
              <button
                type="button"
                onClick={handleDelete}
                disabled={isDeleting}
                className="px-4 py-2 rounded-xl bg-error text-on-error font-label-md text-xs font-semibold hover:bg-error/90 shadow-xs disabled:opacity-50"
              >
                {isDeleting ? 'Đang xóa...' : 'Xác nhận xóa'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
