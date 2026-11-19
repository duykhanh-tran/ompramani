"use client";

import React from "react";
import Link from "next/link";
import { FavoriteContentType } from "@/types/favorite";

interface FavoriteEmptyStateProps {
  currentType: FavoriteContentType;
}

const CONFIG_BY_TYPE = {
  all: {
    title: "Chưa có nội dung yêu thích",
    description: "Bạn chưa lưu nội dung nào vào danh sách yêu thích. Hãy khám phá và lưu lại những bài kinh văn, bài viết hoặc video để dễ dàng xem lại.",
    ctaLabel: "Khám phá Thư viện Kinh",
    ctaHref: "/kinh",
  },
  sutra: {
    title: "Chưa lưu bộ kinh nào",
    description: "Bạn chưa lưu bộ kinh nào vào danh mục yêu thích. Hãy khám phá kho tàng kinh điển Đại Thừa và Nguyên Thủy.",
    ctaLabel: "Khám phá Thư viện Kinh",
    ctaHref: "/kinh",
  },
  article: {
    title: "Chưa lưu bài viết nào",
    description: "Bạn chưa lưu bài viết Phật pháp nào. Hãy đọc và lưu lại những bài phân tích, giáo lý hữu ích.",
    ctaLabel: "Đọc bài viết Phật pháp",
    ctaHref: "/bai-viet",
  },
  video: {
    title: "Chưa lưu video nào",
    description: "Bạn chưa lưu bài giảng video hoặc hướng dẫn tu học nào.",
    ctaLabel: "Xem Video Phật Pháp",
    ctaHref: "/video-phat-phap",
  },
};

export function FavoriteEmptyState({ currentType }: FavoriteEmptyStateProps) {
  const info = CONFIG_BY_TYPE[currentType] || CONFIG_BY_TYPE.all;

  return (
    <div className="text-center py-16 px-4 max-w-md mx-auto">
      <div className="w-16 h-16 rounded-full bg-surface-container-low flex items-center justify-center mx-auto mb-4 border border-outline-variant/30 text-primary/70">
        <span className="material-symbols-outlined text-3xl">favorite_border</span>
      </div>

      <h3 className="font-headline-md text-lg sm:text-xl font-bold text-on-surface mb-2">
        {info.title}
      </h3>

      <p className="font-body-md text-sm text-on-surface-variant leading-relaxed mb-6">
        {info.description}
      </p>

      <Link
        href={info.ctaHref}
        className="inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-primary text-on-primary font-label-md text-xs sm:text-sm font-semibold hover:bg-primary/90 shadow-xs transition-all cursor-pointer"
      >
        <span>{info.ctaLabel}</span>
        <span className="material-symbols-outlined text-[18px]">
          arrow_forward
        </span>
      </Link>
    </div>
  );
}
