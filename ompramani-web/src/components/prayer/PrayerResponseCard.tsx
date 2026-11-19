'use client';

import React, { useState } from 'react';
import { PublicPrayerResponse } from '@/types/prayer-response';
import { PrayerResponseReportDialog } from './PrayerResponseReportDialog';

interface PrayerResponseCardProps {
  response: PublicPrayerResponse;
}

export function PrayerResponseCard({ response }: PrayerResponseCardProps) {
  const [isReportOpen, setIsReportOpen] = useState(false);

  const formatDate = (isoString?: string) => {
    if (!isoString) return '';
    try {
      const d = new Date(isoString);
      return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      }).format(d);
    } catch {
      return '';
    }
  };

  return (
    <article className="rounded-2xl bg-surface-container-lowest border border-outline-variant/30 p-4 sm:p-5 shadow-2xs hover:border-outline-variant/60 transition-colors">
      <div className="flex items-start justify-between gap-3">
        {/* Author metadata */}
        <div className="flex items-center gap-2.5">
          <div className="w-7 h-7 rounded-full bg-secondary-container/60 text-on-secondary-container flex items-center justify-center shrink-0">
            <span className="material-symbols-outlined text-[15px]">spa</span>
          </div>
          <div>
            <span className="font-label-md text-xs font-semibold text-on-surface block leading-tight">
              {response.display_author_label}
            </span>
            <time className="font-body-sm text-[11px] text-outline block">
              {formatDate(response.published_at)}
            </time>
          </div>
        </div>

        {/* Report Button */}
        <button
          type="button"
          onClick={() => setIsReportOpen(true)}
          className="text-outline/70 hover:text-error hover:bg-error-container/20 p-1.5 rounded-lg text-xs transition-colors flex items-center gap-1 shrink-0"
          title="Báo cáo nội dung không phù hợp"
          aria-label="Báo cáo nội dung"
        >
          <span className="material-symbols-outlined text-[16px]">flag</span>
        </button>
      </div>

      {/* Response Body */}
      <p className="mt-3 font-body-md text-sm text-on-surface-variant whitespace-pre-line leading-relaxed">
        {response.body}
      </p>

      {/* Report Modal */}
      <PrayerResponseReportDialog
        responsePublicId={response.public_id}
        isOpen={isReportOpen}
        onClose={() => setIsReportOpen(false)}
      />
    </article>
  );
}
