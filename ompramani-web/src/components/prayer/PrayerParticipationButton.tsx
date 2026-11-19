'use client';

import React, { useState, useEffect, useTransition } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/context/AuthContext';
import {
  participatePrayer,
  withdrawPrayerParticipation,
  getPrayerParticipationStatuses,
} from '@/lib/api/prayers';

interface PrayerParticipationButtonProps {
  publicId: string;
  initialCount: number;
  initialIsParticipating?: boolean;
  isOwner?: boolean;
  compact?: boolean;
  onCountChange?: (newCount: number, isParticipating: boolean) => void;
}

export function PrayerParticipationButton({
  publicId,
  initialCount,
  initialIsParticipating = false,
  isOwner = false,
  compact = false,
  onCountChange,
}: PrayerParticipationButtonProps) {
  const router = useRouter();
  const { isAuthenticated, isLoading: isAuthLoading } = useAuth();
  const [isPending, startTransition] = useTransition();

  const [mutationState, setMutationState] = useState<{
    isParticipating: boolean;
    count: number;
  } | null>(null);
  const [fetchedStatus, setFetchedStatus] = useState<boolean | null>(null);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [announcement, setAnnouncement] = useState<string>('');

  // Reconcile status with backend if authenticated and not yet mutated locally
  useEffect(() => {
    if (!isAuthenticated) {
      return;
    }

    let isCancelled = false;
    getPrayerParticipationStatuses([publicId])
      .then((res) => {
        if (!isCancelled && res?.data?.statuses && typeof res.data.statuses[publicId] === 'boolean') {
          setFetchedStatus(res.data.statuses[publicId]);
        }
      })
      .catch(() => {
        // Silently ignore reconciliation errors
      });

    return () => {
      isCancelled = true;
    };
  }, [isAuthenticated, publicId]);

  // Derived effective state without cascading renders
  const isParticipating = mutationState
    ? mutationState.isParticipating
    : isAuthenticated
    ? (fetchedStatus ?? initialIsParticipating)
    : false;

  const count = mutationState ? mutationState.count : (initialCount ?? 0);

  const formatCount = (c: number): string => {
    if (c <= 0) return 'Chưa có lượt đồng hành';
    if (c === 1) return '1 người đồng hành';
    return `${c.toLocaleString('vi-VN')} người đồng hành`;
  };

  const handleAction = async () => {
    // 1. Guest Handling
    if (!isAuthenticated) {
      if (typeof window !== 'undefined') {
        const returnUrl = `/cau-nguyen/${encodeURIComponent(publicId)}`;
        router.push(`/dang-nhap?redirect=${encodeURIComponent(returnUrl)}`);
      }
      return;
    }

    // 2. Owner Safeguard
    if (isOwner) {
      return;
    }

    if (isPending) return;

    setErrorMessage(null);

    // Optimistic UI updates
    const previousState = isParticipating;
    const previousCount = count;
    const nextState = !previousState;
    const nextCount = nextState ? previousCount + 1 : Math.max(0, previousCount - 1);

    setMutationState({
      isParticipating: nextState,
      count: nextCount,
    });

    startTransition(async () => {
      try {
        if (!previousState) {
          // Perform Join (PUT)
          const res = await participatePrayer(publicId);
          setMutationState({
            isParticipating: res.data.is_participating,
            count: res.data.participation_count,
          });
          setAnnouncement('Đã đồng hành với lời nguyện.');
          onCountChange?.(res.data.participation_count, res.data.is_participating);
        } else {
          // Perform Withdraw (DELETE)
          const res = await withdrawPrayerParticipation(publicId);
          setMutationState({
            isParticipating: res.data.is_participating,
            count: res.data.participation_count,
          });
          setAnnouncement('Đã rút lại lượt đồng hành.');
          onCountChange?.(res.data.participation_count, res.data.is_participating);
        }
      } catch (err: unknown) {
        // Rollback optimistic state
        setMutationState(null);
        const msg = err instanceof Error ? err.message : 'Không thể cập nhật lượt đồng hành.';
        setErrorMessage(msg);
      }
    });
  };

  if (isOwner) {
    return (
      <div className={`flex items-center gap-3 ${compact ? 'text-xs' : 'text-sm'}`}>
        <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-surface-container-high text-on-surface-variant font-label-md font-medium">
          <span className="material-symbols-outlined text-[16px] text-primary">person</span>
          <span>Lời nguyện của bạn</span>
        </span>
        <span className="text-outline font-label-md">{formatCount(count)}</span>
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-1.5">
      {/* Screen Reader Live Region */}
      <div aria-live="polite" className="sr-only">
        {announcement}
      </div>

      <div className="flex items-center gap-3 flex-wrap">
        <button
          type="button"
          onClick={handleAction}
          disabled={isPending || isAuthLoading}
          aria-pressed={isParticipating}
          aria-label={
            isParticipating
              ? 'Đã đồng hành với lời nguyện này. Nhấn để rút lại.'
              : 'Cùng cầu nguyện với lời nguyện này'
          }
          className={`inline-flex items-center gap-2 rounded-full font-label-md font-semibold transition-all select-none focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-primary/40 active:scale-98 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed ${
            compact ? 'px-3.5 py-1.5 text-xs' : 'px-5 py-2.5 text-sm'
          } ${
            isParticipating
              ? 'bg-primary-container text-on-primary-container hover:bg-primary-container/80 border border-primary/30 shadow-xs'
              : 'bg-surface-container hover:bg-surface-container-high text-on-surface border border-outline-variant/50 hover:border-primary/40'
          }`}
        >
          {isPending ? (
            <span className="material-symbols-outlined animate-spin text-[18px]">
              progress_activity
            </span>
          ) : isParticipating ? (
            <span className="material-symbols-outlined text-[18px] text-primary">
              check_circle
            </span>
          ) : (
            <span className="material-symbols-outlined text-[18px] text-primary">
              volunteer_activism
            </span>
          )}

          <span>
            {isPending
              ? isParticipating
                ? 'Đang rút lại...'
                : 'Đang ghi nhận...'
              : isParticipating
              ? 'Đã đồng hành'
              : 'Cùng cầu nguyện'}
          </span>
        </button>

        {/* Counter UI */}
        <span
          className={`font-label-md text-on-surface-variant/80 ${
            compact ? 'text-xs' : 'text-sm'
          }`}
        >
          {formatCount(count)}
        </span>
      </div>

      {/* Error Notice */}
      {errorMessage && (
        <span className="text-xs text-error font-body-sm leading-tight">
          {errorMessage}
        </span>
      )}
    </div>
  );
}
