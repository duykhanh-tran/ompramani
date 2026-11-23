import React from "react";
import Link from "next/link";
import { BuddhistFigure } from "@/types/api";
import { resolveMediaUrl } from "@/lib/utils/media";
import { getFigureTraditions, getFigureTypeLabel, parseSafeContent } from "@/lib/utils/sacred";
import { SafeImage } from "@/components/common/SafeImage";

interface FigureHeroProps {
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
        className={`w-full space-y-4 leading-relaxed break-words ${
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
      className={`w-full space-y-4 break-words ${
        isEditorial
          ? "font-serif text-[17px] sm:text-[18px] leading-[32px] text-on-surface"
          : "font-body-lg text-[16px] sm:text-[17px] leading-[30px] text-on-surface-variant"
      }`}
    >
      {parsed.paragraphs.map((paragraph, index) => (
        <p key={index} className="whitespace-pre-line leading-relaxed text-justify">
          {paragraph}
        </p>
      ))}
    </div>
  );
}

export function FigureHero({ figure }: FigureHeroProps) {
  const imageUrl = resolveMediaUrl(figure.thumbnail);
  const traditions = getFigureTraditions(figure);
  const figureTypeLabel = getFigureTypeLabel(figure.figure_type);
  const hasBiography = Boolean(figure.biography && figure.biography.trim());
  const hasIconography = Boolean(figure.iconography && figure.iconography.trim());
  const hasPracticeGuidance = Boolean(figure.practice_guidance && figure.practice_guidance.trim());

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
        <Link
          href="/phat-bo-tat"
          className="hover:text-primary transition-colors"
        >
          Phật &amp; Bồ Tát
        </Link>
        <span className="material-symbols-outlined text-[14px]">
          chevron_right
        </span>
        <span className="text-on-surface font-semibold truncate max-w-xs sm:max-w-md">
          {figure.name}
        </span>
      </nav>

      {/* Unified Main Card: Profile Hero + Niệm Phật CTA + Giới Thiệu & Công Hạnh */}
      <div className="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 lg:p-10 border border-outline-variant/30 shadow-sm space-y-8 relative overflow-hidden">
        {/* Subtle decorative background halo */}
        <div className="absolute -right-16 -top-16 w-64 h-64 rounded-full bg-secondary-container/20 blur-3xl pointer-events-none" />

        {/* 1. Profile Hero Section */}
        <div className="flex flex-col md:flex-row gap-8 items-center md:items-start relative z-10">
          {/* Portrait Image / Sacred Emblem */}
          <div className="w-56 sm:w-64 md:w-60 h-72 rounded-2xl overflow-hidden relative shrink-0 bg-surface-container-low border border-outline-variant/40 shadow-sm">
            <SafeImage
              src={imageUrl}
              alt={figure.name}
              fill
              sizes="(max-width: 768px) 256px, 240px"
              className="object-cover"
              priority
              fallback={
                <div className="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-surface-container via-surface-container-high to-secondary-container/40 text-primary p-6 text-center">
                  <div className="w-20 h-20 rounded-full bg-surface-container-lowest/80 border border-outline-variant/40 flex items-center justify-center shadow-xs mb-3">
                    <span className="material-symbols-outlined text-[44px] text-primary">
                      self_improvement
                    </span>
                  </div>
                  <span className="font-headline-md text-sm font-semibold text-on-surface line-clamp-2">
                    {figure.name}
                  </span>
                </div>
              }
            />
          </div>

          {/* Profile Details */}
          <div className="flex-1 space-y-4 min-w-0 text-center md:text-left">
            {/* Type & Tradition Badges */}
            <div className="flex flex-wrap items-center justify-center md:justify-start gap-2">
              <span className="px-3.5 py-1 rounded-full bg-primary/10 text-primary font-label-md text-label-md font-semibold flex items-center gap-1.5">
                <span className="w-1.5 h-1.5 rounded-full bg-primary" />
                {figureTypeLabel}
              </span>

              {traditions.map((t) => (
                <Link
                  key={t.slug}
                  href={`/phat-bo-tat?tradition=${t.slug}`}
                  className="px-3.5 py-1 rounded-full bg-secondary-container text-on-secondary-container hover:bg-primary hover:text-on-primary transition-colors font-label-md text-label-md font-medium"
                >
                  {t.name}
                </Link>
              ))}
            </div>

            {/* Name & Original Name */}
            <div>
              <h1 className="font-headline-xl text-3xl sm:text-4xl lg:text-[40px] font-bold text-on-surface tracking-tight leading-tight">
                {figure.name}
              </h1>
              {figure.original_name && (
                <p className="font-quote-editorial text-primary italic text-lg sm:text-xl mt-1.5">
                  {figure.original_name}
                </p>
              )}
            </div>

            {/* Short Description */}
            {figure.short_description && (
              <p className="font-body-lg text-[16px] sm:text-[17px] text-on-surface-variant max-w-3xl leading-relaxed">
                {figure.short_description}
              </p>
            )}

            {/* Practice CTA Banner (Tích hợp trong khối đầu) */}
            <div className="pt-2">
              <div className="bg-primary/5 border border-primary/20 rounded-2xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div className="flex items-center gap-3 text-left">
                  <div className="w-10 h-10 rounded-xl bg-primary text-on-primary flex items-center justify-center shrink-0">
                    <span className="material-symbols-outlined text-[22px]">self_improvement</span>
                  </div>
                  <div>
                    <h4 className="font-headline-md text-sm font-semibold text-on-surface">
                      Niệm Danh Hiệu {figure.name}
                    </h4>
                    <p className="font-body-sm text-xs text-on-surface-variant">
                      {figure.invocation_text
                        ? `Hành trì câu niệm: "${figure.invocation_text}"`
                        : "Định tâm niệm danh hiệu với bộ đếm công phu và máy niệm."}
                    </p>
                  </div>
                </div>
                <Link
                  href={`/tri-chu?figure=${figure.slug}`}
                  className="px-5 py-2.5 rounded-full bg-primary hover:bg-primary/90 text-on-primary font-label-md text-xs font-semibold transition-all shadow-xs flex items-center gap-2 shrink-0 self-stretch sm:self-auto justify-center cursor-pointer"
                >
                  <span className="material-symbols-outlined text-[16px]">play_arrow</span>
                  <span>Bắt Đầu Trì Chú</span>
                </Link>
              </div>
            </div>
          </div>
        </div>

        {/* 2. Bổn Tích & Công Hạnh (Ghép liền mạch vào cùng một khung thẻ) */}
        {hasBiography && figure.biography && (
          <section
            aria-labelledby="biography-heading"
            className="pt-8 border-t border-outline-variant/30 space-y-5"
          >
            <div className="flex items-center gap-3 pb-3 border-b border-outline-variant/20">
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

        {/* 3. Iconography (Tôn tượng & Biểu trưng nếu có) */}
        {hasIconography && figure.iconography && (
          <section className="pt-8 border-t border-outline-variant/30 space-y-5">
            <div className="flex items-center gap-3 pb-3 border-b border-outline-variant/20">
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

        {/* 4. Practice Guidance (Phương pháp tu tập nếu có) */}
        {hasPracticeGuidance && figure.practice_guidance && (
          <section className="pt-8 border-t border-outline-variant/30 space-y-5">
            <div className="flex items-center gap-3 pb-3 border-b border-outline-variant/20">
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
    </div>
  );
}
