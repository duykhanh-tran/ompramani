import React from "react";
import { RelatedArticleItem } from "@/types/api";
import { ArticleCard } from "./ArticleCard";

interface RelatedArticlesSectionProps {
  articles?: RelatedArticleItem[];
}

export function RelatedArticlesSection({
  articles,
}: RelatedArticlesSectionProps) {
  if (!articles || articles.length === 0) {
    return null;
  }

  return (
    <section className="w-full mt-16 pt-12 border-t border-outline-variant/30">
      <div className="flex items-center justify-between mb-8">
        <div>
          <span className="font-label-caps text-label-caps text-primary uppercase tracking-wider block mb-1">
            Gợi ý đón đọc
          </span>
          <h2 className="font-headline-xl text-xl sm:text-2xl font-bold text-on-surface">
            Bài Viết Liên Quan
          </h2>
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        {articles.slice(0, 3).map((item) => (
          <ArticleCard key={item.id} article={item} variant="standard" />
        ))}
      </div>
    </section>
  );
}
