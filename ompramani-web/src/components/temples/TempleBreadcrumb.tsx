import React from "react";
import Link from "next/link";

interface TempleBreadcrumbProps {
  templeName: string;
}

export function TempleBreadcrumb({ templeName }: TempleBreadcrumbProps) {
  return (
    <nav
      aria-label="Đường dẫn điều hướng"
      className="flex items-center gap-2 text-xs sm:text-sm font-label-md text-on-surface-variant overflow-hidden"
    >
      <Link
        href="/"
        className="hover:text-primary transition-colors shrink-0 flex items-center gap-1"
      >
        <span className="material-symbols-outlined text-[16px]">home</span>
        <span>Trang chủ</span>
      </Link>

      <span className="text-outline-variant/80 shrink-0">/</span>

      <Link
        href="/tim-chua"
        className="hover:text-primary transition-colors shrink-0"
      >
        Chùa Việt Nam
      </Link>

      <span className="text-outline-variant/80 shrink-0">/</span>

      <span
        className="text-on-surface font-medium truncate max-w-[180px] sm:max-w-xs md:max-w-md"
        title={templeName}
        aria-current="page"
      >
        {templeName}
      </span>
    </nav>
  );
}
