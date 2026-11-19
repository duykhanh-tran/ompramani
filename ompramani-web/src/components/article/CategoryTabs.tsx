"use client";

import React from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { ArticleCategory } from "@/types/api";

interface CategoryTabsProps {
  categories: ArticleCategory[];
  currentCategory?: string;
  currentTag?: string;
}

export function CategoryTabs({
  categories,
  currentCategory,
  currentTag,
}: CategoryTabsProps) {
  const searchParams = useSearchParams();

  const createCategoryUrl = (categorySlug?: string) => {
    const params = new URLSearchParams(searchParams.toString());

    if (categorySlug) {
      params.set("category", categorySlug);
    } else {
      params.delete("category");
    }

    params.delete("page"); // Reset to page 1 on category change
    const str = params.toString();
    return str ? `/bai-viet?${str}` : "/bai-viet";
  };

  const createClearTagUrl = () => {
    const params = new URLSearchParams(searchParams.toString());
    params.delete("tag");
    params.delete("page");
    const str = params.toString();
    return str ? `/bai-viet?${str}` : "/bai-viet";
  };

  return (
    <div className="flex flex-col gap-3">
      {/* Active Tag Notice if user clicked a tag */}
      {currentTag && (
        <div className="flex items-center gap-2 text-sm text-on-surface-variant bg-surface-container-low px-4 py-2 rounded-xl border border-outline-variant/40 w-fit">
          <span className="material-symbols-outlined text-base text-primary">
            label
          </span>
          <span>
            Đang lọc theo thẻ: <strong className="text-primary font-medium">#{currentTag}</strong>
          </span>
          <Link
            href={createClearTagUrl()}
            className="ml-2 inline-flex items-center text-xs text-outline hover:text-on-surface bg-surface-container-lowest px-2 py-0.5 rounded-md border border-outline-variant/30 transition-colors"
          >
            Bỏ lọc thẻ
            <span className="material-symbols-outlined text-[14px] ml-1">close</span>
          </Link>
        </div>
      )}

      {/* Category Tabs */}
      <div
        className="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none"
        role="tablist"
        aria-label="Danh mục bài viết"
      >
        {/* "Tất cả" tab */}
        <Link
          href={createCategoryUrl()}
          role="tab"
          aria-selected={!currentCategory}
          className={`shrink-0 px-4 py-2 rounded-full font-label-md text-sm transition-all ${
            !currentCategory
              ? "bg-primary text-on-primary font-semibold shadow-xs"
              : "bg-surface-container-low text-on-surface hover:bg-surface-container hover:text-primary border border-outline-variant/40"
          }`}
        >
          Tất cả chủ đề
        </Link>

        {/* Dynamic categories from backend */}
        {categories.map((cat) => {
          const isActive = currentCategory === cat.slug;
          return (
            <Link
              key={cat.id}
              href={createCategoryUrl(cat.slug)}
              role="tab"
              aria-selected={isActive}
              className={`shrink-0 px-4 py-2 rounded-full font-label-md text-sm transition-all flex items-center gap-1.5 ${
                isActive
                  ? "bg-primary text-on-primary font-semibold shadow-xs"
                  : "bg-surface-container-low text-on-surface hover:bg-surface-container hover:text-primary border border-outline-variant/40"
              }`}
            >
              <span>{cat.name}</span>
              {typeof cat.published_articles_count === "number" && (
                <span
                  className={`text-[11px] px-1.5 py-0.2 rounded-full ${
                    isActive
                      ? "bg-on-primary/20 text-on-primary"
                      : "bg-outline-variant/30 text-outline"
                  }`}
                >
                  {cat.published_articles_count}
                </span>
              )}
            </Link>
          );
        })}
      </div>
    </div>
  );
}
