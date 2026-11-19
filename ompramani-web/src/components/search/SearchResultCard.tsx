import React from "react";
import Link from "next/link";
import Image from "next/image";
import { NormalizedSearchResult } from "@/types/api";
import { resolveMediaUrl } from "@/lib/utils/media";
import {
  getSearchResultHref,
  getSearchTypeBadge,
  highlightMatches,
} from "@/lib/utils/search";
import { getFigureTypeLabel } from "@/lib/utils/sacred";

interface SearchResultCardProps {
  item: NormalizedSearchResult;
  query: string;
}

function HighlightedText({ text, query }: { text: string; query: string }) {
  const segments = highlightMatches(text, query);
  return (
    <>
      {segments.map((seg, i) =>
        seg.isMatch ? (
          <mark
            key={i}
            className="bg-primary/15 text-primary font-semibold rounded-xs px-0.5"
          >
            {seg.text}
          </mark>
        ) : (
          <span key={i}>{seg.text}</span>
        )
      )}
    </>
  );
}

const MATCHED_IN_LABELS: Record<string, string> = {
  title: "Tiêu đề",
  alias: "Danh hiệu khác",
  original_title: "Nguyên tác",
  original_name: "Tên gốc Phạn/Hán",
  content: "Nội dung",
  sections: "Phẩm / Phần kinh",
  transliteration: "Phiên âm",
  tag: "Thẻ chủ đề",
  category: "Chuyên mục",
};

export function SearchResultCard({ item, query }: SearchResultCardProps) {
  const href = getSearchResultHref(item);
  const badge = getSearchTypeBadge(item.type);
  const thumbnail = resolveMediaUrl(item.thumbnail);

  const originalName =
    item.metadata?.original_title || item.metadata?.original_name;

  return (
    <Link
      href={href}
      className="group block p-5 rounded-2xl bg-surface-container-lowest border border-outline-variant/40 hover:border-primary/50 hover:shadow-md transition-all duration-300"
    >
      <div className="flex flex-col sm:flex-row items-start gap-4 sm:gap-5">
        {/* Thumbnail or Icon Box */}
        <div className="relative w-full sm:w-28 sm:h-28 aspect-[16/9] sm:aspect-square shrink-0 rounded-xl overflow-hidden bg-surface-container-low border border-outline-variant/30">
          {thumbnail ? (
            <Image
              src={thumbnail}
              alt={item.title}
              fill
              sizes="(max-width: 640px) 100vw, 112px"
              className="object-cover group-hover:scale-105 transition-transform duration-300"
            />
          ) : (
            <div className="w-full h-full flex flex-col items-center justify-center text-outline/50 bg-secondary-container/20">
              <span className="material-symbols-outlined text-[36px] text-primary/60">
                {badge.icon}
              </span>
            </div>
          )}
        </div>

        {/* Info Column */}
        <div className="min-w-0 flex-1 w-full space-y-2">
          {/* Header Badges */}
          <div className="flex flex-wrap items-center gap-2">
            <span
              className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wider border ${badge.badgeClass}`}
            >
              <span className="material-symbols-outlined text-[13px]">
                {badge.icon}
              </span>
              <span>{badge.label}</span>
            </span>

            {item.tradition && (
              <span className="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-surface-container text-on-surface-variant border border-outline-variant/30">
                {item.tradition.name}
              </span>
            )}

            {item.type === "figure" && item.metadata?.figure_type && (
              <span className="px-2 py-0.5 rounded text-[11px] text-outline bg-surface-container-low">
                {getFigureTypeLabel(item.metadata.figure_type)}
              </span>
            )}

            {item.type === "article" && item.metadata?.category_name && (
              <span className="px-2 py-0.5 rounded text-[11px] text-outline bg-surface-container-low">
                Chuyên mục: {item.metadata.category_name}
              </span>
            )}

            {item.type === "mantra" && item.metadata?.figure_name && (
              <span className="px-2 py-0.5 rounded text-[11px] text-outline bg-surface-container-low">
                Bổn tôn: {item.metadata.figure_name}
              </span>
            )}
          </div>

          {/* Title */}
          <div>
            <h3 className="font-headline-md text-base sm:text-lg font-bold text-on-surface group-hover:text-primary transition-colors leading-snug">
              <HighlightedText text={item.title} query={query} />
            </h3>

            {originalName && (
              <p className="font-body-sm text-xs text-outline italic mt-0.5">
                {originalName}
              </p>
            )}
          </div>

          {/* Description / Excerpt */}
          {item.description && (
            <p className="font-body-sm text-sm text-on-surface-variant line-clamp-2 leading-relaxed">
              <HighlightedText text={item.description} query={query} />
            </p>
          )}

          {/* Bottom Row: Matched-in hint & Arrow */}
          <div className="flex items-center justify-between pt-1 border-t border-outline-variant/20 text-xs text-outline">
            {item.matched_in && item.matched_in.length > 0 ? (
              <div className="flex items-center gap-1.5 flex-wrap">
                <span className="material-symbols-outlined text-[14px] text-primary/70">
                  check_circle
                </span>
                <span>Khớp ở:</span>
                {item.matched_in.slice(0, 3).map((field, idx) => (
                  <span
                    key={idx}
                    className="px-1.5 py-0.2 rounded bg-surface-container text-[11px] text-on-surface-variant"
                  >
                    {MATCHED_IN_LABELS[field] || field}
                  </span>
                ))}
              </div>
            ) : (
              <div />
            )}

            <div className="inline-flex items-center gap-1 font-label-md font-medium text-primary opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all">
              <span>Xem chi tiết</span>
              <span className="material-symbols-outlined text-[16px]">
                arrow_forward
              </span>
            </div>
          </div>
        </div>
      </div>
    </Link>
  );
}
