import React from "react";
import Link from "next/link";
import { Article } from "@/types/api";
import { resolveMediaUrl } from "@/lib/utils/media";
import { FavoriteButton } from "@/components/favorites/FavoriteButton";
import { SafeImage } from "@/components/common/SafeImage";

interface ArticleHeroProps {
  article: Article;
}

export function ArticleHero({ article }: ArticleHeroProps) {
  const coverImage = resolveMediaUrl(article.cover_image);

  return (
    <div className="w-full">
      {/* Breadcrumb Navigation */}
      <nav
        aria-label="Điều hướng đường dẫn"
        className="flex items-center flex-wrap gap-2 text-xs sm:text-sm text-outline mb-4 font-body-sm"
      >
        <Link href="/" className="hover:text-primary transition-colors">
          Trang chủ
        </Link>
        <span className="material-symbols-outlined text-[14px]">
          chevron_right
        </span>
        <Link href="/bai-viet" className="hover:text-primary transition-colors">
          Bài Viết
        </Link>
        {article.category && (
          <>
            <span className="material-symbols-outlined text-[14px]">
              chevron_right
            </span>
            <Link
              href={`/bai-viet?category=${article.category.slug}`}
              className="hover:text-primary transition-colors"
            >
              {article.category.name}
            </Link>
          </>
        )}
        <span className="material-symbols-outlined text-[14px]">
          chevron_right
        </span>
        <span className="text-on-surface line-clamp-1 max-w-[200px] sm:max-w-[320px]">
          {article.title}
        </span>
      </nav>

      {/* Title & Favorite Action Row - Left Aligned, Clean Header */}
      <div className="w-full flex items-start justify-between gap-4 mb-6">
        <h1 className="font-headline-2xl sm:font-headline-3xl text-2xl sm:text-3xl lg:text-[38px] font-bold text-on-surface leading-tight tracking-tight font-serif flex-1">
          {article.title}
        </h1>
        <div className="shrink-0 pt-1">
          <FavoriteButton
            contentType="article"
            contentPublicId={article.slug}
            variant="pill"
            showLabel
          />
        </div>
      </div>

      {/* Featured Cover Image - Big and Wide */}
      {coverImage && (
        <div className="w-full mb-8">
          <div className="relative w-full aspect-[16/9] max-h-[580px] rounded-2xl overflow-hidden bg-surface-container-low shadow-md border border-outline-variant/40">
            <SafeImage
              src={coverImage}
              alt={article.title}
              fill
              priority
              sizes="(max-width: 1280px) 100vw, 1200px"
              className="object-cover"
              fallbackIcon="auto_stories"
              fallbackText={article.title}
            />
          </div>
        </div>
      )}
    </div>
  );
}
