"use client";

import React, { useState, useEffect, useRef, useCallback } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { SutraSectionDetail, SutraSectionTreeNode } from "@/types/api";
import { useAuth } from "@/context/AuthContext";
import {
  saveReadingProgress,
  getSutraReadingProgress,
} from "@/lib/api/reading-progress";
import {
  checkSutraBookmarks,
  createSutraBookmark,
  removeSutraBookmarkByTarget,
} from "@/lib/api/sutra-bookmarks";
import { ApiError } from "@/lib/api/client";

interface SutraReaderAreaProps {
  sutraSlug: string;
  versionId?: number;
  section: SutraSectionDetail;
  prevSection: SutraSectionTreeNode | null;
  nextSection: SutraSectionTreeNode | null;
}

export function SutraReaderArea({
  sutraSlug,
  versionId,
  section,
  prevSection,
  nextSection,
}: SutraReaderAreaProps) {
  const router = useRouter();
  const { isAuthenticated } = useAuth();

  // Font size control: 16px, 18px (default), 20px, 22px, 24px
  const [fontSizeLevel, setFontSizeLevel] = useState<number>(1);
  const [copied, setCopied] = useState<boolean>(false);

  // Reading progress state
  const [isSaved, setIsSaved] = useState<boolean>(false);
  const [lastSavedTime, setLastSavedTime] = useState<string | null>(null);
  const currentRevisionRef = useRef<number>(1);
  const saveTimeoutRef = useRef<NodeJS.Timeout | null>(null);

  // Section bookmark state
  const [isSectionBookmarked, setIsSectionBookmarked] = useState<boolean>(false);
  const [isBookmarkLoading, setIsBookmarkLoading] = useState<boolean>(false);
  const [bookmarkFeedback, setBookmarkFeedback] = useState<string | null>(null);

  useEffect(() => {
    try {
      const saved = localStorage.getItem("ompramani_reading_fontsize");
      if (saved !== null) {
        const level = parseInt(saved, 10);
        if (!isNaN(level) && level >= 0 && level <= 4) {
          setFontSizeLevel(level);
        }
      }
    } catch {
      // Ignore localStorage errors
    }
  }, []);

  const fontSizes = [
    "text-[16px] leading-[28px]",
    "text-[18px] leading-[32px]",
    "text-[20px] leading-[36px]",
    "text-[22px] leading-[40px]",
    "text-[24px] leading-[44px]",
  ];

  const changeFontSize = (delta: number) => {
    setFontSizeLevel((prev) => {
      const next = Math.max(0, Math.min(4, prev + delta));
      try {
        localStorage.setItem("ompramani_reading_fontsize", next.toString());
      } catch {
        // Ignore
      }
      return next;
    });
  };

  const handleCopyLink = () => {
    if (typeof window !== "undefined") {
      navigator.clipboard.writeText(window.location.href);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    }
  };

  // --- Check section bookmark status ---
  useEffect(() => {
    let ignore = false;
    if (isAuthenticated) {
      checkSutraBookmarks(sutraSlug)
        .then((res) => {
          if (!ignore) {
            setIsSectionBookmarked(res.saved_sections.includes(section.id));
          }
        })
        .catch(() => {});
    } else {
      setIsSectionBookmarked(false);
    }
    return () => {
      ignore = true;
    };
  }, [isAuthenticated, sutraSlug, section.id]);

  // --- Toggle section bookmark ---
  const handleToggleSectionBookmark = async () => {
    if (!isAuthenticated) {
      const currentUrl = typeof window !== "undefined" ? window.location.pathname + window.location.search : `/kinh/${sutraSlug}`;
      router.push(`/dang-nhap?next=${encodeURIComponent(currentUrl)}`);
      return;
    }

    if (isBookmarkLoading) return;
    setIsBookmarkLoading(true);

    const prevSaved = isSectionBookmarked;
    setIsSectionBookmarked(!prevSaved);

    try {
      if (prevSaved) {
        await removeSutraBookmarkByTarget({
          type: "section",
          sutra_id: section.sutra.id,
          sutra_section_id: section.id,
        });
        setBookmarkFeedback("Đã bỏ đánh dấu phẩm");
      } else {
        await createSutraBookmark({
          type: "section",
          sutra_id: section.sutra.id,
          sutra_version_id: versionId,
          sutra_section_id: section.id,
        });
        setBookmarkFeedback("Đã đánh dấu phẩm này");
      }
      setTimeout(() => setBookmarkFeedback(null), 3000);
    } catch {
      setIsSectionBookmarked(prevSaved);
      setBookmarkFeedback("Có lỗi xảy ra, vui lòng thử lại");
      setTimeout(() => setBookmarkFeedback(null), 3000);
    } finally {
      setIsBookmarkLoading(false);
    }
  };

  // --- Auto-save Reading Progress with Debounce ---
  const doSaveProgress = useCallback(
    async (scrollPct: number = 0) => {
      if (!isAuthenticated) return;

      try {
        const saved = await saveReadingProgress(sutraSlug, {
          sutra_version_id: versionId,
          sutra_section_id: section.id,
          scroll_percentage: scrollPct,
          revision: currentRevisionRef.current,
        });
        currentRevisionRef.current = (saved.revision || currentRevisionRef.current) + 1;
        setIsSaved(true);
        setLastSavedTime(
          new Date().toLocaleTimeString("vi-VN", {
            hour: "2-digit",
            minute: "2-digit",
          })
        );
      } catch (err) {
        if (err instanceof ApiError && err.status === 409) {
          // Stale revision: sync with server
          const latest = await getSutraReadingProgress(sutraSlug, versionId);
          if (latest) {
            currentRevisionRef.current = latest.revision + 1;
          }
        }
      }
    },
    [isAuthenticated, sutraSlug, versionId, section.id]
  );

  useEffect(() => {
    if (!isAuthenticated) return;

    // Reset saved state when switching sections
    setIsSaved(false);

    // Initial save after 1.5s reading on this section
    if (saveTimeoutRef.current) {
      clearTimeout(saveTimeoutRef.current);
    }

    saveTimeoutRef.current = setTimeout(() => {
      doSaveProgress(0);
    }, 1500);

    return () => {
      if (saveTimeoutRef.current) {
        clearTimeout(saveTimeoutRef.current);
      }
    };
  }, [isAuthenticated, section.id, doSaveProgress]);

  // Handle scroll progress tracking
  useEffect(() => {
    if (!isAuthenticated) return;

    const handleScroll = () => {
      if (saveTimeoutRef.current) {
        clearTimeout(saveTimeoutRef.current);
      }

      saveTimeoutRef.current = setTimeout(() => {
        const totalHeight =
          document.documentElement.scrollHeight - window.innerHeight;
        const currentScroll = window.scrollY;
        const pct =
          totalHeight > 0
            ? Math.min(100, Math.max(0, Math.round((currentScroll / totalHeight) * 100)))
            : 0;

        doSaveProgress(pct);
      }, 2000);
    };

    window.addEventListener("scroll", handleScroll, { passive: true });
    return () => {
      window.removeEventListener("scroll", handleScroll);
      if (saveTimeoutRef.current) {
        clearTimeout(saveTimeoutRef.current);
      }
    };
  }, [isAuthenticated, doSaveProgress]);

  // Verbatim paragraph formatting
  const rawContent = section.content || "Chưa có nội dung cho phần này.";
  const paragraphs = rawContent
    .split(/\r?\n/)
    .map((p) => p.trim())
    .filter((p) => p.length > 0);

  return (
    <article className="flex-1 min-w-0 bg-surface-container-lowest rounded-3xl p-4 sm:p-8 md:p-10 border border-outline-variant/30 shadow-sm flex flex-col justify-between">
      <div>
        {/* Section Header & Toolbar */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-outline-variant/30 mb-8">
          <div>
            <div className="flex items-center gap-2 mb-1">
              <span className="w-2 h-2 rounded-full bg-primary" />
              <span className="font-label-caps text-label-caps text-primary uppercase">
                Chính Văn Khai Thị
              </span>
              {(section.source_page_start || section.source_page_end) && (
                <span className="text-[11px] font-label-md text-outline">
                  · Trang {section.source_page_start}
                  {section.source_page_end && ` - ${section.source_page_end}`}
                </span>
              )}
              {isSaved && (
                <span className="inline-flex items-center gap-1 text-[11px] font-label-md text-primary ml-2 animate-in fade-in">
                  <span className="material-symbols-outlined text-[13px]">
                    check_circle
                  </span>
                  <span>Đã lưu vị trí đọc {lastSavedTime ? `(${lastSavedTime})` : ""}</span>
                </span>
              )}
            </div>
            <h2 className="font-headline-xl text-2xl sm:text-3xl font-bold text-on-surface">
              {section.title}
            </h2>
          </div>

          {/* Reading Controls Toolbar */}
          <div className="flex items-center gap-2 shrink-0 self-start sm:self-auto bg-surface-container-low p-1.5 rounded-2xl border border-outline-variant/40">
            {/* Font size decrement */}
            <button
              type="button"
              suppressHydrationWarning
              onClick={() => changeFontSize(-1)}
              disabled={fontSizeLevel === 0}
              className="w-9 h-9 min-w-[36px] min-h-[36px] rounded-xl flex items-center justify-center font-bold text-on-surface hover:bg-surface-container hover:text-primary disabled:opacity-30 disabled:cursor-not-allowed transition-colors cursor-pointer touch-manipulation"
              title="Giảm cỡ chữ"
              aria-label="Giảm cỡ chữ"
            >
              A-
            </button>
            <span className="text-[12px] font-label-md text-outline px-1">
              {16 + fontSizeLevel * 2}px
            </span>
            {/* Font size increment */}
            <button
              type="button"
              suppressHydrationWarning
              onClick={() => changeFontSize(1)}
              disabled={fontSizeLevel === 4}
              className="w-9 h-9 min-w-[36px] min-h-[36px] rounded-xl flex items-center justify-center font-bold text-on-surface hover:bg-surface-container hover:text-primary disabled:opacity-30 disabled:cursor-not-allowed transition-colors cursor-pointer touch-manipulation"
              title="Tăng cỡ chữ"
              aria-label="Tăng cỡ chữ"
            >
              A+
            </button>

            <div className="w-[1px] h-4 bg-outline-variant/50 mx-1" />

            {/* Bookmark section button */}
            <button
              type="button"
              suppressHydrationWarning
              onClick={handleToggleSectionBookmark}
              disabled={isBookmarkLoading}
              className={`w-9 h-9 min-w-[36px] min-h-[36px] rounded-xl flex items-center justify-center transition-colors cursor-pointer touch-manipulation ${
                isSectionBookmarked
                  ? "bg-primary text-on-primary shadow-xs"
                  : "text-on-surface hover:bg-surface-container hover:text-primary"
              }`}
              title={
                isSectionBookmarked
                  ? "Đã đánh dấu phẩm này (bấm để hủy)"
                  : "Đánh dấu phẩm này để đọc lại sau"
              }
              aria-label="Đánh dấu phẩm này"
            >
              <span className="material-symbols-outlined text-[18px]">
                {isSectionBookmarked ? "bookmark_added" : "bookmark_add"}
              </span>
            </button>

            {/* Copy share link */}
            <button
              type="button"
              suppressHydrationWarning
              onClick={handleCopyLink}
              className="w-8 h-8 rounded-xl flex items-center justify-center text-on-surface hover:bg-surface-container hover:text-primary transition-colors cursor-pointer"
              title="Sao chép liên kết phẩm này"
            >
              <span className="material-symbols-outlined text-[18px]">
                {copied ? "check" : "share"}
              </span>
            </button>
          </div>
        </div>

        {bookmarkFeedback && (
          <div className="mb-4 -mt-4 text-xs font-label-md text-primary animate-in fade-in">
            {bookmarkFeedback}
          </div>
        )}

        {/* Sacred Content Body Container */}
        <div className="max-w-[760px] mx-auto py-2">
          <div
            className={`font-serif text-on-surface font-normal transition-all duration-200 ${fontSizes[fontSizeLevel]}`}
          >
            {paragraphs.map((paragraph, idx) => (
              <p key={idx} className="mb-6 text-justify indent-6">
                {paragraph}
              </p>
            ))}
          </div>
        </div>
      </div>

      {/* Bottom Linear Navigation: Previous / Next Section */}
      <nav
        aria-label="Điều hướng phẩm trước và tiếp theo"
        className="mt-12 pt-8 border-t border-outline-variant/30 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4"
      >
        {prevSection ? (
          <Link
            href={versionId ? `/kinh/${sutraSlug}?version=${versionId}&section=${prevSection.id}` : `/kinh/${sutraSlug}?section=${prevSection.id}`}
            className="flex items-center gap-3 p-3.5 rounded-2xl bg-surface-container-low hover:bg-surface-container text-on-surface hover:text-primary transition-all border border-outline-variant/40 group max-w-sm"
          >
            <span className="material-symbols-outlined text-primary text-[22px] group-hover:-translate-x-1 transition-transform">
              arrow_back
            </span>
            <div className="min-w-0 text-left">
              <span className="font-label-caps text-[10px] text-outline uppercase block">
                Phần trước
              </span>
              <span className="font-label-lg text-sm font-semibold truncate block">
                {prevSection.title}
              </span>
            </div>
          </Link>
        ) : (
          <div className="p-3.5 rounded-2xl bg-surface-container/20 border border-outline-variant/20 text-outline flex items-center gap-2 text-sm opacity-50 cursor-not-allowed">
            <span className="material-symbols-outlined text-[20px]">
              first_page
            </span>
            <span>Đầu bản kinh</span>
          </div>
        )}

        {nextSection ? (
          <Link
            href={versionId ? `/kinh/${sutraSlug}?version=${versionId}&section=${nextSection.id}` : `/kinh/${sutraSlug}?section=${nextSection.id}`}
            className="flex items-center justify-end gap-3 p-3.5 rounded-2xl bg-surface-container-low hover:bg-surface-container text-on-surface hover:text-primary transition-all border border-outline-variant/40 group max-w-sm text-right"
          >
            <div className="min-w-0">
              <span className="font-label-caps text-[10px] text-outline uppercase block">
                Phần tiếp theo
              </span>
              <span className="font-label-lg text-sm font-semibold truncate block">
                {nextSection.title}
              </span>
            </div>
            <span className="material-symbols-outlined text-primary text-[22px] group-hover:translate-x-1 transition-transform">
              arrow_forward
            </span>
          </Link>
        ) : (
          <div className="p-3.5 rounded-2xl bg-surface-container/20 border border-outline-variant/20 text-outline flex items-center justify-end gap-2 text-sm opacity-50 cursor-not-allowed">
            <span>Cuối bản kinh</span>
            <span className="material-symbols-outlined text-[20px]">
              last_page
            </span>
          </div>
        )}
      </nav>
    </article>
  );
}
