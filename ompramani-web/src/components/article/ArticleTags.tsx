import React from "react";
import Link from "next/link";
import { ArticleTag } from "@/types/api";

interface ArticleTagsProps {
  tags?: ArticleTag[];
}

export function ArticleTags({ tags }: ArticleTagsProps) {
  if (!tags || tags.length === 0) {
    return null;
  }

  return (
    <div className="max-w-[780px] mx-auto w-full mt-8">
      <div className="flex items-center flex-wrap gap-2">
        <span className="text-xs text-outline font-label-md flex items-center gap-1 mr-1">
          <span className="material-symbols-outlined text-[16px]">label</span>
          Thẻ:
        </span>
        {tags.map((tag) => (
          <Link
            key={tag.id}
            href={`/bai-viet?tag=${tag.slug}`}
            className="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium bg-surface-container-low hover:bg-surface-container text-on-surface-variant hover:text-primary border border-outline-variant/30 hover:border-primary/40 transition-colors"
          >
            #{tag.name}
          </Link>
        ))}
      </div>
    </div>
  );
}
