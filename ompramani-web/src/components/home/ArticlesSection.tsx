import React from "react";
import Link from "next/link";
import { Article } from "@/types/api";
import { resolveMediaUrl } from "@/lib/utils/media";
import { SafeImage } from "@/components/common/SafeImage";

interface Props {
  articles?: Article[];
}

export function ArticlesSection({ articles }: Props) {
  // If no articles exist in backend, do not display the section
  if (!articles || articles.length === 0) {
    return null;
  }

  return (
    <div className="lg:col-span-8 space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <span className="font-label-caps text-label-caps text-primary uppercase">
            Pháp Thoại Ứng Dụng
          </span>
          <h2 className="font-headline-xl text-headline-xl text-on-surface font-semibold tracking-tight">
            Bài Viết Được Quan Tâm
          </h2>
        </div>
        <Link
          href="/bai-viet"
          className="font-label-lg text-label-lg text-primary hover:underline"
        >
          Xem thêm bài viết
        </Link>
      </div>

      <div className="space-y-4">
        {articles.map((article: Article) => {
          const categoryName = article.category?.name || "Pháp Học";
          const readingTime = article.reading_time_minutes || 10;
          const sharesCount = article.shares_count
            ? `${article.shares_count.toLocaleString("vi-VN")} lượt chia sẻ`
            : "Đọc nhiều nhất";
          const coverImage = resolveMediaUrl(article.cover_image);

          return (
            <Link
              key={article.id}
              href={`/bai-viet/${article.slug}`}
              className="bg-surface-container-lowest p-5 rounded-2xl shadow-sm hover:shadow-md transition-all flex flex-col sm:flex-row items-center gap-5 group block"
            >
              {/* Thumbnail */}
              <div className="w-full sm:w-36 h-28 rounded-xl overflow-hidden relative shrink-0 bg-surface-container-low">
                <SafeImage
                  src={coverImage}
                  alt={article.title}
                  fill
                  fallbackIcon="auto_stories"
                  fallbackText={article.title}
                  sizes="(max-width: 640px) 100vw, 144px"
                  className="object-cover group-hover:scale-105 transition-transform duration-300"
                />
              </div>

              {/* Text Info */}
              <div className="min-w-0 flex-1">
                <span className="font-label-caps text-label-caps text-primary uppercase">
                  {categoryName}
                </span>
                <h3 className="font-headline-md text-headline-md font-semibold text-on-surface group-hover:text-primary transition-colors mt-0.5">
                  {article.title}
                </h3>
                {article.summary && (
                  <p className="font-body-sm text-body-sm text-on-surface-variant line-clamp-2 mt-1">
                    {article.summary}
                  </p>
                )}
                <div className="flex items-center gap-4 mt-2 font-label-md text-label-md text-outline">
                  <span>{readingTime} phút đọc</span>
                  <span>·</span>
                  <span>{sharesCount}</span>
                </div>
              </div>
            </Link>
          );
        })}
      </div>
    </div>
  );
}
