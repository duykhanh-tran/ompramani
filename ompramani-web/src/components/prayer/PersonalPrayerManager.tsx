'use client';

import React, { useState, useEffect, useCallback } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/context/AuthContext';
import {
  getMyPrayerIntentions,
  deletePrayerIntention,
  withdrawPrayerIntention,
  submitPrayerIntentionForReview,
  updatePrayerIntention,
  getMyPrayerParticipations,
  withdrawPrayerParticipation,
} from '@/lib/api/prayers';
import {
  MyPrayerIntention,
  MyPrayerParticipation,
  PrayerCategoryKey,
} from '@/types/prayer';
import { ApiError } from '@/lib/api/client';
import { PersonalPrayerResponsesManager } from './PersonalPrayerResponsesManager';

type TabKey = 'all' | 'private' | 'pending' | 'shared' | 'rejected' | 'participated' | 'responses';

const TABS: { key: TabKey; label: string }[] = [
  { key: 'all', label: 'Tất cả' },
  { key: 'private', label: 'Riêng tư' },
  { key: 'pending', label: 'Chờ duyệt' },
  { key: 'shared', label: 'Đã chia sẻ' },
  { key: 'rejected', label: 'Chưa được duyệt' },
  { key: 'participated', label: 'Đã đồng hành' },
  { key: 'responses', label: 'Lời nhắn đã gửi' },
];

const CATEGORIES: { key: PrayerCategoryKey; label: string }[] = [
  { key: 'binh_an', label: 'Bình an & Cầu an' },
  { key: 'gia_dinh', label: 'Gia đình & Thân quyến' },
  { key: 'tri_an', label: 'Tri ân & Báo ân' },
  { key: 'hoi_huong', label: 'Hồi hướng công đức' },
  { key: 'thien_lanh', label: 'Lời nguyện thiện lành' },
  { key: 'khac', label: 'Khác' },
];

