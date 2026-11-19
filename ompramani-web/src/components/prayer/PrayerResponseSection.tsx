'use client';

import React, { useState, useEffect, useCallback } from 'react';
import Link from 'next/link';
import { useAuth } from '@/context/AuthContext';
import { PublicPrayerResponse } from '@/types/prayer-response';
import { getPublicPrayerResponses } from '@/lib/api/prayer-responses';
import { PrayerResponseCard } from './PrayerResponseCard';
import { SubmitPrayerResponseForm } from './SubmitPrayerResponseForm';

interface PrayerResponseSectionProps {
  publicId: string;
}

export function PrayerResponseSection({ publicId }: PrayerResponseSectionProps) {
  const { isAuthenticated, isLoading: isAuthLoading } = useAuth();

  const [responses, setResponses] = useState<PublicPrayerResponse[]>([]);
  const [totalCount, setTotalCount] = useState<number>(0);
  const [currentPage, setCurrentPage] = useState<number>(1);
  const [lastPage, setLastPage] = useState<number>(1);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);

  // Load public approved responses
  const fetchResponses = useCallback(
    async (pageToLoad: number) => {
      setIsLoading(true);
      setError(null);
      try {
        const res = await getPublicPrayerResponses(publicId, pageToLoad, 10);
        if (pageToLoad === 1) {
          setResponses(res.data);
        } else {
          setResponses((prev) => [...prev, ...res.data]);
        }
        setCurrentPage(res.meta?.current_page || 1);
        setLastPage(res.meta?.last_page || 1);
        setTotalCount(res.meta?.total || res.data.length);
      } catch (err: unknown) {
        setError(
          err instanceof Error ? err.message : 'Không thể tải danh sách lời nhắn.'
        );
      } finally {
        setIsLoading(false);
      }
    },
    [publicId]
  );

  useEffect(() => {
    let ignore = false;
    const load = async () => {
      try {
        const res = await getPublicPrayerResponses(publicId, 1, 10);
        if (!ignore) {
          setResponses(res.data);
          setCurrentPage(res.meta?.current_page || 1);
          setLastPage(res.meta?.last_page || 1);
          setTotalCount(res.meta?.total || res.data.length);
        }
      } catch (err: unknown) {
        if (!ignore) {
          setError(
            err instanceof Error ? err.message : 'Không thể tải danh sách lời nhắn.'
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
  }, [publicId]);

  const handleResponseSubmitted = () => {
    // Note: Submitted response is pending moderation, so it is not immediately visible in public list
    // User sees immediate feedback via the form banner
  };

  return (
    <section id="loi-nhan" className="max-w-3xl mx-auto space-y-6 pt-6">
      {/* Section Header */}
      <div className="flex items-center justify-between gap-3 border-b border-outline-variant/30 pb-3">
        <div>
          <div className="flex items-center gap-2">
            <h3 className="font-headline-md text-xl font-bold text-on-surface">
              Lời Nhắn Đồng Hành
            </h3>
            {totalCount > 0 && (
              <span className="px-2.5 py-0.5 rounded-full bg-secondary-container/60 text-on-secondary-container font-label-md text-xs font-semibold">
                {totalCount}
              </span>
            )}
          </div>
          <p className="font-body-sm text-xs text-outline mt-0.5">
            Những lời động viên thiện lành được chia sẻ cùng người gửi lời nguyện.
          </p>
        </div>
      </div>

      {/* Response Submission or Login CTA */}
      {!isAuthLoading && (
        <div>
          {isAuthenticated ? (
            <SubmitPrayerResponseForm
              publicId={publicId}
              onResponseSubmitted={handleResponseSubmitted}
            />
          ) : (
            <div className="rounded-3xl bg-surface-container-low border border-outline-variant/40 p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div className="space-y-1">
                <h4 className="font-label-md text-sm font-semibold text-on-surface flex items-center gap-1.5">
                  <span className="material-symbols-outlined text-primary text-[18px]">
                    chat_bubble
                  </span>
                  <span>Gửi lời động viên đến lời nguyện này</span>
                </h4>
                <p className="font-body-sm text-xs text-outline">
                  Đăng nhập để chia sẻ những lời chúc bình an và thiện lành.
                </p>
              </div>

              <Link
                href={`/dang-nhap?redirect=${encodeURIComponent(
                  typeof window !== 'undefined' ? window.location.pathname : `/cau-nguyen/${publicId}`
                )}`}
                className="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-primary text-on-primary font-label-md text-xs font-semibold hover:bg-primary-hover transition-colors shadow-xs shrink-0"
              >
                <span>Đăng nhập</span>
                <span className="material-symbols-outlined text-[16px]">arrow_forward</span>
              </Link>
            </div>
          )}
        </div>
      )}

      {/* Responses List */}
      <div className="space-y-3.5">
        {isLoading && responses.length === 0 ? (
          <div className="space-y-3">
            {[1, 2].map((i) => (
              <div
                key={i}
                className="h-24 rounded-2xl bg-surface-container-low animate-pulse"
              />
            ))}
          </div>
        ) : error ? (
          <div className="p-4 rounded-2xl bg-error-container/30 border border-error/20 text-error font-body-sm text-xs text-center">
            {error}
          </div>
        ) : responses.length === 0 ? (
          <div className="py-10 text-center rounded-3xl bg-surface-container-low/40 border border-dashed border-outline-variant/40 p-6 space-y-2">
            <div className="w-10 h-10 rounded-full bg-surface-container-high text-outline mx-auto flex items-center justify-center">
              <span className="material-symbols-outlined text-[20px]">
                chat_bubble_outline
              </span>
            </div>
            <p className="font-label-md text-sm font-semibold text-on-surface">
              Chưa có lời nhắn đồng hành
            </p>
            <p className="font-body-sm text-xs text-outline max-w-sm mx-auto">
              Hãy là người đầu tiên gửi những lời chúc an lành và sự thấu cảm đến
              người gửi lời nguyện.
            </p>
          </div>
        ) : (
          <>
            {responses.map((resp) => (
              <PrayerResponseCard key={resp.public_id} response={resp} />
            ))}

            {/* Load More Button */}
            {currentPage < lastPage && (
              <div className="pt-2 text-center">
                <button
                  type="button"
                  onClick={() => fetchResponses(currentPage + 1)}
                  disabled={isLoading}
                  className="px-4 py-2 rounded-xl border border-outline-variant/50 text-outline hover:text-primary hover:bg-surface-container font-label-md text-xs font-semibold transition-colors disabled:opacity-50"
                >
                  {isLoading ? 'Đang tải thêm...' : 'Xem thêm lời nhắn'}
                </button>
              </div>
            )}
          </>
        )}
      </div>
    </section>
  );
}
