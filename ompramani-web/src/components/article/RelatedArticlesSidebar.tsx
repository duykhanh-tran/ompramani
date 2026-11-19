import React from "react";
import Link from "next/link";
import Image from "next/image";
import { Article, RelatedArticleItem } from "@/types/api";
import { resolveMediaUrl } from "@/lib/utils/media";

interface RelatedArticlesSidebarProps {
  articles?: (Article | RelatedArticleItem)[];
  currentSlug?: string;
}

export function RelatedArticlesSidebar({
  articles,
  currentSlug,
}: RelatedArticlesSidebarProps) {
  const filtered = (articles || []).filter(
    (item) => item.slug !== currentSlug
  );

  if (filtered.length === 0) {
    return null;
  }

  return (
    <aside className="w-full space-y-4">
      <div className="border-b border-outline-variant/40 pb-3">
        <span className="font-label-caps text-xs text-primary uppercase tracking-wider block font-semibold mb-1">
          Gợi ý đọc thêm
        </span>
        <h2 className="font-headline-sm text-base sm:text-lg font-bold text-on-surface font-serif">
          Bài Viết Liên Quan
        </h2>
      </div>

      <div className="flex flex-col divide-y divide-outline-variant/30">
        {filtered.slice(0, 6).map((item) => {
          const coverUrl = resolveMediaUrl(item.cover_image);

          return (
            <Link
              key={item.id}
              href={`/bai-viet/${item.slug}`}
              className="group py-3.5 first:pt-0 last:pb-0 flex flex-col gap-2 transition-colors"
            >
              {coverUrl && (
                <div className="relative aspect-[16/9] w-full rounded-xl overflow-hidden bg-surface-container-low shadow-2xs border border-outline-variant/30">
                  <Image
                    src={coverUrl}
                    alt={item.title}
                    fill
                    sizes="(max-width: 1024px) 100vw, 320px"
                    className="object-cover group-hover:scale-103 transition-transform duration-300"
                  />
                </div>
              )}

              <div className="space-y-1">
                <h3 className="font-serif font-semibold text-sm leading-snug text-on-surface group-hover:text-primary transition-colors line-clamp-2">
                  {item.title}
                </h3>

                {"category" in item && item.category && (
                  <span className="inline-block text-[11px] font-medium text-primary">
                    {item.category.name}
                  </span>
                )}
              </div>
            </Link>
          );
        })}
      </div>
    </aside>
  );
}