export function PersonalPrayerManager() {
  const router = useRouter();
  const { isAuthenticated, isLoading: isAuthLoading } = useAuth();

  const [activeTab, setActiveTab] = useState<TabKey>('all');
  const [prayers, setPrayers] = useState<MyPrayerIntention[]>([]);
  const [participations, setParticipations] = useState<MyPrayerParticipation[]>([]);
  const [withdrawingPublicId, setWithdrawingPublicId] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Modals state
  const [editingPrayer, setEditingPrayer] = useState<MyPrayerIntention | null>(null);
  const [editTitle, setEditTitle] = useState('');
  const [editCategory, setEditCategory] = useState<PrayerCategoryKey | ''>('binh_an');
  const [editBody, setEditBody] = useState('');
  const [editConsent, setEditConsent] = useState(false);
  const [isSubmittingEdit, setIsSubmittingEdit] = useState(false);
  const [editErrors, setEditErrors] = useState<Record<string, string[]>>({});

  // Submit Modal state
  const [submittingPrayer, setSubmittingPrayer] = useState<MyPrayerIntention | null>(null);
  const [submitConsent, setSubmitConsent] = useState(false);
  const [isSubmittingReview, setIsSubmittingReview] = useState(false);

  // Delete Confirm Modal state
  const [deletingId, setDeletingId] = useState<number | null>(null);
  const [isDeleting, setIsDeleting] = useState(false);

  const refetch = useCallback(async () => {
    if (activeTab === 'responses') {
      setIsLoading(false);
      return;
    }
    setIsLoading(true);
    setError(null);
    try {
      if (activeTab === 'participated') {
        const res = await getMyPrayerParticipations({ per_page: 30 });
        setParticipations(res.data || []);
      } else {
        const res = await getMyPrayerIntentions({
          tab: activeTab === 'all' ? undefined : activeTab,
          per_page: 30,
        });
        setPrayers(res.data || []);
      }
    } catch (err) {
      setError(
        err instanceof Error ? err.message : 'Không thể tải danh sách lời nguyện.'
      );
    } finally {
      setIsLoading(false);
    }
  }, [activeTab]);

  const fetchPrayers = refetch;

  useEffect(() => {
    if (isAuthLoading) return;
    if (!isAuthenticated) {
      router.push('/dang-nhap?redirect=/tai-khoan/loi-nguyen');
      return;
    }

    let isMounted = true;
    void Promise.resolve().then(async () => {
      if (!isMounted) return;
      if (activeTab === 'responses') {
        setIsLoading(false);
        return;
      }
      setIsLoading(true);
      setError(null);
      try {
        if (activeTab === 'participated') {
          const res = await getMyPrayerParticipations({ per_page: 30 });
          if (isMounted) setParticipations(res.data || []);
        } else {
          const res = await getMyPrayerIntentions({
            tab: activeTab === 'all' ? undefined : activeTab,
            per_page: 30,
          });
          if (isMounted) setPrayers(res.data || []);
        }
      } catch (err) {
        if (isMounted) {
          setError(
            err instanceof Error ? err.message : 'Không thể tải danh sách lời nguyện.'
          );
        }
      } finally {
        if (isMounted) setIsLoading(false);
      }
    });

    return () => {
      isMounted = false;
    };
  }, [isAuthenticated, isAuthLoading, router, activeTab]);

  const handleWithdrawParticipation = async (publicId: string) => {
    setWithdrawingPublicId(publicId);
    try {
      await withdrawPrayerParticipation(publicId);
      setParticipations((prev) =>
        prev.filter((p) => p.prayer_intention?.public_id !== publicId)
      );
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Có lỗi khi rút lại lượt đồng hành.');
    } finally {
      setWithdrawingPublicId(null);
    }
  };

  // Handle Withdraw
  const handleWithdraw = async (id: number) => {
    try {
      await withdrawPrayerIntention(id);
      await refetch();
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Có lỗi khi rút lời nguyện.');
    }
  };

  // Handle Submit for Review
  const handleConfirmSubmitForReview = async () => {
    if (!submittingPrayer) return;
    if (!submitConsent) {
      alert('Vui lòng xác nhận đồng ý với lưu ý quyền riêng tư.');
      return;
    }

    setIsSubmittingReview(true);
    try {
      await submitPrayerIntentionForReview(submittingPrayer.id, true);
      setSubmittingPrayer(null);
      setSubmitConsent(false);
      await refetch();
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Có lỗi khi gửi duyệt.');
    } finally {
      setIsSubmittingReview(false);
    }
  };

  // Open Edit Modal
  const openEditModal = (p: MyPrayerIntention) => {
    setEditingPrayer(p);
    setEditTitle(p.title || '');
    setEditCategory(p.category || 'binh_an');
    setEditBody(p.body);
    setEditConsent(false);
    setEditErrors({});
  };

  // Handle Save Edit
  const handleSaveEdit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingPrayer) return;

    if (editBody.trim().length < 20) {
      setEditErrors({
        body: ['Nội dung cần tối thiểu 20 ký tự.'],
      });
      return;
    }

    // If was approved, editing will require re-submitting with consent
    const isApproved = editingPrayer.moderation_status === 'approved';
    if (isApproved && !editConsent) {
      setEditErrors({
        consent: ['Vui lòng xác nhận đồng ý gửi duyệt lại sau khi chỉnh sửa.'],
      });
      return;
    }

    setIsSubmittingEdit(true);
    setEditErrors({});
    try {
      await updatePrayerIntention(editingPrayer.id, {
        title: editTitle.trim() || undefined,
        category: editCategory || undefined,
        body: editBody.trim(),
        visibility: isApproved ? 'public' : editingPrayer.visibility,
        consent_public_sharing: isApproved ? editConsent : undefined,
      });

      setEditingPrayer(null);
      await fetchPrayers();
    } catch (err) {
      if (err instanceof ApiError && err.errors) {
        setEditErrors(err.errors);
      } else {
        alert(err instanceof Error ? err.message : 'Có lỗi khi cập nhật.');
      }
    } finally {
      setIsSubmittingEdit(false);
    }
  };

  // Handle Delete
  const handleConfirmDelete = async () => {
    if (!deletingId) return;
    setIsDeleting(true);
    try {
      await deletePrayerIntention(deletingId);
      setDeletingId(null);
      await fetchPrayers();
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Có lỗi khi xóa.');
    } finally {
      setIsDeleting(false);
    }
  };

  const formatDate = (isoString?: string | null) => {
    if (!isoString) return '';
    try {
      return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      }).format(new Date(isoString));
    } catch {
      return '';
    }
  };

  return (
    <div className="max-w-4xl mx-auto space-y-8">
      {/* Page Title & CTA */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-outline-variant/30">
        <div>
          <h1 className="font-headline-lg text-2xl sm:text-3xl font-semibold text-primary">
            Lời Nguyện Của Tôi
          </h1>
          <p className="font-body-md text-sm text-on-surface-variant mt-1">
            Sổ tay lưu giữ và quản lý tâm nguyện tu học cá nhân.
          </p>
        </div>

        <Link
          href="/cau-nguyen/gui-loi-nguyen"
          className="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-primary text-on-primary hover:bg-on-primary-container font-label-md text-sm font-semibold shadow-xs transition-colors shrink-0"
        >
          <span className="material-symbols-outlined text-[18px]">
            add
          </span>
          <span>Viết lời nguyện mới</span>
        </Link>
      </div>

      {/* Tabs */}
      <div className="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none border-b border-outline-variant/30">
        {TABS.map((tab) => {
          const isActive = activeTab === tab.key;
          return (
            <button
              key={tab.key}
              type="button"
              onClick={() => setActiveTab(tab.key)}
              className={`px-4 py-2 rounded-full font-label-md text-sm font-medium transition-all ${
                isActive
                  ? 'bg-primary text-on-primary shadow-xs'
                  : 'bg-surface-container-low text-on-surface hover:bg-surface-container-high'
              }`}
            >
              {tab.label}
            </button>
          );
        })}
      </div>

      {/* Content State */}
      {isLoading ? (
        <div className="space-y-4 animate-pulse">
          {Array.from({ length: 3 }).map((_, i) => (
            <div
              key={i}
              className="h-44 rounded-3xl bg-surface-container-low border border-outline-variant/30"
            />
          ))}
        </div>
      ) : error ? (
        <div className="p-6 rounded-3xl bg-error/10 border border-error/30 text-error text-center space-y-2">
          <p className="font-semibold">{error}</p>
          <button
            type="button"
            onClick={fetchPrayers}
            className="px-4 py-2 rounded-full bg-error text-on-error font-label-md text-xs font-semibold"
          >
            Thử lại
          </button>
        </div>
      ) : activeTab === 'responses' ? (
        <PersonalPrayerResponsesManager />
      ) : activeTab === 'participated' ? (
        participations.length === 0 ? (
          <div className="rounded-3xl bg-surface-container-lowest border border-outline-variant/40 p-12 text-center max-w-lg mx-auto space-y-4 shadow-xs">
            <div className="w-14 h-14 rounded-full bg-primary-fixed/30 text-primary mx-auto flex items-center justify-center">
              <span className="material-symbols-outlined text-[28px]">
                volunteer_activism
              </span>
            </div>
            <div className="space-y-1">
              <h3 className="font-headline-md text-xl font-semibold text-on-surface">
                Bạn chưa đồng hành với lời nguyện nào
              </h3>
              <p className="font-body-md text-sm text-on-surface-variant">
                Khám phá không gian Cầu Nguyện Cộng Đồng để cùng cầu nguyện và lan tỏa thiện tâm.
              </p>
            </div>
            <div>
              <Link
                href="/cau-nguyen"
                className="inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-primary text-on-primary hover:bg-on-primary-container font-label-md text-sm font-semibold transition-colors"
              >
                <span className="material-symbols-outlined text-[18px]">
                  explore
                </span>
                <span>Khám phá Cầu Nguyện Cộng Đồng</span>
              </Link>
            </div>
          </div>
        ) : (
          <div className="space-y-4">
            {participations.map((part) => {
              const intention = part.prayer_intention;
              if (!intention) return null;

              const isWithdrawing = withdrawingPublicId === intention.public_id;

              return (
                <div
                  key={part.id}
                  className="rounded-3xl bg-surface-container-lowest border border-outline-variant/40 p-6 shadow-xs hover:border-primary/20 transition-all space-y-4"
                >
                  <div className="flex items-center justify-between gap-3 flex-wrap">
                    <div className="flex items-center gap-2 flex-wrap">
                      <span className="px-3 py-1 rounded-full font-label-md text-xs font-semibold flex items-center gap-1 bg-primary-container text-on-primary-container">
                        <span className="material-symbols-outlined text-[14px]">
                          check_circle
                        </span>
                        <span>Đã đồng hành</span>
                      </span>

                      {intention.category_label && (
                        <span className="px-3 py-1 rounded-full bg-surface-container-high text-on-surface-variant font-label-md text-xs font-medium">
                          {intention.category_label}
                        </span>
                      )}
                    </div>

                    <div className="text-right">
                      <span className="font-body-sm text-xs text-outline block">
                        Tham gia: {formatDate(part.participated_at)}
                      </span>
                    </div>
                  </div>

                  <div>
                    <h3 className="font-headline-md text-lg font-semibold text-primary">
                      {intention.title || 'Lời nguyện thanh tịnh'}
                    </h3>
                    <p className="font-body-md text-sm text-on-surface-variant mt-1.5 line-clamp-3 leading-relaxed whitespace-pre-line">
                      {intention.body}
                    </p>
                  </div>

                  <div className="pt-3 border-t border-outline-variant/20 flex items-center justify-between gap-3 flex-wrap">
                    <span className="font-label-md text-xs text-outline flex items-center gap-1.5">
                      <span className="material-symbols-outlined text-[16px] text-primary">
                        group
                      </span>
                      <span>
                        {intention.participation_count <= 0
                          ? 'Chưa có lượt đồng hành'
                          : `${intention.participation_count.toLocaleString('vi-VN')} người đồng hành`}
                      </span>
                    </span>

                    <div className="flex items-center gap-2">
                      <Link
                        href={`/cau-nguyen/${intention.public_id}`}
                        className="px-3.5 py-1.5 rounded-full bg-surface-container-low hover:bg-surface-container-high border border-outline-variant/60 text-primary font-label-md text-xs font-semibold flex items-center gap-1 transition-colors"
                      >
                        <span>Mở lời nguyện</span>
                        <span className="material-symbols-outlined text-[14px]">
                          arrow_forward
                        </span>
                      </Link>

                      <button
                        type="button"
                        disabled={isWithdrawing}
                        onClick={() => handleWithdrawParticipation(intention.public_id)}
                        className="px-3.5 py-1.5 rounded-full bg-surface-container-low hover:bg-surface-container-high border border-outline-variant/60 text-on-surface font-label-md text-xs font-semibold flex items-center gap-1 transition-colors disabled:opacity-50 cursor-pointer"
                      >
                        <span className="material-symbols-outlined text-[14px] text-outline">
                          undo
                        </span>
                        <span>{isWithdrawing ? 'Đang rút lại...' : 'Rút lại'}</span>
                      </button>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        )
      ) : prayers.length === 0 ? (
        <div className="rounded-3xl bg-surface-container-lowest border border-outline-variant/40 p-12 text-center max-w-lg mx-auto space-y-4 shadow-xs">
          <div className="w-14 h-14 rounded-full bg-primary-fixed/30 text-primary mx-auto flex items-center justify-center">
            <span className="material-symbols-outlined text-[28px]">
              history_edu
            </span>
          </div>
          <div className="space-y-1">
            <h3 className="font-headline-md text-xl font-semibold text-on-surface">
              Bạn chưa lưu lời nguyện nào
            </h3>
            <p className="font-body-md text-sm text-on-surface-variant">
              Hãy viết lời nguyện đầu tiên để lưu giữ trong sổ tay tu học hoặc chia sẻ cùng đại chúng.
            </p>
          </div>
          <div>
            <Link
              href="/cau-nguyen/gui-loi-nguyen"
              className="inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-primary text-on-primary hover:bg-on-primary-container font-label-md text-sm font-semibold transition-colors"
            >
              <span className="material-symbols-outlined text-[18px]">
                edit_note
              </span>
              <span>Viết lời nguyện</span>
            </Link>
          </div>
        </div>
      ) : (
        <div className="space-y-4">
          {prayers.map((prayer) => {
            const isApproved = prayer.moderation_status === 'approved' && !prayer.withdrawn_at;
            const isPending = prayer.moderation_status === 'pending';
            const isRejected = prayer.moderation_status === 'rejected';
            const isPrivate = prayer.visibility === 'private';

            return (
              <div
                key={prayer.id}
                className="rounded-3xl bg-surface-container-lowest border border-outline-variant/40 p-6 shadow-xs hover:border-primary/20 transition-all space-y-4"
              >
                {/* Meta Header */}
                <div className="flex items-center justify-between gap-3 flex-wrap">
                  <div className="flex items-center gap-2 flex-wrap">
                    {/* Status Badge */}
                    <span
                      className={`px-3 py-1 rounded-full font-label-md text-xs font-semibold flex items-center gap-1 ${
                        isApproved
                          ? 'bg-secondary-container text-on-secondary-container'
                          : isPending
                          ? 'bg-amber-500/15 text-amber-800 dark:text-amber-300'
                          : isRejected
                          ? 'bg-error/15 text-error'
                          : 'bg-surface-container-high text-on-surface-variant'
                      }`}
                    >
                      <span className="material-symbols-outlined text-[14px]">
                        {isApproved
                          ? 'check_circle'
                          : isPending
                          ? 'hourglass_top'
                          : isRejected
                          ? 'cancel'
                          : 'lock'}
                      </span>
                      <span>
                        {isApproved
                          ? 'Đã chia sẻ công khai'
                          : isPending
                          ? 'Đang chờ duyệt'
                          : isRejected
                          ? 'Chưa được chấp thuận'
                          : 'Riêng tư'}
                      </span>
                    </span>

                    {/* Category */}
                    {prayer.category_label && (
                      <span className="px-2.5 py-1 rounded-full bg-surface-container-low text-outline font-label-md text-xs">
                        {prayer.category_label}
                      </span>
                    )}
                  </div>

                  <span className="font-body-sm text-xs text-outline">
                    {formatDate(prayer.created_at)}
                  </span>
                </div>

                {/* Title */}
                {prayer.title && (
                  <h3 className="font-headline-md text-lg font-semibold text-primary">
                    {prayer.title}
                  </h3>
                )}

                {/* Body */}
                <p className="font-body-md text-sm text-on-surface-variant whitespace-pre-line leading-relaxed">
                  {prayer.body}
                </p>

                {/* Rejection Note if rejected */}
                {isRejected && prayer.user_rejection_note && (
                  <div className="p-3.5 rounded-2xl bg-error/10 border border-error/20 text-xs sm:text-sm text-error space-y-1">
                    <p className="font-semibold flex items-center gap-1">
                      <span className="material-symbols-outlined text-[16px]">
                        info
                      </span>
                      <span>Lý do phản hồi:</span>
                    </p>
                    <p>{prayer.user_rejection_note}</p>
                  </div>
                )}

                {/* Actions Footer */}
                <div className="pt-3 border-t border-outline-variant/20 flex items-center justify-between gap-3 flex-wrap">
                  <div className="flex items-center gap-2">
                    {/* View public link if approved */}
                    {isApproved && prayer.public_url && (
                      <Link
                        href={prayer.public_url}
                        target="_blank"
                        className="px-3 py-1.5 rounded-full bg-primary-fixed/30 text-primary hover:bg-primary-fixed font-label-md text-xs font-semibold flex items-center gap-1 transition-colors"
                      >
                        <span className="material-symbols-outlined text-[14px]">
                          visibility
                        </span>
                        <span>Xem trang công cộng</span>
                      </Link>
                    )}

                    {/* Submit for review button (if private or rejected) */}
                    {(isPrivate || isRejected) && (
                      <button
                        type="button"
                        onClick={() => {
                          setSubmittingPrayer(prayer);
                          setSubmitConsent(false);
                        }}
                        className="px-3.5 py-1.5 rounded-full bg-primary text-on-primary hover:bg-on-primary-container font-label-md text-xs font-semibold flex items-center gap-1 transition-colors"
                      >
                        <span className="material-symbols-outlined text-[14px]">
                          send
                        </span>
                        <span>{isRejected ? 'Gửi duyệt lại' : 'Gửi để chia sẻ'}</span>
                      </button>
                    )}

                    {/* Withdraw button (if pending or approved) */}
                    {(isPending || isApproved) && (
                      <button
                        type="button"
                        onClick={() => handleWithdraw(prayer.id)}
                        className="px-3.5 py-1.5 rounded-full bg-surface-container-low hover:bg-surface-container-high border border-outline-variant/60 text-on-surface font-label-md text-xs font-semibold flex items-center gap-1 transition-colors"
                      >
                        <span className="material-symbols-outlined text-[14px] text-outline">
                          lock
                        </span>
                        <span>Ngừng chia sẻ</span>
                      </button>
                    )}
                  </div>

                  <div className="flex items-center gap-2">
                    {/* Edit button */}
                    <button
                      type="button"
                      onClick={() => openEditModal(prayer)}
                      className="p-2 rounded-full text-outline hover:text-primary hover:bg-surface-container-low transition-colors"
                      title="Chỉnh sửa lời nguyện"
                    >
                      <span className="material-symbols-outlined text-[18px]">
                        edit
                      </span>
                    </button>

                    {/* Delete button */}
                    <button
                      type="button"
                      onClick={() => setDeletingId(prayer.id)}
                      className="p-2 rounded-full text-outline hover:text-error hover:bg-error/10 transition-colors"
                      title="Xóa lời nguyện"
                    >
                      <span className="material-symbols-outlined text-[18px]">
                        delete
                      </span>
                    </button>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* Edit Modal */}
      {editingPrayer && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-on-surface/40 backdrop-blur-xs animate-in fade-in duration-150"
          role="dialog"
          aria-modal="true"
        >
          <div className="relative w-full max-w-xl bg-surface-container-lowest rounded-3xl p-6 sm:p-8 shadow-xl border border-outline-variant/60 max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between pb-4 border-b border-outline-variant/30">
              <h2 className="font-headline-md text-xl font-semibold text-primary">
                Chỉnh Sửa Lời Nguyện
              </h2>
              <button
                type="button"
                onClick={() => setEditingPrayer(null)}
                className="p-1 rounded-full text-outline hover:text-on-surface"
              >
                <span className="material-symbols-outlined text-[20px]">close</span>
              </button>
            </div>

            <form onSubmit={handleSaveEdit} className="py-4 space-y-4">
              {editingPrayer.moderation_status === 'approved' && (
                <div className="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-xs text-amber-900 dark:text-amber-200">
                  <strong>Lưu ý bảo mật:</strong> Lời nguyện này đang được công khai. Khi bạn chỉnh sửa nội dung, bài đăng sẽ lập tức được rút khỏi cộng đồng và chuyển sang hàng chờ kiểm duyệt lại phiên bản mới.
                </div>
              )}

              <div className="space-y-1">
                <label className="font-label-md text-xs font-semibold text-on-surface">
                  Tiêu đề (tùy chọn)
                </label>
                <input
                  type="text"
                  value={editTitle}
                  onChange={(e) => setEditTitle(e.target.value)}
                  maxLength={255}
                  className="w-full px-4 py-2.5 rounded-xl bg-surface-container-low border border-outline-variant/60 text-sm text-on-surface focus:outline-none focus:ring-1 focus:ring-primary"
                />
              </div>

              <div className="space-y-1">
                <label className="font-label-md text-xs font-semibold text-on-surface">
                  Chủ đề
                </label>
                <select
                  value={editCategory}
                  onChange={(e) => setEditCategory(e.target.value as PrayerCategoryKey)}
                  className="w-full px-4 py-2.5 rounded-xl bg-surface-container-low border border-outline-variant/60 text-sm text-on-surface focus:outline-none focus:ring-1 focus:ring-primary"
                >
                  {CATEGORIES.map((c) => (
                    <option key={c.key} value={c.key}>
                      {c.label}
                    </option>
                  ))}
                </select>
              </div>

              <div className="space-y-1">
                <label className="font-label-md text-xs font-semibold text-on-surface">
                  Nội dung lời nguyện <span className="text-error">*</span>
                </label>
                <textarea
                  rows={5}
                  value={editBody}
                  onChange={(e) => setEditBody(e.target.value)}
                  maxLength={2000}
                  required
                  className="w-full px-4 py-2.5 rounded-xl bg-surface-container-low border border-outline-variant/60 text-sm text-on-surface focus:outline-none focus:ring-1 focus:ring-primary"
                />
                {editErrors.body && (
                  <p className="text-xs text-error">{editErrors.body[0]}</p>
                )}
              </div>

              {editingPrayer.moderation_status === 'approved' && (
                <label className="flex items-start gap-2 pt-2 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={editConsent}
                    onChange={(e) => setEditConsent(e.target.checked)}
                    className="mt-0.5 rounded text-primary focus:ring-primary"
                  />
                  <span className="text-xs text-on-surface-variant">
                    Tôi xác nhận nội dung không chứa thông tin cá nhân và đồng ý gửi kiểm duyệt lại.
                  </span>
                </label>
              )}
              {editErrors.consent && (
                <p className="text-xs text-error">{editErrors.consent[0]}</p>
              )}

              <div className="pt-3 border-t border-outline-variant/30 flex justify-end gap-3">
                <button
                  type="button"
                  onClick={() => setEditingPrayer(null)}
                  className="px-5 py-2 rounded-full border border-outline-variant/60 text-outline hover:text-on-surface font-label-md text-xs font-semibold"
                >
                  Hủy
                </button>
                <button
                  type="submit"
                  disabled={isSubmittingEdit}
                  className="px-6 py-2 rounded-full bg-primary text-on-primary hover:bg-on-primary-container disabled:opacity-50 font-label-md text-xs font-semibold transition-colors"
                >
                  {isSubmittingEdit ? 'Đang lưu...' : 'Lưu thay đổi'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Submit for Review Modal */}
      {submittingPrayer && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-on-surface/40 backdrop-blur-xs animate-in fade-in duration-150"
          role="dialog"
          aria-modal="true"
        >
          <div className="relative w-full max-w-lg bg-surface-container-lowest rounded-3xl p-6 sm:p-8 shadow-xl border border-outline-variant/60 space-y-4">
            <h3 className="font-headline-md text-xl font-semibold text-primary">
              Gửi Chia Sẻ Với Cộng Đồng
            </h3>

            <div className="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 space-y-2 text-xs sm:text-sm text-amber-900 dark:text-amber-200">
              <p className="font-semibold flex items-center gap-1.5">
                <span className="material-symbols-outlined text-[18px]">
                  privacy_tip
                </span>
                <span>Lưu ý bảo vệ quyền riêng tư</span>
              </p>
              <p className="text-xs leading-relaxed">
                Nội dung được duyệt sẽ hiển thị công khai dưới tên ẩn danh <strong>&quot;Một Phật tử&quot;</strong>. Xin bảo đảm bạn không ghi họ tên đầy đủ, số điện thoại, địa chỉ hoặc thông tin riêng tư.
              </p>
            </div>

            <label className="flex items-start gap-2.5 cursor-pointer pt-2">
              <input
                type="checkbox"
                checked={submitConsent}
                onChange={(e) => setSubmitConsent(e.target.checked)}
                className="mt-0.5 rounded text-primary focus:ring-primary"
              />
              <span className="text-xs text-on-surface">
                Tôi xác nhận nội dung không chứa thông tin định danh cá nhân và đồng ý gửi Ban Kiểm Duyệt thẩm định.
              </span>
            </label>

            <div className="pt-3 border-t border-outline-variant/30 flex justify-end gap-3">
              <button
                type="button"
                onClick={() => setSubmittingPrayer(null)}
                className="px-5 py-2 rounded-full border border-outline-variant/60 text-outline hover:text-on-surface font-label-md text-xs font-semibold"
              >
                Hủy bỏ
              </button>
              <button
                type="button"
                disabled={!submitConsent || isSubmittingReview}
                onClick={handleConfirmSubmitForReview}
                className="px-6 py-2 rounded-full bg-primary text-on-primary hover:bg-on-primary-container disabled:opacity-40 font-label-md text-xs font-semibold transition-colors"
              >
                {isSubmittingReview ? 'Đang gửi...' : 'Xác nhận gửi duyệt'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Delete Confirm Modal */}
      {deletingId && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-on-surface/40 backdrop-blur-xs animate-in fade-in duration-150"
          role="dialog"
          aria-modal="true"
        >
          <div className="relative w-full max-w-md bg-surface-container-lowest rounded-3xl p-6 sm:p-7 shadow-xl border border-outline-variant/60 space-y-4">
            <h3 className="font-headline-md text-lg font-semibold text-error flex items-center gap-1.5">
              <span className="material-symbols-outlined text-[20px]">
                warning
              </span>
              <span>Xác nhận xóa lời nguyện</span>
            </h3>

            <p className="font-body-md text-sm text-on-surface-variant">
              Lời nguyện này sẽ bị xóa khỏi tài khoản của bạn và không còn xuất hiện trên không gian cộng đồng (nếu đã chia sẻ). Bạn có chắc chắn muốn xóa?
            </p>

            <div className="pt-3 border-t border-outline-variant/30 flex justify-end gap-3">
              <button
                type="button"
                onClick={() => setDeletingId(null)}
                className="px-4 py-2 rounded-full border border-outline-variant/60 text-outline hover:text-on-surface font-label-md text-xs font-semibold"
              >
                Giữ lại
              </button>
              <button
                type="button"
                disabled={isDeleting}
                onClick={handleConfirmDelete}
                className="px-5 py-2 rounded-full bg-error text-on-error hover:bg-error/90 disabled:opacity-50 font-label-md text-xs font-semibold transition-colors"
              >
                {isDeleting ? 'Đang xóa...' : 'Xóa lời nguyện'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
