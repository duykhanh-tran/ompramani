"use client";

import React from "react";
import { PracticeSession } from "@/types/practice";

interface MeditationConflictModalProps {
  session: PracticeSession;
  onFinishOldAndStart: () => Promise<void> | void;
  onCancelOldAndStart: () => Promise<void> | void;
  onDismiss: () => void;
  isLoading?: boolean;
}

export function MeditationConflictModal({
  session,
  onFinishOldAndStart,
  onCancelOldAndStart,
  onDismiss,
  isLoading = false,
}: MeditationConflictModalProps) {
  return (
    <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 animate-in fade-in duration-150">
      <div className="bg-surface-container-lowest border border-outline-variant/50 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-5">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center shrink-0">
            <span className="material-symbols-outlined text-[24px] text-primary">
              warning
            </span>
          </div>
          <div>
            <h3 className="font-headline-md text-base sm:text-lg font-semibold text-on-surface">
              Phiên tu tập đang diễn ra
            </h3>
            <p className="font-body-sm text-xs text-outline">
              Bạn đang có phiên {session.practice_type_label} chưa kết thúc.
            </p>
          </div>
        </div>

        <p className="font-body-sm text-xs text-on-surface-variant leading-relaxed">
          Theo nguyên tắc chánh niệm của OmPraMani, tại một thời điểm mỗi hành giả chỉ nên chuyên chú vào một phương tiện tu tập. Bạn có muốn kết thúc phiên cũ để bắt đầu thời thiền không?
        </p>

        <div className="flex flex-col gap-2 pt-2">
          <button
            type="button"
            suppressHydrationWarning
            disabled={isLoading}
            onClick={onFinishOldAndStart}
            className="w-full py-3 rounded-xl bg-primary text-on-primary font-label-md text-xs font-semibold hover:bg-primary-hover transition-colors shadow-xs cursor-pointer disabled:opacity-50"
          >
            Hoàn thành phiên cũ &amp; Bắt đầu thiền
          </button>

          <button
            type="button"
            suppressHydrationWarning
            disabled={isLoading}
            onClick={onCancelOldAndStart}
            className="w-full py-2.5 rounded-xl bg-surface-container-low text-on-surface border border-outline-variant/60 font-label-md text-xs hover:bg-surface-container transition-colors cursor-pointer disabled:opacity-50"
          >
            Hủy phiên cũ &amp; Bắt đầu thiền
          </button>

          <button
            type="button"
            suppressHydrationWarning
            disabled={isLoading}
            onClick={onDismiss}
            className="w-full py-2 text-outline hover:text-on-surface font-label-md text-xs transition-colors cursor-pointer"
          >
            Quay lại
          </button>
        </div>
      </div>
    </div>
  );
}
