import React from "react";
import Link from "next/link";

interface TempleNotFoundStateProps {
  message?: string;
}

export function TempleNotFoundState({
  message = "Địa điểm không tồn tại hoặc hiện chưa được chia sẻ công khai.",
}: TempleNotFoundStateProps) {
  return (
    <div className="max-w-2xl mx-auto px-4 py-20 text-center space-y-6">
      <div className="w-20 h-20 mx-auto rounded-full bg-surface-container-high/60 flex items-center justify-center text-primary">
        <span className="material-symbols-outlined text-[44px]">temple_buddhist</span>
      </div>

      <div className="space-y-2">
        <h1 className="font-headline-lg text-2xl sm:text-3xl font-serif font-bold text-on-surface">
          Không Tìm Thấy Địa Điểm
        </h1>
        <p className="font-body-md text-on-surface-variant max-w-md mx-auto leading-relaxed">
          {message}
        </p>
      </div>

      <div className="pt-2">
        <Link
          href="/tim-chua"
          className="inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-primary text-on-primary hover:bg-primary/90 font-label-md text-sm font-semibold transition-colors shadow-xs"
        >
          <span className="material-symbols-outlined text-[18px]">arrow_back</span>
          <span>Quay lại Chùa Việt Nam</span>
        </Link>
      </div>
    </div>
  );
}
