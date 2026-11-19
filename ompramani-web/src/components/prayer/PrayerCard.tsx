import React from 'react';
import Link from 'next/link';
import { PublicPrayerIntention } from '@/types/prayer';
import { PrayerParticipationButton } from './PrayerParticipationButton';

interface PrayerCardProps {
  prayer: PublicPrayerIntention;
  isDetailed?: boolean;
  isParticipating?: boolean;
  isOwner?: boolean;
}

export function PrayerCard({
  prayer,
  isDetailed = false,
  isParticipating = false,
  isOwner = false,
}: PrayerCardProps) {
  // Format published date in Vietnamese
  const formatDate = (isoString?: string) => {
    if (!isoString) return '';
    try {
      const d = new Date(isoString);
      return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
      }).format(d);
    } catch {
      return '';
    }
  };

  return (
    <article
      className={`rounded-3xl bg-surface-container-lowest border border-outline-variant/40 p-6 sm:p-7 shadow-xs hover:shadow-sm hover:border-primary/30 transition-all flex flex-col justify-between ${
        isDetailed ? 'max-w-3xl mx-auto ring-1 ring-primary/10' : ''
      }`}
    >
      <div className="space-y-4">
        {/* Header meta */}
        <div className="flex items-center justify-between gap-3 flex-wrap">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-full bg-primary-fixed/40 text-primary flex items-center justify-center shrink-0">
              <span className="material-symbols-outlined text-[18px]">
                spa
              </span>
            </div>
            <div>
              <span className="font-label-md text-label-md font-semibold text-on-surface block leading-tight">
                {prayer.display_author_label}
              </span>
              <span className="font-body-sm text-[11px] text-outline block">
                {formatDate(prayer.published_at)}
              </span>
            </div>
          </div>

          {prayer.category_label && (
            <span className="px-3 py-1 rounded-full bg-secondary-container/60 text-on-secondary-container font-label-md text-xs font-medium">
              {prayer.category_label}
            </span>
          )}
        </div>

        {/* Title if available */}
        {prayer.title && (
          <h3
            className={`font-headline-md font-semibold text-primary tracking-tight ${
              isDetailed ? 'text-2xl sm:text-3xl' : 'text-lg sm:text-xl'
            }`}
          >
            {prayer.title}
          </h3>
        )}

        {/* Prayer Body (Safe escaped text, preserving line breaks) */}
        <p
          className={`font-body-md text-on-surface-variant whitespace-pre-line leading-relaxed ${
            isDetailed ? 'text-base sm:text-lg' : 'text-sm sm:text-base line-clamp-6'
          }`}
        >
          {prayer.body}
        </p>
      </div>

      {/* Footer: Participation Action + Counter + Details link */}
      <div className="mt-5 pt-4 border-t border-outline-variant/20 flex items-center justify-between gap-3 flex-wrap">
        <PrayerParticipationButton
          publicId={prayer.public_id}
          initialCount={prayer.participation_count ?? 0}
          initialIsParticipating={isParticipating}
          isOwner={isOwner}
          compact={!isDetailed}
        />

        {!isDetailed && (
          <div className="flex items-center gap-3 ml-auto">
            {typeof prayer.approved_response_count === 'number' && prayer.approved_response_count > 0 && (
              <Link
                href={`/cau-nguyen/${prayer.public_id}#loi-nhan`}
                className="text-outline hover:text-primary font-label-md text-xs flex items-center gap-1 transition-colors"
                title={`${prayer.approved_response_count} lời nhắn đồng hành`}
              >
                <span className="material-symbols-outlined text-[15px]">chat_bubble</span>
                <span>{prayer.approved_response_count}</span>
              </Link>
            )}
            <Link
              href={`/cau-nguyen/${prayer.public_id}`}
              className="text-primary hover:text-on-primary-container font-label-md text-xs font-semibold flex items-center gap-1 transition-colors"
            >
              <span>Chi tiết</span>
              <span className="material-symbols-outlined text-[16px]">
                arrow_forward
              </span>
            </Link>
          </div>
        )}
      </div>
    </article>
  );
}
