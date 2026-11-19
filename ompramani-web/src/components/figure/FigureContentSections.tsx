import React from "react";
import { BuddhistFigure } from "@/types/api";
import { parseSafeContent } from "@/lib/utils/sacred";
import { FigureVowsAccordion } from "./FigureVowsAccordion";

interface FigureContentSectionsProps {
  figure: BuddhistFigure;
}

function ContentBody({
  rawContent,
  isEditorial = false,
}: {
  rawContent: string;
  isEditorial?: boolean;
}) {
  const parsed = parseSafeContent(rawContent);

  if (parsed.isHtml) {
    return (
      <div
        className={`max-w-[840px] space-y-4 leading-relaxed break-words ${
          isEditorial
            ? "font-serif text-[17px] sm:text-[18px] leading-[32px] text-on-surface"
            : "font-body-lg text-[16px] sm:text-[17px] leading-[30px] text-on-surface-variant"
        }`}
        dangerouslySetInnerHTML={{ __html: parsed.sanitizedHtml }}
      />
    );
  }

  return (
    <div
      className={`max-w-[840px] space-y-4 break-words ${
        isEditorial
          ? "font-serif text-[17px] sm:text-[18px] leading-[32px] text-on-surface"
          : "font-body-lg text-[16px] sm:text-[17px] leading-[30px] text-on-surface-variant"
      }`}
    >
      {parsed.paragraphs.map((paragraph, index) => (
        <p key={index} className="whitespace-pre-line leading-relaxed">
          {paragraph}
        </p>
      ))}
    </div>
  );
}

export function FigureContentSections({ figure }: FigureContentSectionsProps) {
  const hasBiography = Boolean(figure.biography && figure.biography.trim());
  const hasVowsIntro = Boolean(figure.vows_introduction && figure.vows_introduction.trim());
  const hasVowItems = Boolean(figure.vows && figure.vows.length > 0);
  const hasLegacyGreatVows = Boolean(figure.great_vows && figure.great_vows.trim());
  const hasIconography = Boolean(figure.iconography && figure.iconography.trim());
  const hasPracticeGuidance = Boolean(figure.practice_guidance && figure.practice_guidance.trim());

  if (
    !hasBiography &&
    !hasVowsIntro &&
    !hasVowItems &&
    !hasLegacyGreatVows &&
    !hasIconography &&
    !hasPracticeGuidance
  ) {
    return null;
  }

  return (
    <div className="space-y-8">
      {/* 1. Thông Tin Giới Thiệu Chi Tiết */}
      {hasBiography && figure.biography && (
        <section
          aria-labelledby="biography-heading"
          className="bg-surface-container-lowest rounded-3xl p-6 sm:p-10 border border-outline-variant/30 shadow-sm space-y-5"
        >
          <div className="flex items-center gap-3 pb-4 border-b border-outline-variant/30">
            <div className="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center text-primary shrink-0">
              <span className="material-symbols-outlined text-[22px]">
                history_edu
              </span>
            </div>
            <div>
              <span className="font-label-caps text-label-caps text-primary uppercase block">
                Bổn Tích &amp; Công Hạnh
              </span>
              <h2
                id="biography-heading"
                className="font-headline-lg text-xl sm:text-2xl font-bold text-on-surface"
              >
                Giới Thiệu &amp; Công Hạnh
              </h2>
            </div>
          </div>

          <ContentBody rawContent={figure.biography} />
        </section>
      )}

      {/* 2. Đại Nguyện (Giới thiệu chung + Accordion từng mục chi tiết) */}
      <FigureVowsAccordion
        figureName={figure.name}
        vowsIntroduction={figure.vows_introduction}
        legacyGreatVows={figure.great_vows}
        vows={figure.vows}
      />

      {/* 3. Legacy Iconography (Bảo toàn dữ liệu lịch sử) */}
      {hasIconography && figure.iconography && (
        <section className="bg-surface-container-lowest rounded-3xl p-6 sm:p-10 border border-outline-variant/30 shadow-sm space-y-5">
          <div className="flex items-center gap-3 pb-4 border-b border-outline-variant/30">
            <div className="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center text-primary shrink-0">
              <span className="material-symbols-outlined text-[22px]">
                filter_vintage
              </span>
            </div>
            <div>
              <span className="font-label-caps text-label-caps text-primary uppercase block">
                Pháp Tướng Trang Nghiêm
              </span>
              <h2 className="font-headline-lg text-xl sm:text-2xl font-bold text-on-surface">
                Tôn Tượng &amp; Biểu Trưng
              </h2>
            </div>
          </div>

          <ContentBody rawContent={figure.iconography} />
        </section>
      )}

      {/* 4. Legacy Practice Guidance (Bảo toàn dữ liệu lịch sử) */}
      {hasPracticeGuidance && figure.practice_guidance && (
        <section className="bg-surface-container-lowest rounded-3xl p-6 sm:p-10 border border-outline-variant/30 shadow-sm space-y-5">
          <div className="flex items-center gap-3 pb-4 border-b border-outline-variant/30">
            <div className="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center text-primary shrink-0">
              <span className="material-symbols-outlined text-[22px]">
                self_improvement
              </span>
            </div>
            <div>
              <span className="font-label-caps text-label-caps text-primary uppercase block">
                Phương Pháp Tu Tập
              </span>
              <h2 className="font-headline-lg text-xl sm:text-2xl font-bold text-on-surface">
                Hướng Dẫn Hành Trì
              </h2>
            </div>
          </div>

          <ContentBody rawContent={figure.practice_guidance} />
        </section>
      )}
    </div>
  );
}
