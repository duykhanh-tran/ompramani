'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { useRouter, useSearchParams } from 'next/navigation';
import { PublicPrayerIntention } from '@/types/prayer';
import { getPrayerParticipationStatuses } from '@/lib/api/prayers';
import { useAuth } from '@/context/AuthContext';
import { PrayerCard } from './PrayerCard';
import { ContentGuidelinesModal } from './ContentGuidelinesModal';

interface CommunityPrayerFeedProps {
  initialPrayers: PublicPrayerIntention[];
  currentPage: number;
  lastPage: number;
  total: number;
  currentCategory?: string;
}

const CATEGORY_TABS: { key: string; label: string }[] = [
  { key: '', label: 'Tất cả' },
  { key: 'binh_an', label: 'Bình an' },
  { key: 'gia_dinh', label: 'Gia đình' },
  { key: 'tri_an', label: 'Tri ân' },
  { key: 'hoi_huong', label: 'Hồi hướng' },
  { key: 'thien_lanh', label: 'Thiện lành' },
  { key: 'khac', label: 'Khác' },
];

export function CommunityPrayerFeed({
  initialPrayers,
  currentPage,
  lastPage,
  total,
  currentCategory = '',
}: CommunityPrayerFeedProps) {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { isAuthenticated } = useAuth();
  const [isGuidelinesOpen, setIsGuidelinesOpen] = useState(false);
  const [participationStatuses, setParticipationStatuses] = useState<Record<string, boolean>>({});

  useEffect(() => {
    if (!isAuthenticated || initialPrayers.length === 0) {
      return;
    }

    const publicIds = initialPrayers.map((p) => p.public_id);
    let isCancelled = false;

    getPrayerParticipationStatuses(publicIds)
      .then((res) => {
        if (!isCancelled && res?.data?.statuses) {
          setParticipationStatuses(res.data.statuses);
        }
      })
      .catch(() => {
        // Silently ignore viewer status error
      });

    return () => {
      isCancelled = true;
    };
  }, [isAuthenticated, initialPrayers]);

  const handleCategorySelect = (categoryKey: string) => {
    const params = new URLSearchParams(searchParams.toString());
    if (categoryKey) {
      params.set('category', categoryKey);
    } else {
      params.delete('category');
    }
    params.set('page', '1');
    router.push(`/cau-nguyen?${params.toString()}`);
  };

  const handlePageChange = (page: number) => {
    const params = new URLSearchParams(searchParams.toString());
    params.set('page', page.toString());
    router.push(`/cau-nguyen?${params.toString()}`);
  };

  return (
    <div className="space-y-8">
      {/* Action Bar & Category Filters */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-outline-variant/30">
        {/* Category Pills */}
        <div className="flex items-center gap-2 overflow-x-auto pb-2 md:pb-0 scrollbar-none">
          {CATEGORY_TABS.map((tab) => {
            const isActive = currentCategory === tab.key;
            return (
              <button
                key={tab.key}
                type="button"
                onClick={() => handleCategorySelect(tab.key)}
                className={`px-4 py-1.5 rounded-full font-label-md text-xs sm:text-sm font-medium whitespace-nowrap transition-all ${
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

        {/* Guidelines Link */}
        <button
          type="button"
          onClick={() => setIsGuidelinesOpen(true)}
          className="flex items-center gap-1.5 text-primary hover:text-on-primary-container font-label-md text-xs sm:text-sm font-semibold shrink-0 transition-colors"
        >
          <span className="material-symbols-outlined text-[18px]">
            help_outline
          </span>
          <span>Quy chuẩn &amp; Quyền riêng tư</span>
        </button>
      </div>

      {/* Prayers List */}
      {initialPrayers.length === 0 ? (
        <div className="rounded-3xl bg-surface-container-lowest border border-outline-variant/40 p-12 text-center max-w-xl mx-auto space-y-5 shadow-xs">
          <div className="w-16 h-16 rounded-full bg-primary-fixed/30 text-primary mx-auto flex items-center justify-center">
            <span className="material-symbols-outlined text-[32px]">
              self_improvement
            </span>
          </div>
          <div className="space-y-2">
            <h3 className="font-headline-md text-xl font-semibold text-on-surface">
              Chưa có lời nguyện nào được chia sẻ công khai
            </h3>
            <p className="font-body-md text-sm text-on-surface-variant max-w-sm mx-auto">
              Không gian thanh tịnh đang đón chào những tâm nguyện an lành đầu tiên từ quý Phật tử.
            </p>
          </div>
          <div>
            <Link
              href="/cau-nguyen/gui-loi-nguyen"
              className="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-primary text-on-primary hover:bg-on-primary-container font-label-md text-sm font-semibold shadow-xs transition-colors"
            >
              <span className="material-symbols-outlined text-[18px]">
                edit_note
              </span>
              <span>Gửi lời nguyện đầu tiên</span>
            </Link>
          </div>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {initialPrayers.map((prayer) => (
            <PrayerCard
              key={prayer.public_id}
              prayer={prayer}
              isParticipating={isAuthenticated ? Boolean(participationStatuses[prayer.public_id]) : false}
            />
          ))}
        </div>
      )}

      {/* Pagination Controls */}
      {lastPage > 1 && (
        <div className="flex items-center justify-center gap-3 pt-6">
          <button
            type="button"
            disabled={currentPage <= 1}
            onClick={() => handlePageChange(currentPage - 1)}
            className="px-4 py-2 rounded-full border border-outline-variant/60 bg-surface-container-lowest text-on-surface disabled:opacity-40 disabled:cursor-not-allowed hover:bg-surface-container-low font-label-md text-xs sm:text-sm font-medium transition-colors flex items-center gap-1"
          >
            <span className="material-symbols-outlined text-[18px]">
              chevron_left
            </span>
            <span>Trang trước</span>
          </button>

          <span className="font-label-md text-xs sm:text-sm text-outline px-2">
            Trang {currentPage} / {lastPage} ({total} lời nguyện)
          </span>

          <button
            type="button"
            disabled={currentPage >= lastPage}
            onClick={() => handlePageChange(currentPage + 1)}
            className="px-4 py-2 rounded-full border border-outline-variant/60 bg-surface-container-lowest text-on-surface disabled:opacity-40 disabled:cursor-not-allowed hover:bg-surface-container-low font-label-md text-xs sm:text-sm font-medium transition-colors flex items-center gap-1"
          >
            <span>Trang sau</span>
            <span className="material-symbols-outlined text-[18px]">
              chevron_right
            </span>
          </button>
        </div>
      )}

      {/* Guidelines Modal */}
      <ContentGuidelinesModal
        isOpen={isGuidelinesOpen}
        onClose={() => setIsGuidelinesOpen(false)}
      />
    </div>
  );
}
