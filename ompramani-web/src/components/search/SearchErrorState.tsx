import React from "react";
import Link from "next/link";

interface SearchErrorStateProps {
  message?: string;
}

export function SearchErrorState({ message }: SearchErrorStateProps) {
  return (
    <div className="py-12 px-4 max-w-md mx-auto text-center space-y-5 bg-surface-container-lowest rounded-3xl border border-outline-variant/30 my-8 shadow-xs">
      <div className="w-16 h-16 rounded-full bg-error-container/30 flex items-center justify-center mx-auto text-error">
        <span className="material-symbols-outlined text-[32px]">
          error_outline
        </span>
      </div>

      <div className="space-y-2">
        <h2 className="font-headline-lg text-xl font-bold text-on-surface">
          Chưa thể hoàn tất tìm kiếm
        </h2>
        <p className="font-body-sm text-sm text-on-surface-variant leading-relaxed">
          {message ||
            "Hệ thống đang bận hoặc từ khóa tìm kiếm chưa hợp lệ (tối thiểu 2 ký tự). Xin hoan hỷ thử lại sau giây lát."}
        </p>
      </div>

      <div className="pt-2">
        <Link
          href="/tim-kiem"
          className="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-primary text-on-primary font-label-md text-xs sm:text-sm font-semibold hover:bg-on-primary-container transition-colors shadow-xs"
        >
          <span className="material-symbols-outlined text-[18px]">
            refresh
          </span>
          <span>Thử lại với từ khóa khác</span>
        </Link>
      </div>
    </div>
  );
}
