"use client";

import React from "react";
import Link from "next/link";
import { Article, RelatedArticleItem } from "@/types/api";
import { resolveMediaUrl } from "@/lib/utils/media";
import { formatArticleDate, estimateReadingTime } from "@/lib/utils/sacred";
import { FavoriteButton } from "@/components/favorites/FavoriteButton";
import { SafeImage } from "@/components/common/SafeImage";

interface ArticleCardProps {
  article: Article | RelatedArticleItem;
  variant?: "standard" | "compact" | "horizontal";
}

export function ArticleCard({
  article,
  variant = "standard",
}: ArticleCardProps) {
  // Safe field resolution across Article and RelatedArticleItem
  const title = article.title;
  const slug = article.slug;
  const excerpt =
    ("summary" in article && article.summary) ||
    ("excerpt" in article && article.excerpt) ||
    "";
  const coverImage = resolveMediaUrl(article.cover_image);
  const category = "category" in article ? article.category : null;
  const categoryName = category?.name || null;
  const publishedDate = formatArticleDate(article.published_at);
  const readingTime =
    "reading_time_minutes" in article && article.reading_time_minutes
      ? article.reading_time_minutes
      : estimateReadingTime(excerpt || title);

  if (variant === "compact") {
    return (
      <Link
        href={`/bai-viet/${slug}`}
        className="group flex items-start gap-4 p-3 rounded-xl bg-surface-container-lowest border border-outline-variant/40 hover:border-primary/40 hover:shadow-sm transition-all"
      >
        <div className="relative w-20 h-20 shrink-0 rounded-lg overflow-hidden bg-surface-container-low">
          <SafeImage
            src={coverImage}
            alt={title}
            fill
            sizes="80px"
            className="object-cover group-hover:scale-105 transition-transform duration-300"
            fallback={
              <div className="w-full h-full flex items-center justify-center text-outline/50 bg-secondary-container/20">
                <span className="material-symbols-outlined text-2xl text-primary/60">
                  auto_stories
                </span>
              </div>
            }
          />
        </div>
        <div className="min-w-0 flex-1">
          {categoryName && (
            <span className="font-label-sm text-[11px] font-medium text-primary uppercase tracking-wider block mb-0.5">
              {categoryName}
            </span>
          )}
          <h4 className="font-headline-sm text-sm font-semibold text-on-surface group-hover:text-primary transition-colors line-clamp-2 leading-snug">
            {title}
          </h4>
          <div className="flex items-center gap-2 mt-2 font-label-sm text-[12px] text-outline">
            {publishedDate && <span>{publishedDate}</span>}
            {publishedDate && <span>·</span>}
            <span>{readingTime} phút đọc</span>
          </div>
        </div>
      </Link>
    );
  }

  if (variant === "horizontal") {
    return (
      <Link
        href={`/bai-viet/${slug}`}
        className="group flex flex-col sm:flex-row items-center gap-5 p-5 rounded-2xl bg-surface-container-lowest border border-outline-variant/40 hover:border-primary/40 hover:shadow-md transition-all"
      >
        <div className="relative w-full sm:w-48 h-36 shrink-0 rounded-xl overflow-hidden bg-surface-container-low">
          <SafeImage
            src={coverImage}
            alt={title}
            fill
            sizes="(max-width: 640px) 100vw, 192px"
            className="object-cover group-hover:scale-105 transition-transform duration-300"
            fallback={
              <div className="w-full h-full flex items-center justify-center text-outline/50 bg-secondary-container/20">
                <span className="material-symbols-outlined text-4xl text-primary/60">
                  auto_stories
                </span>
              </div>
            }
          />
        </div>
        <div className="min-w-0 flex-1 w-full">
          {categoryName && (
            <span className="font-label-caps text-label-caps text-primary uppercase tracking-wider block mb-1">
              {categoryName}
            </span>
          )}
          <h3 className="font-headline-md text-headline-md font-semibold text-on-surface group-hover:text-primary transition-colors line-clamp-2 leading-tight">
            {title}
          </h3>
          {excerpt && (
            <p className="font-body-sm text-body-sm text-on-surface-variant line-clamp-2 mt-2 leading-relaxed">
              {excerpt}
            </p>
          )}
          <div className="flex items-center gap-3 mt-3 font-label-md text-label-md text-outline">
            {publishedDate && <span>{publishedDate}</span>}
            {publishedDate && <span>·</span>}
            <span>{readingTime} phút đọc</span>
          </div>
        </div>
      </Link>
    );
  }

  // Standard vertical grid card
  return (
    <Link
      href={`/bai-viet/${slug}`}
      className="group flex flex-col h-full rounded-2xl overflow-hidden bg-surface-container-lowest border border-outline-variant/40 hover:border-primary/40 hover:shadow-md transition-all duration-300"
    >
      {/* Cover Image Container */}
      <div className="relative w-full aspect-[16/10] bg-surface-container-low overflow-hidden">
        <SafeImage
          src={coverImage}
          alt={title}
          fill
          sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
          className="object-cover group-hover:scale-105 transition-transform duration-500 ease-out"
          fallback={
            <div className="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-surface-container-low to-surface-container text-outline/40 p-4">
              <span className="material-symbols-outlined text-5xl text-primary/40 mb-1">
                menu_book
              </span>
              <span className="font-label-sm text-xs text-outline tracking-wider">
                OmPraMani
              </span>
            </div>
          }
        />

        {categoryName && (
          <div className="absolute top-3 left-3 z-10">
            <span className="px-3 py-1 rounded-full text-xs font-medium bg-surface-container-lowest/90 backdrop-blur-sm text-primary shadow-xs border border-outline-variant/30">
              {categoryName}
            </span>
          </div>
        )}

        <div
          className="absolute top-3 right-3 z-10"
          onClick={(e) => {
            e.preventDefault();
            e.stopPropagation();
          }}
        >
          <FavoriteButton
            contentType="article"
            contentPublicId={slug}
            variant="compact"
          />
        </div>
      </div>

      {/* Content */}
      <div className="flex flex-col flex-1 p-5">
        <h3 className="font-headline-md text-lg sm:text-xl font-semibold text-on-surface group-hover:text-primary transition-colors line-clamp-2 leading-snug">
          {title}
        </h3>

        {excerpt && (
          <p className="font-body-sm text-sm text-on-surface-variant line-clamp-2 mt-2 leading-relaxed flex-1">
            {excerpt}
          </p>
        )}

        {/* Footer Meta */}
        <div className="flex items-center justify-between pt-4 mt-auto border-t border-outline-variant/30 font-label-sm text-xs text-outline">
          <div className="flex items-center gap-2">
            <span className="material-symbols-outlined text-[16px] text-outline/70">
              schedule
            </span>
            <span>{readingTime} phút đọc</span>
          </div>
          {publishedDate && <span>{publishedDate}</span>}
        </div>
      </div>
    </Link>
  );
}
