"use client";

import React, { useState, useEffect } from "react";
import Link from "next/link";
import Image from "next/image";
import { useRouter } from "next/navigation";
import { Sutra, SutraVersion, SutraReadingProgress } from "@/types/api";
import { resolveMediaUrl } from "@/lib/utils/media";
import { useAuth } from "@/context/AuthContext";
import { getSutraReadingProgress } from "@/lib/api/reading-progress";
import {
  checkSutraBookmarks,
  createSutraBookmark,
  removeSutraBookmarkByTarget,
} from "@/lib/api/sutra-bookmarks";
import { FavoriteButton } from "@/components/favorites/FavoriteButton";

interface SutraHeaderProps {
  sutra: Sutra;
  activeVersion?: SutraVersion | null;
  totalSections?: number;
}

export function SutraHeader({ sutra, activeVersion, totalSections }: SutraHeaderProps) {
  const router = useRouter();
  const { isAuthenticated } = useAuth();
  const imageUrl = resolveMediaUrl(sutra.thumbnail);
  const translatorName = sutra.translator || activeVersion?.translator;
  const publisherName = sutra.publisher || activeVersion?.publisher;
  const pubYear = sutra.publication_year || activeVersion?.publication_year;

  const [readingProgress, setReadingProgress] =
    useState<SutraReadingProgress | null>(null);
  const [isSutraSaved, setIsSutraSaved] = useState<boolean>(false);
  const [isBookmarkLoading, setIsBookmarkLoading] = useState<boolean>(false);
  const [feedbackMessage, setFeedbackMessage] = useState<string | null>(null);

  // Load progress and bookmark status if user is authenticated
  useEffect(() => {
    let ignore = false;
    if (!isAuthenticated) {
      return;
    }

    Promise.all([
      getSutraReadingProgress(sutra.slug, activeVersion?.id),
      checkSutraBookmarks(sutra.slug),
    ])
      .then(([progress, bookmarkCheck]) => {
        if (!ignore) {
          setReadingProgress(progress);
          setIsSutraSaved(bookmarkCheck.is_sutra_saved);
        }
      })
      .catch(() => {});

    return () => {
      ignore = true;
    };
  }, [isAuthenticated, sutra.slug, activeVersion?.id]);

  const handleToggleSutraBookmark = async () => {
    if (!isAuthenticated) {
      router.push(`/dang-nhap?next=/kinh/${sutra.slug}`);
      return;
    }

    if (isBookmarkLoading) return;
    setIsBookmarkLoading(true);

    const prevSaved = isSutraSaved;
    // Optimistic UI update
    setIsSutraSaved(!prevSaved);

    try {
      if (prevSaved) {
        await removeSutraBookmarkByTarget({
          type: "sutra",
          sutra_id: sutra.id,
        });
        setFeedbackMessage("Đã bỏ lưu kinh khỏi tủ");
      } else {
        await createSutraBookmark({
          type: "sutra",
          sutra_id: sutra.id,
          sutra_version_id: activeVersion?.id,
        });
        setFeedbackMessage("Đã lưu kinh vào tủ");
      }
      setTimeout(() => setFeedbackMessage(null), 3000);
    } catch {
      // Revert on error
      setIsSutraSaved(prevSaved);
      setFeedbackMessage("Có lỗi xảy ra, vui lòng thử lại");
      setTimeout(() => setFeedbackMessage(null), 3000);
    } finally {
      setIsBookmarkLoading(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* Breadcrumbs */}
      <nav
        aria-label="Breadcrumb"
        className="flex items-center gap-2 text-[13px] font-label-md text-outline"
      >
        <Link href="/" className="hover:text-primary transition-colors">
          Trang chủ
        </Link>
        <span className="material-symbols-outlined text-[14px]">
          chevron_right
        </span>
        <Link href="/kinh" className="hover:text-primary transition-colors">
          Thư viện Kinh
        </Link>
        <span className="material-symbols-outlined text-[14px]">
          chevron_right
        </span>
        <span className="text-on-surface font-semibold truncate max-w-xs sm:max-w-md">
          {sutra.title}
        </span>
      </nav>

      {/* Main Sutra Hero Info */}
      <div className="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-outline-variant/30 shadow-sm flex flex-col md:flex-row gap-6 items-start">
        {/* Sutra Thumbnail / Emblem */}
        <div className="w-full md:w-44 h-48 rounded-2xl overflow-hidden relative shrink-0 bg-surface-container-low border border-outline-variant/30">
          {imageUrl ? (
            <Image
              src={imageUrl}
              alt={sutra.title}
              fill
              sizes="(max-width: 768px) 100vw, 176px"
              className="object-cover"
              priority
            />
          ) : (
            <div className="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-surface-container to-surface-container-high text-primary p-4 text-center">
              <span className="material-symbols-outlined text-[48px] mb-2 opacity-80">
                menu_book
              </span>
              <span className="font-headline-md text-xs font-semibold text-on-surface line-clamp-2">
                {sutra.title}
              </span>
            </div>
          )}
        </div>

        {/* Info Column */}
        <div className="flex-1 space-y-3 min-w-0">
          <div className="flex flex-wrap items-center gap-2">
            {sutra.tradition && (
              <span className="px-3 py-1 rounded-full bg-surface-container text-primary font-label-md text-label-md font-semibold">
                {sutra.tradition.name}
              </span>
            )}
            {translatorName && (
              <span className="px-3 py-1 rounded-full bg-secondary-container text-on-secondary-container font-label-md text-label-md">
                Dịch giả: {translatorName}
              </span>
            )}
          </div>

          <div>
            <h1 className="font-headline-xl text-2xl sm:text-3xl lg:text-4xl font-bold text-on-surface tracking-tight">
              {sutra.title}
            </h1>
            {sutra.original_title && (
              <p className="font-quote-editorial text-primary italic text-base sm:text-lg mt-1">
                {sutra.original_title}
              </p>
            )}
          </div>

          {sutra.short_description && (
            <p className="font-body-md text-body-md text-on-surface-variant max-w-3xl leading-relaxed">
              {sutra.short_description}
            </p>
          )}

          {(publisherName || pubYear || totalSections) && (
            <div className="pt-2 flex flex-wrap items-center gap-3 text-[12px] font-label-md text-outline">
              {publisherName && (
                <span>Nhà xuất bản: {publisherName}</span>
              )}
              {publisherName && pubYear && (
                <span>•</span>
              )}
              {pubYear && (
                <span>Năm: {pubYear}</span>
              )}
              {(publisherName || pubYear) && totalSections && (
                <span>•</span>
              )}
              {totalSections && (
                <span className="font-semibold text-primary">
                  {totalSections} phần kinh văn
                </span>
              )}
            </div>
          )}

          {/* User Reading Progress & Bookmark Actions Bar */}
          <div className="pt-4 flex flex-wrap items-center gap-3">
            {/* Primary Action Button: Đi tới tụng kinh */}
            <button
              type="button"
              onClick={() => {
                const el = document.getElementById("muc-luc-tung-kinh");
                if (el) {
                  el.scrollIntoView({ behavior: "smooth", block: "start" });
                }
              }}
              className="inline-flex items-center gap-2.5 px-6 py-2.5 rounded-2xl bg-primary text-on-primary font-label-md text-sm font-semibold hover:bg-primary/90 shadow-xs hover:shadow-md transition-all cursor-pointer ring-2 ring-primary/20"
            >
              <span className="material-symbols-outlined text-[20px]">
                auto_stories
              </span>
              <span>
                {readingProgress
                  ? `Đi tới tụng kinh (Tiếp: ${readingProgress.section.title})`
                  : "Đi tới tụng kinh"}
              </span>
              <span className="material-symbols-outlined text-[18px]">
                arrow_downward
              </span>
            </button>

            <button
              type="button"
              suppressHydrationWarning
              onClick={handleToggleSutraBookmark}
              disabled={isBookmarkLoading}
              className={`inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl font-label-md text-xs font-semibold border transition-all cursor-pointer ${
                isSutraSaved
                  ? "bg-primary-fixed text-on-primary-fixed border-primary-fixed shadow-xs"
                  : "bg-surface-container-low border-outline-variant/40 text-on-surface hover:bg-surface-container hover:text-primary"
              }`}
              title={isSutraSaved ? "Bỏ lưu kinh này" : "Lưu kinh vào tủ"}
            >
              <span className="material-symbols-outlined text-[18px]">
                {isSutraSaved ? "bookmark_added" : "bookmark_add"}
              </span>
              <span>{isSutraSaved ? "Đã lưu vào tủ kinh" : "Lưu vào tủ kinh"}</span>
            </button>

            <FavoriteButton
              contentType="sutra"
              contentPublicId={sutra.slug}
              variant="pill"
              showLabel
            />

            {feedbackMessage && (
              <span className="text-xs font-label-md text-primary animate-in fade-in">
                {feedbackMessage}
              </span>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
