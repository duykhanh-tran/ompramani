"use client";

import React, { useState } from "react";
import { BuddhistFigureVow } from "@/types/api";
import { parseSafeContent } from "@/lib/utils/sacred";

interface FigureVowsAccordionProps {
  figureName: string;
  vowsIntroduction?: string | null;
  legacyGreatVows?: string | null;
  vows?: BuddhistFigureVow[];
}

export function FigureVowsAccordion({
  figureName,
  vowsIntroduction,
  legacyGreatVows,
  vows = [],
}: FigureVowsAccordionProps) {
  // State: whether the entire list of vows is dropped down or collapsed into the summary
  const [isListExpanded, setIsListExpanded] = useState<boolean>(false);

  // State: set of individual vows that are currently expanded within the list
  const [expandedVowIds, setExpandedVowIds] = useState<Set<number>>(new Set());

  // Determine introductory text (new vows_introduction or fallback to legacy great_vows if no individual vows)
  const introText =
    vowsIntroduction && vowsIntroduction.trim().length > 0
      ? vowsIntroduction
      : vows.length === 0 && legacyGreatVows && legacyGreatVows.trim().length > 0
      ? legacyGreatVows
      : null;

  const hasIntro = Boolean(introText && introText.trim().length > 0);
  const hasVowItems = vows.length > 0;

  // If no introduction and no vow items, hide entire section gracefully
  if (!hasIntro && !hasVowItems) {
    return null;
  }

  const toggleIndividualVow = (id: number) => {
    setExpandedVowIds((prev) => {
      const next = new Set(prev);
      if (next.has(id)) {
        next.delete(id);
      } else {
        next.add(id);
      }
      return next;
    });
  };

  const allIndividualExpanded =
    vows.length > 0 && vows.every((v) => expandedVowIds.has(v.id));

  const handleToggleAllIndividual = () => {
    if (allIndividualExpanded) {
      setExpandedVowIds(new Set());
    } else {
      setExpandedVowIds(new Set(vows.map((v) => v.id)));
    }
  };

  const parsedIntro = introText ? parseSafeContent(introText) : null;

  return (
    <section
      aria-labelledby="vows-heading"
      className="bg-gradient-to-br from-surface-container-lowest via-surface-container-low/40 to-secondary-container/15 rounded-3xl p-6 sm:p-8 lg:p-10 border border-secondary-container/50 shadow-sm space-y-6"
    >
      {/* 1. Tiêu đề phần Đại Nguyện (Tách biệt riêng một khối) */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-outline-variant/30">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-xl bg-secondary-container flex items-center justify-center text-on-secondary-container shrink-0">
            <span className="material-symbols-outlined text-[22px]">
              volunteer_activism
            </span>
          </div>
          <div>
            <span className="font-label-caps text-label-caps text-primary uppercase block">
              Bi Nguyện Độ Sinh
            </span>
            <h2
              id="vows-heading"
              className="font-headline-lg text-xl sm:text-2xl font-bold text-on-surface"
            >
              Đại Nguyện của {figureName}
            </h2>
          </div>
        </div>

        {hasVowItems && (
          <span className="px-3 py-1 rounded-full bg-secondary-container text-on-secondary-container font-label-md text-xs font-semibold self-start sm:self-auto">
            {vows.length} đại nguyện
          </span>
        )}
      </div>

      {/* 2. Phần Đại Nguyện Tổng (Luôn hiển thị trang nghiêm) */}
      {hasIntro && parsedIntro && (
        <div className="space-y-3 w-full">
          {parsedIntro.isHtml ? (
            <div
              className="font-serif text-[16px] sm:text-[17px] leading-[32px] text-on-surface w-full break-words"
              dangerouslySetInnerHTML={{ __html: parsedIntro.sanitizedHtml }}
            />
          ) : (
            <div className="font-serif text-[16px] sm:text-[17px] leading-[32px] text-on-surface w-full space-y-3">
              {parsedIntro.paragraphs.map((p, idx) => (
                <p key={idx} className="whitespace-pre-line leading-relaxed text-justify">
                  {p}
                </p>
              ))}
            </div>
          )}
        </div>
      )}

      {/* 3. Thu gọn danh sách đại nguyện vào phần tổng - Nhấn nút để sổ xuống xem */}
      {hasVowItems && (
        <div className="pt-2 space-y-4">
          {/* Nút bấm sổ xuống / thu gọn danh sách đại nguyện */}
          <button
            type="button"
            onClick={() => setIsListExpanded(!isListExpanded)}
            className={`w-full p-4 sm:p-5 rounded-2xl border transition-all duration-300 flex items-center justify-between gap-4 cursor-pointer shadow-2xs group ${
              isListExpanded
                ? "bg-primary text-on-primary border-primary shadow-sm"
                : "bg-surface-container-low hover:bg-surface-container border-secondary-container/60 hover:border-primary text-on-surface"
            }`}
            aria-expanded={isListExpanded}
          >
            <div className="flex items-center gap-3 min-w-0">
              <div
                className={`w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-transform ${
                  isListExpanded
                    ? "bg-white/20 text-white"
                    : "bg-primary/10 text-primary group-hover:scale-105"
                }`}
              >
                <span className="material-symbols-outlined text-[22px]">
                  {isListExpanded ? "unfold_less" : "format_list_numbered"}
                </span>
              </div>
              <div className="text-left min-w-0">
                <span
                  className={`font-label-caps text-[11px] uppercase tracking-wider block font-semibold ${
                    isListExpanded ? "text-white/80" : "text-primary"
                  }`}
                >
                  Toàn Văn Bi Nguyện
                </span>
                <span
                  className={`font-headline-md text-sm sm:text-base font-bold block truncate ${
                    isListExpanded ? "text-white" : "text-on-surface"
                  }`}
                >
                  {isListExpanded
                    ? `Thu gọn danh sách ${vows.length} đại nguyện`
                    : `Xem chi tiết ${vows.length} đại nguyện độ sinh`}
                </span>
              </div>
            </div>

            <div
              className={`flex items-center gap-1.5 font-label-md text-xs sm:text-sm font-semibold shrink-0 px-3.5 py-1.5 rounded-xl transition-colors ${
                isListExpanded
                  ? "bg-white/20 text-white"
                  : "bg-surface-container text-primary border border-outline-variant/40 group-hover:border-primary"
              }`}
            >
              <span>{isListExpanded ? "Thu gọn" : "Sổ xuống xem"}</span>
              <span
                className={`material-symbols-outlined text-[18px] transition-transform duration-300 ${
                  isListExpanded ? "rotate-180" : ""
                }`}
              >
                expand_more
              </span>
            </div>
          </button>

          {/* Danh sách đại nguyện được sổ xuống khi nhấn */}
          {isListExpanded && (
            <div className="space-y-4 pt-2 animate-in fade-in duration-300">
              {/* Thanh điều khiển tiện ích: Mở tất cả / Thu gọn tất cả */}
              <div className="flex items-center justify-between px-2 text-xs font-label-md text-outline">
                <span className="font-medium text-on-surface-variant">
                  Toàn bộ {vows.length} đại nguyện độ sinh:
                </span>
                <button
                  type="button"
                  onClick={handleToggleAllIndividual}
                  className="text-primary hover:underline font-semibold flex items-center gap-1 cursor-pointer"
                >
                  <span className="material-symbols-outlined text-[16px]">
                    {allIndividualExpanded ? "unfold_less" : "unfold_more"}
                  </span>
                  <span>
                    {allIndividualExpanded ? "Thu gọn tất cả" : "Mở tất cả chi tiết"}
                  </span>
                </button>
              </div>

              {/* Từng mục đại nguyện */}
              <div className="space-y-3">
                {vows.map((vow, index) => {
                  const isExpanded = expandedVowIds.has(vow.id);
                  const contentId = `vow-content-${vow.id}`;
                  const buttonId = `vow-button-${vow.id}`;
                  const orderNum = String(index + 1).padStart(2, "0");

                  return (
                    <div
                      key={vow.id}
                      className={`rounded-2xl border transition-all duration-200 overflow-hidden ${
                        isExpanded
                          ? "bg-surface-container-lowest border-primary/40 shadow-xs"
                          : "bg-surface-container-low/70 hover:bg-surface-container-lowest border-outline-variant/30 hover:border-outline-variant/60"
                      }`}
                    >
                      {/* Accordion Trigger Header */}
                      <button
                        type="button"
                        id={buttonId}
                        aria-expanded={isExpanded}
                        aria-controls={contentId}
                        onClick={() => toggleIndividualVow(vow.id)}
                        className="w-full p-4 sm:p-5 flex items-center justify-between gap-4 text-left cursor-pointer transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 rounded-2xl"
                      >
                        <div className="flex items-center gap-3 sm:gap-4 min-w-0">
                          {/* Order Badge */}
                          <span className="w-8 h-8 rounded-xl bg-secondary-container/90 text-on-secondary-container font-headline-md text-xs font-bold flex items-center justify-center shrink-0">
                            {orderNum}
                          </span>

                          {/* Vow Title */}
                          <h3 className="font-headline-md text-sm sm:text-base font-semibold text-on-surface truncate">
                            {vow.title}
                          </h3>
                        </div>

                        {/* Toggle CTA Button with Accessible Semantic Label */}
                        <div
                          className={`px-3 py-1.5 rounded-xl font-label-md text-xs font-medium flex items-center gap-1 shrink-0 transition-colors ${
                            isExpanded
                              ? "bg-primary text-on-primary"
                              : "bg-surface-container hover:bg-surface-container-high text-on-surface-variant hover:text-primary border border-outline-variant/40"
                          }`}
                        >
                          <span>{isExpanded ? "Thu gọn" : "Chi tiết"}</span>
                          <span
                            className={`material-symbols-outlined text-[16px] transition-transform duration-200 ${
                              isExpanded ? "rotate-180" : ""
                            }`}
                          >
                            expand_more
                          </span>
                        </div>
                      </button>

                      {/* Accordion Expandable Content */}
                      {isExpanded && (
                        <div
                          id={contentId}
                          role="region"
                          aria-labelledby={buttonId}
                          className="px-5 pb-6 pt-1 sm:px-6 sm:pb-6 text-on-surface-variant border-t border-outline-variant/20"
                        >
                          <div className="font-serif text-[15px] sm:text-[16px] leading-[30px] text-on-surface max-w-3xl whitespace-pre-line pt-3">
                            {vow.content}
                          </div>
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>

              {/* Nút thu gọn ở cuối danh sách */}
              <div className="pt-4 flex justify-center">
                <button
                  type="button"
                  onClick={() => {
                    setIsListExpanded(false);
                    const el = document.getElementById("vows-heading");
                    if (el) {
                      el.scrollIntoView({ behavior: "smooth", block: "start" });
                    }
                  }}
                  className="px-6 py-2.5 rounded-full bg-surface-container hover:bg-surface-container-high text-on-surface border border-outline-variant/40 font-label-md text-xs font-semibold flex items-center gap-2 transition-colors cursor-pointer shadow-2xs"
                >
                  <span className="material-symbols-outlined text-[18px]">
                    expand_less
                  </span>
                  <span>Thu gọn danh sách đại nguyện</span>
                </button>
              </div>
            </div>
          )}
        </div>
      )}
    </section>
  );
}
