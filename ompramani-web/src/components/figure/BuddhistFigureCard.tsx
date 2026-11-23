import React from "react";
import Link from "next/link";
import { BuddhistFigure } from "@/types/api";
import { resolveMediaUrl } from "@/lib/utils/media";
import { getFigureTraditions, getFigureTypeLabel } from "@/lib/utils/sacred";
import { SafeImage } from "@/components/common/SafeImage";

interface BuddhistFigureCardProps {
  figure: BuddhistFigure;
}

export function BuddhistFigureCard({ figure }: BuddhistFigureCardProps) {
  const imageUrl = resolveMediaUrl(figure.thumbnail);
  const traditions = getFigureTraditions(figure);
  const figureTypeLabel = getFigureTypeLabel(figure.figure_type);
  const previewText =
    figure.short_description ||
    (figure.biography
      ? figure.biography.replace(/<[^>]*>/g, " ").replace(/\s+/g, " ").trim()
      : "");

  return (
    <Link
      href={`/phat-bo-tat/${figure.slug}`}
      className="bg-surface-container-lowest rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-all group flex flex-col border border-outline-variant/30"
    >
      {/* Portrait Thumbnail or Sacred Fallback */}
      <div className="h-64 sm:h-72 bg-[#F8F5F0] overflow-hidden relative flex items-center justify-center border-b border-outline-variant/20 pt-8 pb-3 px-3">
        {imageUrl ? (
          <>
            {/* Ambient blur backdrop matching colors of the painting */}
            <SafeImage
              src={imageUrl}
              alt=""
              fill
              className="object-cover blur-xl opacity-30 scale-110 pointer-events-none"
              aria-hidden="true"
              fallback={<div className="hidden" />}
            />
            {/* Full uncropped artwork - sits below the badges (z-0) */}
            <div className="relative w-full h-full z-0">
              <SafeImage
                src={imageUrl}
                alt={figure.name}
                fill
                sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 25vw"
                className="object-contain group-hover:scale-[1.03] transition-transform duration-500 drop-shadow-sm"
                fallback={
                  <div className="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-surface-container via-surface-container-high to-secondary-container/40 text-primary p-6">
                    <div className="w-16 h-16 rounded-full bg-surface-container-lowest/70 border border-outline-variant/40 flex items-center justify-center shadow-xs mb-3">
                      <span className="material-symbols-outlined text-[36px] text-primary">
                        self_improvement
                      </span>
                    </div>
                    <span className="font-headline-md text-sm text-center text-on-surface line-clamp-1 font-medium">
                      {figure.name}
                    </span>
                  </div>
                }
              />
            </div>
          </>
        ) : (
          <div className="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-surface-container via-surface-container-high to-secondary-container/40 text-primary p-6">
            <div className="w-16 h-16 rounded-full bg-surface-container-lowest/70 border border-outline-variant/40 flex items-center justify-center shadow-xs mb-3">
              <span className="material-symbols-outlined text-[36px] text-primary">
                self_improvement
              </span>
            </div>
            <span className="font-headline-md text-sm text-center text-on-surface line-clamp-1 font-medium">
              {figure.name}
            </span>
          </div>
        )}

        {/* Top Badges: Figure Type & Traditions - sits above the image (z-10) */}
        <div className="absolute top-3 left-3 right-3 z-10 flex flex-wrap items-center justify-between gap-1.5 pointer-events-none">
          <span className="px-3 py-1 rounded-full bg-surface/90 backdrop-blur-md text-primary font-label-md text-[11px] font-semibold shadow-xs">
            {figureTypeLabel}
          </span>

          {traditions.length > 0 && (
            <div className="flex flex-wrap items-center gap-1">
              {traditions.map((t) => (
                <span
                  key={t.slug}
                  className="px-2.5 py-0.5 rounded-full bg-secondary-container/90 backdrop-blur-md text-on-secondary-container font-label-md text-[11px] font-medium shadow-xs"
                >
                  {t.name}
                </span>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Card Body */}
      <div className="p-5 flex flex-col justify-between flex-1">
        <div>
          <h3 className="font-headline-md text-headline-md font-semibold text-on-surface group-hover:text-primary transition-colors">
            {figure.name}
          </h3>
          {figure.original_name && (
            <p className="font-quote-editorial text-[14px] text-outline italic mt-0.5 line-clamp-1">
              {figure.original_name}
            </p>
          )}
          {previewText && (
            <p className="font-body-sm text-body-sm text-on-surface-variant mt-2.5 line-clamp-3">
              {previewText}
            </p>
          )}
        </div>

        <div className="mt-5 pt-3 border-t border-outline-variant/30 flex items-center justify-between font-label-md text-label-md">
          <span className="text-outline">
            {figureTypeLabel}
          </span>
          <span className="text-primary font-medium group-hover:underline flex items-center gap-0.5">
            Chiêm bái hạnh nguyện
            <span className="material-symbols-outlined text-[16px]">
              arrow_forward
            </span>
          </span>
        </div>
      </div>
    </Link>
  );
}
