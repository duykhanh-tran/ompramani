'use client';

import React from 'react';

interface PracticePlannerEmptyStateProps {
  onCreatePlan: () => void;
}

export function PracticePlannerEmptyState({
  onCreatePlan,
}: PracticePlannerEmptyStateProps) {
  return (
    <div className="p-10 sm:p-14 rounded-3xl bg-surface-container-lowest border border-outline-variant/30 text-center space-y-4">
      <div className="w-16 h-16 rounded-full bg-primary/10 text-primary mx-auto flex items-center justify-center">
        <span className="material-symbols-outlined text-[32px]">
          calendar_month
        </span>
      </div>
      <div className="max-w-md mx-auto space-y-2">
        <h3 className="font-serif text-lg sm:text-xl font-bold text-on-surface">
          Chưa có kế hoạch tu tập nào
        </h3>
        <p className="text-xs sm:text-sm font-body-md text-outline leading-relaxed">
          Kế hoạch tu tập cá nhân giúp quý vị sắp xếp thời khóa thiền định, niệm Phật, trì chú và đọc kinh theo nhịp sống riêng, an lạc và tự tại.
        </p>
      </div>
      <div className="pt-2">
        <button
          type="button"
          onClick={onCreatePlan}
          className="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-primary text-on-primary text-xs font-label-md font-bold shadow-xs hover:bg-primary-variant transition-colors cursor-pointer"
        >
          <span className="material-symbols-outlined text-[18px]">add</span>
          <span>Thiết lập kế hoạch đầu tiên</span>
        </button>
      </div>
    </div>
  );
}
