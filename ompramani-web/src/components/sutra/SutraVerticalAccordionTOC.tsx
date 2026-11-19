"use client";

import React, { useState, useEffect, useMemo } from "react";
import { useRouter } from "next/navigation";
import { SutraSectionDetail, SutraSectionTreeNode } from "@/types/api";
import { getSutraSection } from "@/lib/api/sutras";
import { saveReadingProgress } from "@/lib/api/reading-progress";
import {
  checkSutraBookmarks,
  createSutraBookmark,
  removeSutraBookmarkByTarget,
} from "@/lib/api/sutra-bookmarks";
import { useAuth } from "@/context/AuthContext";

interface SutraVerticalAccordionTOCProps {
  sutraSlug: string;
  sutraId: number;
  versionId?: number;
  sections: SutraSectionTreeNode[];
  initialActiveSectionId?: number;
  initialSectionDetail?: SutraSectionDetail | null;
}

export function SutraVerticalAccordionTOC({
  sutraSlug,
  sutraId,
  versionId,
  sections,
  initialActiveSectionId,
  initialSectionDetail,
}: SutraVerticalAccordionTOCProps) {
  const router = useRouter();
  const { isAuthenticated } = useAuth();

  // Set of currently open/expanded section IDs
  const [openSectionIds, setOpenSectionIds] = useState<Set<number>>(() => {
    const initialSet = new Set<number>();
    if (initialActiveSectionId) {
      initialSet.add(initialActiveSectionId);
    } else if (sections.length > 0) {
      initialSet.add(sections[0].id);
    }
    return initialSet;
  });

  // Cached full section details { [id]: SutraSectionDetail }
  const [cachedDetails, setCachedDetails] = useState<Record<number, SutraSectionDetail>>(() => {
    const initialCache: Record<number, SutraSectionDetail> = {};
    if (initialSectionDetail) {
      initialCache[initialSectionDetail.id] = initialSectionDetail;
    }
    return initialCache;
  });

  // Loading indicator for section being fetched
  const [loadingSectionId, setLoadingSectionId] = useState<number | null>(null);

  // Font size control: 16px, 18px, 20px (default), 22px, 24px
  const [fontSizeLevel, setFontSizeLevel] = useState<number>(() => {
    if (typeof window === "undefined") return 2;
    try {
      const saved = localStorage.getItem("ompramani_reading_fontsize");
      if (saved !== null) {
        const level = parseInt(saved, 10);
        if (!isNaN(level) && level >= 0 && level <= 4) {
          return level;
        }
      }
    } catch {
      // Ignore
    }
    return 2;
  });
  const [copiedId, setCopiedId] = useState<number | null>(null);

  // Section bookmark state: set of saved section IDs
  const [savedSectionIds, setSavedSectionIds] = useState<Set<number>>(new Set());
  const [bookmarkLoadingId, setBookmarkLoadingId] = useState<number | null>(null);
  const [bookmarkFeedback, setBookmarkFeedback] = useState<string | null>(null);

  // Check initial bookmarked sections for user
  useEffect(() => {
    let ignore = false;
    if (isAuthenticated) {
      checkSutraBookmarks(sutraSlug)
        .then((res) => {
          if (!ignore && res.saved_sections) {
            setSavedSectionIds(new Set(res.saved_sections));
          }
        })
        .catch(() => {});
    }
    return () => {
      ignore = true;
    };
  }, [isAuthenticated, sutraSlug]);

  const fontSizes = [
    "text-[16px] leading-[30px]",
    "text-[18px] leading-[34px]",
    "text-[20px] leading-[38px]",
    "text-[22px] leading-[42px]",
    "text-[24px] leading-[46px]",
  ];

  const fontSizeLabels = ["16px", "18px", "20px", "22px", "24px"];

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

  // Toggle open/close of a section (accordion behavior)
  const handleToggleSection = async (sectionId: number) => {
    const isCurrentlyOpen = openSectionIds.has(sectionId);

    if (isCurrentlyOpen) {
      // Close this section ("không thì đóng lại")
      setOpenSectionIds((prev) => {
        const next = new Set(prev);
        next.delete(sectionId);
        return next;
      });
      return;
    }

    // Open this section ("khi nhấn sẽ hiện ra")
    setOpenSectionIds((prev) => {
      const next = new Set(prev);
      next.add(sectionId);
      return next;
    });

    // If not cached, fetch section details from API
    if (!cachedDetails[sectionId]) {
      setLoadingSectionId(sectionId);
      try {
        const detail = await getSutraSection(sectionId);
        if (detail) {
          setCachedDetails((prev) => ({
            ...prev,
            [sectionId]: detail,
          }));
        }
      } catch (err) {
        console.error("Failed to load section content:", err);
      } finally {
        setLoadingSectionId(null);
      }
    }

    // Auto-record reading progress in background
    if (isAuthenticated) {
      saveReadingProgress(sutraSlug, {
        sutra_section_id: sectionId,
        sutra_version_id: versionId,
      }).catch(() => {});
    }
  };

  // Expand all / Collapse all toggle
  const allOpen = useMemo(() => {
    return sections.length > 0 && sections.every((s) => openSectionIds.has(s.id));
  }, [sections, openSectionIds]);

  const handleToggleAll = async () => {
    if (allOpen) {
      // Collapse all
      setOpenSectionIds(new Set());
    } else {
      // Expand all & prefetch missing
      const allIds = new Set(sections.map((s) => s.id));
      setOpenSectionIds(allIds);

      // Fetch any un-cached sections in parallel
      const uncachedIds = sections.map((s) => s.id).filter((id) => !cachedDetails[id]);
      if (uncachedIds.length > 0) {
        Promise.all(uncachedIds.map((id) => getSutraSection(id)))
          .then((details) => {
            setCachedDetails((prev) => {
              const next = { ...prev };
              details.forEach((d) => {
                if (d) next[d.id] = d;
              });
              return next;
            });
          })
          .catch(() => {});
      }
    }
  };

  // Copy link to a specific section
  const handleCopyLink = (sectionId: number, e: React.MouseEvent) => {
    e.stopPropagation();
    if (typeof window !== "undefined") {
      const url = `${window.location.origin}/kinh/${sutraSlug}?section=${sectionId}`;
      navigator.clipboard.writeText(url);
      setCopiedId(sectionId);
      setTimeout(() => setCopiedId(null), 2500);
    }
  };

  // Bookmark a section
  const handleToggleBookmark = async (sectionId: number, e: React.MouseEvent) => {
    e.stopPropagation();
    if (!isAuthenticated) {
      router.push(`/dang-nhap?next=${encodeURIComponent(`/kinh/${sutraSlug}?section=${sectionId}`)}`);
      return;
    }

    if (bookmarkLoadingId) return;
    setBookmarkLoadingId(sectionId);

    const isBookmarked = savedSectionIds.has(sectionId);
    setSavedSectionIds((prev) => {
      const next = new Set(prev);
      if (isBookmarked) next.delete(sectionId);
      else next.add(sectionId);
      return next;
    });

    try {
      if (isBookmarked) {
        await removeSutraBookmarkByTarget({
          type: "section",
          sutra_id: sutraId,
          sutra_section_id: sectionId,
        });
        setBookmarkFeedback("Đã bỏ lưu phẩm này");
      } else {
        await createSutraBookmark({
          type: "section",
          sutra_id: sutraId,
          sutra_section_id: sectionId,
          sutra_version_id: versionId,
        });
        setBookmarkFeedback("Đã lưu dấu phẩm kinh vào sổ tay");
      }
      setTimeout(() => setBookmarkFeedback(null), 3000);
    } catch {
      // Revert on error
      setSavedSectionIds((prev) => {
        const next = new Set(prev);
        if (isBookmarked) next.add(sectionId);
        else next.delete(sectionId);
        return next;
      });
      setBookmarkFeedback("Có lỗi xảy ra, vui lòng thử lại");
      setTimeout(() => setBookmarkFeedback(null), 3000);
    } finally {
      setBookmarkLoadingId(null);
    }
  };

  return (
    <section id="muc-luc-tung-kinh" className="space-y-6 scroll-mt-24">
      {/* Header of Table of Contents & Global Controls */}
      <div className="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-outline-variant/30 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2 text-primary font-semibold text-xs tracking-wider uppercase font-label-caps">
            <span className="w-2 h-2 rounded-full bg-primary" />
            <span>Chánh Văn Tụng Niệm</span>
            <span className="text-outline">•</span>
            <span className="text-on-surface-variant font-normal">
              {sections.length} phần kinh văn
            </span>
          </div>
          <h2 className="font-headline-xl text-2xl sm:text-3xl font-bold text-on-surface font-serif mt-1 tracking-tight">
            Mục Lục Tụng Kinh
          </h2>
          <p className="font-body-md text-sm sm:text-base text-on-surface-variant mt-1 max-w-xl">
            Nhấn vào từng phần kinh văn bên dưới để mở toàn bộ lời kinh và bắt đầu tụng niệm. Nhấn lại để thu gọn.
          </p>
        </div>

        {/* Global Toolbar: Font size & Expand All */}
        <div className="flex flex-wrap items-center gap-3 shrink-0 pt-2 md:pt-0">
          {/* Font size control */}
          <div className="inline-flex items-center rounded-2xl bg-surface-container-low border border-outline-variant/40 p-1">
            <button
              type="button"
              onClick={() => changeFontSize(-1)}
              disabled={fontSizeLevel === 0}
              className="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-xs text-on-surface hover:bg-surface-container disabled:opacity-30 disabled:hover:bg-transparent transition-colors cursor-pointer"
              title="Giảm cỡ chữ"
            >
              A-
            </button>
            <span className="px-2.5 text-xs font-semibold text-primary min-w-[48px] text-center font-mono">
              {fontSizeLabels[fontSizeLevel]}
            </span>
            <button
              type="button"
              onClick={() => changeFontSize(1)}
              disabled={fontSizeLevel === 4}
              className="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-xs text-on-surface hover:bg-surface-container disabled:opacity-30 disabled:hover:bg-transparent transition-colors cursor-pointer"
              title="Tăng cỡ chữ"
            >
              A+
            </button>
          </div>

          {/* Expand / Collapse All */}
          <button
            type="button"
            onClick={handleToggleAll}
            className="px-4 py-2 rounded-2xl bg-surface-container-low hover:bg-surface-container border border-outline-variant/40 text-on-surface hover:text-primary font-label-md text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer shadow-2xs"
          >
            <span className="material-symbols-outlined text-[18px]">
              {allOpen ? "unfold_less" : "unfold_more"}
            </span>
            <span>{allOpen ? "Thu gọn tất cả" : "Mở tất cả"}</span>
          </button>
        </div>
      </div>

      {/* Global bookmark notification */}
      {bookmarkFeedback && (
        <div className="p-3 rounded-2xl bg-primary/10 border border-primary/20 text-xs sm:text-sm font-label-md text-primary flex items-center gap-2 animate-in fade-in">
          <span className="material-symbols-outlined text-[18px]">info</span>
          <span>{bookmarkFeedback}</span>
        </div>
      )}

      {/* Vertical Accordion Stack: Each section is an expandable row */}
      <div className="space-y-4">
        {sections.map((section, index) => {
          const isOpen = openSectionIds.has(section.id);
          const detail = cachedDetails[section.id];
          const isLoading = loadingSectionId === section.id && !detail;
          const isBookmarked = savedSectionIds.has(section.id);
          const isCopied = copiedId === section.id;

          // Parse page information if present
          const pagesLabel =
            section.source_page_start && section.source_page_end
              ? `Trang ${section.source_page_start} - ${section.source_page_end}`
              : section.source_page_start
              ? `Trang ${section.source_page_start}`
              : null;

          // Parse paragraphs for recitation
          const paragraphs = (detail?.content || "")
            .split(/\r?\n\s*\r?\n/)
            .map((p) => p.trim())
            .filter((p) => p.length > 0);

          return (
            <article
              key={section.id}
              id={`section-${section.id}`}
              className={`rounded-3xl border transition-all duration-300 overflow-hidden shadow-xs ${
                isOpen
                  ? "bg-surface-container-lowest border-primary/50 shadow-md ring-1 ring-primary/20"
                  : "bg-surface-container-lowest hover:bg-surface-container-lowest/90 border-outline-variant/40 hover:border-outline-variant/80"
              }`}
            >
              {/* Accordion Header (Click to expand/collapse) */}
              <button
                type="button"
                onClick={() => handleToggleSection(section.id)}
                className={`w-full p-4 sm:p-5 flex items-center justify-between gap-4 text-left cursor-pointer transition-colors ${
                  isOpen
                    ? "bg-surface-container-low/50 border-b border-outline-variant/30"
                    : "hover:bg-surface-container-low/30"
                }`}
                aria-expanded={isOpen}
              >
                {/* Left: Position badge, Title & Page */}
                <div className="flex items-center gap-3 sm:gap-4 min-w-0">
                  {/* Position number pill */}
                  <span
                    className={`shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-2xl flex items-center justify-center font-bold text-xs sm:text-sm font-mono transition-colors shadow-2xs ${
                      isOpen
                        ? "bg-primary text-on-primary"
                        : "bg-surface-container text-on-surface-variant"
                    }`}
                  >
                    {String(index + 1).padStart(2, "0")}
                  </span>

                  <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                      <span className="font-label-caps text-[11px] uppercase tracking-wider text-primary font-semibold">
                        Phần {index + 1}
                      </span>
                      {pagesLabel && (
                        <span className="text-[11px] font-medium px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant">
                          {pagesLabel}
                        </span>
                      )}
                    </div>
                    <h3 className="font-serif font-bold text-base sm:text-lg text-on-surface truncate mt-0.5 leading-snug">
                      {section.title}
                    </h3>
                  </div>
                </div>

                {/* Right: State badge & Expand chevron */}
                <div className="flex items-center gap-2 sm:gap-3 shrink-0">
                  <span
                    className={`hidden sm:inline-flex items-center text-xs font-semibold px-3 py-1 rounded-full transition-colors ${
                      isOpen
                        ? "bg-primary/10 text-primary"
                        : "bg-surface-container text-on-surface-variant/80"
                    }`}
                  >
                    {isOpen ? "Đang mở" : "Nhấn để mở"}
                  </span>

                  <span
                    className={`w-8 h-8 rounded-full flex items-center justify-center transition-transform duration-300 ${
                      isOpen
                        ? "rotate-180 bg-primary/10 text-primary"
                        : "bg-surface-container text-outline hover:text-primary"
                    }`}
                  >
                    <span className="material-symbols-outlined text-[20px]">
                      expand_more
                    </span>
                  </span>
                </div>
              </button>

              {/* Accordion Body: Revealed only when open */}
              {isOpen && (
                <div className="p-5 sm:p-8 lg:p-10 space-y-6 animate-in fade-in duration-300">
                  {/* Section Reading Toolbar */}
                  <div className="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-outline-variant/30 text-xs sm:text-sm text-outline">
                    <div className="flex items-center gap-2">
                      <span className="material-symbols-outlined text-primary text-[18px]">
                        menu_book
                      </span>
                      <span className="font-medium text-on-surface">
                        Chính văn phần {index + 1}
                      </span>
                      {pagesLabel && <span>• {pagesLabel}</span>}
                    </div>

                    <div className="flex items-center gap-2">
                      {/* Bookmark Section */}
                      <button
                        type="button"
                        onClick={(e) => handleToggleBookmark(section.id, e)}
                        disabled={bookmarkLoadingId === section.id}
                        className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-semibold transition-all cursor-pointer ${
                          isBookmarked
                            ? "bg-primary-fixed text-on-primary-fixed border-primary-fixed"
                            : "bg-surface-container-low hover:bg-surface-container text-on-surface border-outline-variant/40"
                        }`}
                        title={isBookmarked ? "Bỏ lưu dấu phẩm này" : "Lưu dấu phẩm này"}
                      >
                        <span className="material-symbols-outlined text-[16px]">
                          {isBookmarked ? "bookmark_added" : "bookmark_add"}
                        </span>
                        <span>{isBookmarked ? "Đã lưu" : "Lưu dấu"}</span>
                      </button>

                      {/* Copy section link */}
                      <button
                        type="button"
                        onClick={(e) => handleCopyLink(section.id, e)}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-surface-container-low hover:bg-surface-container border border-outline-variant/40 text-on-surface text-xs font-medium transition-colors cursor-pointer"
                        title="Sao chép liên kết phần này"
                      >
                        <span className="material-symbols-outlined text-[16px]">
                          {isCopied ? "check" : "share"}
                        </span>
                        <span>{isCopied ? "Đã sao chép" : "Chia sẻ"}</span>
                      </button>
                    </div>
                  </div>

                  {/* Sacred Chanting Content Body */}
                  {isLoading ? (
                    <div className="py-12 flex flex-col items-center justify-center space-y-3 text-primary">
                      <span className="material-symbols-outlined text-[36px] animate-spin">
                        progress_activity
                      </span>
                      <p className="font-body-md text-sm text-outline">
                        Đang thỉnh chính văn kinh điển...
                      </p>
                    </div>
                  ) : paragraphs.length > 0 ? (
                    <div className="max-w-[820px] mx-auto py-2">
                      <div
                        className={`font-serif text-on-surface font-normal select-text transition-all duration-200 ${fontSizes[fontSizeLevel]}`}
                      >
                        {paragraphs.map((paragraph, pIdx) => (
                          <p
                            key={pIdx}
                            className="mb-6 text-justify leading-relaxed tracking-normal whitespace-pre-line"
                          >
                            {paragraph}
                          </p>
                        ))}
                      </div>
                    </div>
                  ) : (
                    <div className="py-8 text-center text-outline italic font-serif">
                      Nội dung phần kinh này đang được chuẩn bị.
                    </div>
                  )}

                  {/* Section Bottom Actions: Close / Go to next section */}
                  <div className="pt-6 border-t border-outline-variant/30 flex flex-wrap items-center justify-between gap-3">
                    <button
                      type="button"
                      onClick={() => handleToggleSection(section.id)}
                      className="inline-flex items-center gap-1.5 px-4 py-2 rounded-2xl bg-surface-container-low hover:bg-surface-container border border-outline-variant/40 text-on-surface font-label-md text-xs font-semibold transition-colors cursor-pointer"
                    >
                      <span className="material-symbols-outlined text-[16px]">
                        expand_less
                      </span>
                      <span>Thu gọn phần này</span>
                    </button>

                    {index < sections.length - 1 && (
                      <button
                        type="button"
                        onClick={() => {
                          const nextSec = sections[index + 1];
                          if (nextSec) {
                            handleToggleSection(nextSec.id);
                            setTimeout(() => {
                              const el = document.getElementById(`section-${nextSec.id}`);
                              if (el) {
                                el.scrollIntoView({ behavior: "smooth", block: "start" });
                              }
                            }, 100);
                          }
                        }}
                        className="inline-flex items-center gap-2 px-5 py-2 rounded-2xl bg-primary text-on-primary font-label-md text-xs font-semibold hover:bg-primary/90 transition-all shadow-xs cursor-pointer"
                      >
                        <span>Tụng tiếp Phần {index + 2}</span>
                        <span className="material-symbols-outlined text-[16px]">
                          arrow_forward
                        </span>
                      </button>
                    )}
                  </div>
                </div>
              )}
            </article>
          );
        })}
      </div>
    </section>
  );
}
