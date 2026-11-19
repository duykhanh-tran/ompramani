"use client";

import React, { useState } from "react";
import Link from "next/link";
import Image from "next/image";
import { UserFavoriteItem } from "@/types/favorite";
import { resolveMediaUrl } from "@/lib/utils/media";
import { formatArticleDate } from "@/lib/utils/sacred";

interface FavoriteCardProps {
  favorite: UserFavoriteItem;
  onRemove: (favorite: UserFavoriteItem) => Promise<void> | void;
}

const TYPE_CONFIG = {
  sutra: {
    label: "Kinh Phật",
    icon: "auto_stories",
    badgeClass: "bg-amber-100 text-amber-900 border-amber-200/50 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-900/50",
  },
  article: {
    label: "Bài viết",
    icon: "menu_book",
    badgeClass: "bg-slate-100 text-slate-800 border-slate-200/50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700",
  },
  video: {
    label: "Video",
    icon: "play_circle",
    badgeClass: "bg-emerald-100 text-emerald-900 border-emerald-200/50 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900/50",
  },
};

export function FavoriteCard({ favorite, onRemove }: FavoriteCardProps) {
  const [isRemoving, setIsRemoving] = useState(false);
  const typeInfo = TYPE_CONFIG[favorite.type] || {
    label: "Nội dung",
    icon: "bookmark",
    badgeClass: "bg-surface-container text-on-surface-variant border-outline-variant/30",
  };

  const imageUrl = resolveMediaUrl(favorite.thumbnail);

  const handleRemove = async (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (isRemoving) return;

    setIsRemoving(true);
    try {
      await onRemove(favorite);
    } finally {
      setIsRemoving(false);
    }
  };

  return (
    <article
      className={`group relative bg-surface-container-lowest rounded-3xl border border-outline-variant/30 overflow-hidden transition-all duration-300 hover:shadow-md flex flex-col justify-between ${
        !favorite.is_available ? "opacity-75" : ""
      }`}
    >
      <div>
        {/* Media Thumbnail or Emblem */}
        <div className="relative aspect-16/9 w-full bg-surface-container-low overflow-hidden">
          {imageUrl && favorite.is_available ? (
            <Image
              src={imageUrl}
              alt={favorite.title}
              fill
              className="object-cover transition-transform duration-500 group-hover:scale-105"
              sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
            />
          ) : (
            <div className="absolute inset-0 flex flex-col items-center justify-center p-4 text-center bg-linear-to-b from-amber-50/40 to-amber-100/30 dark:from-stone-900 dark:to-stone-950">
              <span className="material-symbols-outlined text-4xl text-primary/60 mb-2">
                {favorite.is_available ? typeInfo.icon : "lock"}
              </span>
              <span className="font-sacred-quote text-xs text-on-surface-variant">
                {favorite.is_available ? "OmPraMani" : "Nội dung không khả dụng"}
              </span>
            </div>
          )}

          {/* Type Badge */}
          <div className="absolute top-3 left-3">
            <span
              className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-label-md font-medium border backdrop-blur-xs shadow-xs ${typeInfo.badgeClass}`}
            >
              <span className="material-symbols-outlined text-[14px]">
                {typeInfo.icon}
              </span>
              <span>{typeInfo.label}</span>
            </span>
          </div>

          {/* Remove Favorite Quick Action */}
          <button
            type="button"
            onClick={handleRemove}
            disabled={isRemoving}
            title="Bỏ khỏi yêu thích"
            aria-label="Bỏ khỏi danh sách yêu thích"
            className="absolute top-3 right-3 w-8 h-8 rounded-full bg-surface/80 hover:bg-surface text-rose-600 dark:text-rose-400 flex items-center justify-center shadow-xs backdrop-blur-xs transition-all cursor-pointer"
          >
            <span
              className={`material-symbols-outlined text-[18px] ${
                isRemoving ? "animate-pulse" : ""
              }`}
              style={{ fontVariationSettings: "'FILL' 1" }}
            >
              favorite
            </span>
          </button>
        </div>

        {/* Content Body */}
        <div className="p-5 sm:p-6 space-y-3">
          <div className="flex items-center justify-between text-[12px] font-label-md text-outline">
            <span>Đã lưu: {formatArticleDate(favorite.saved_at)}</span>
          </div>

          <h3 className="font-headline-sm text-base sm:text-lg font-bold text-on-surface line-clamp-2 group-hover:text-primary transition-colors leading-snug">
            {favorite.is_available ? (
              <Link href={favorite.content_url} className="hover:underline">
                {favorite.title}
              </Link>
            ) : (
              <span className="text-on-surface-variant italic">
                {favorite.title}
              </span>
            )}
          </h3>

          {favorite.short_description && favorite.is_available && (
            <p className="font-body-md text-xs sm:text-sm text-on-surface-variant line-clamp-2 leading-relaxed">
              {favorite.short_description}
            </p>
          )}
        </div>
      </div>

      {/* Card Footer Actions */}
      <div className="px-5 pb-5 sm:px-6 sm:pb-6 pt-0 flex items-center justify-between gap-3 border-t border-outline-variant/10 mt-3 pt-3">
        {favorite.is_available ? (
          <Link
            href={favorite.content_url}
            className="inline-flex items-center gap-1.5 text-xs font-label-md font-semibold text-primary hover:text-primary/80 transition-colors"
          >
            <span>Mở xem nội dung</span>
            <span className="material-symbols-outlined text-[16px]">
              arrow_forward
            </span>
          </Link>
        ) : (
          <span className="text-xs font-label-md text-on-surface-variant/70 italic">
            Không thể truy cập
          </span>
        )}

        <button
          type="button"
          onClick={handleRemove}
          disabled={isRemoving}
          className="text-xs font-label-md text-outline hover:text-rose-600 transition-colors cursor-pointer"
        >
          {isRemoving ? "Đang gỡ..." : "Bỏ lưu"}
        </button>
      </div>
    </article>
  );
}
