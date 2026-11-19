"use client";

import React, { useState } from "react";
import { parseSafeContent } from "@/lib/utils/sacred";

interface ArticleContentProps {
  content?: string | null;
  articleTitle: string;
}

export function ArticleContent({ content, articleTitle }: ArticleContentProps) {
  const [copied, setCopied] = useState(false);

  const handleShare = async () => {
    try {
      if (typeof window !== "undefined") {
        if (navigator.share) {
          try {
            await navigator.share({
              title: articleTitle,
              url: window.location.href,
            });
            return;
          } catch (shareErr) {
            if (shareErr instanceof Error && shareErr.name === "AbortError") {
              return;
            }
          }
        }
        await navigator.clipboard.writeText(window.location.href);
        setCopied(true);
        setTimeout(() => setCopied(false), 2500);
      }
    } catch (err) {
      console.error("Failed to share or copy link:", err);
    }
  };

  const parsed = parseSafeContent(content);

  return (
    <article className="w-full">
      {/* Content Body */}
      {parsed.isHtml ? (
        <div
          className="prose prose-stone lg:prose-lg max-w-none text-on-surface/90 leading-[32px] sm:leading-[36px] text-[17px] sm:text-[18px] font-sans
            prose-headings:font-serif prose-headings:font-bold prose-headings:text-on-surface prose-headings:tracking-tight
            prose-h2:text-2xl sm:prose-h2:text-3xl prose-h2:mt-10 prose-h2:mb-4 prose-h2:text-primary
            prose-h3:text-xl sm:prose-h3:text-2xl prose-h3:mt-8 prose-h3:mb-3
            prose-p:my-5 prose-p:leading-relaxed text-justify
            prose-blockquote:border-l-4 prose-blockquote:border-primary/60 prose-blockquote:bg-surface-container-low/50 prose-blockquote:py-3 prose-blockquote:px-5 prose-blockquote:rounded-r-xl prose-blockquote:italic prose-blockquote:text-on-surface-variant prose-blockquote:my-6
            prose-a:text-primary prose-a:underline hover:prose-a:text-primary-dark prose-a:font-medium
            prose-img:rounded-2xl prose-img:shadow-sm prose-img:mx-auto prose-img:my-8 prose-img:border prose-img:border-outline-variant/30
            prose-li:my-1.5 prose-li:leading-relaxed
            prose-strong:text-on-surface prose-strong:font-semibold"
          dangerouslySetInnerHTML={{ __html: parsed.sanitizedHtml }}
        />
      ) : parsed.paragraphs.length > 0 ? (
        <div className="space-y-6 text-on-surface/90 leading-[32px] sm:leading-[36px] text-[17px] sm:text-[18px]">
          {parsed.paragraphs.map((p, idx) => (
            <p key={idx} className="text-justify leading-relaxed">
              {p}
            </p>
          ))}
        </div>
      ) : (
        <div className="py-12 text-center text-outline bg-surface-container-low/30 rounded-2xl border border-outline-variant/30">
          <span className="material-symbols-outlined text-4xl mb-2 text-outline/60">
            description
          </span>
          <p className="font-body-md text-sm">Nội dung bài viết đang được cập nhật.</p>
        </div>
      )}

      {/* Article End Divider & Share Actions */}
      <div className="mt-12 pt-8 border-t border-outline-variant/30 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div className="flex items-center gap-2 text-xs text-outline font-label-md">
          <span className="material-symbols-outlined text-[18px] text-primary">
            verified
          </span>
          <span>Nội dung đã được kiểm duyệt theo giáo lý Phật giáo</span>
        </div>

        <div className="flex items-center gap-3">
          <button
            type="button"
            suppressHydrationWarning
            onClick={handleShare}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container text-on-surface hover:text-primary border border-outline-variant/40 transition-all text-xs sm:text-sm font-label-md"
            aria-label="Chia sẻ bài viết"
          >
            <span className="material-symbols-outlined text-[18px]">
              {copied ? "check" : "share"}
            </span>
            <span>{copied ? "Đã sao chép liên kết!" : "Chia sẻ bài viết"}</span>
          </button>
        </div>
      </div>
    </article>
  );
}
