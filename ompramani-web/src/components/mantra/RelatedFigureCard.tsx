import React from "react";
import Link from "next/link";
import Image from "next/image";
import { BuddhistFigure } from "@/types/api";
import { resolveMediaUrl } from "@/lib/utils/media";
import { getFigureTypeLabel } from "@/lib/utils/sacred";

interface RelatedFigureCardProps {
  figure?: BuddhistFigure | null;
}

export function RelatedFigureCard({ figure }: RelatedFigureCardProps) {
  if (!figure) {
    return null;
  }

  const imageUrl = resolveMediaUrl(figure.thumbnail);
  const typeLabel = getFigureTypeLabel(figure.figure_type);

  return (
    <section
      aria-label="Liên hệ Phật và Bồ Tát"
      className="bg-gradient-to-br from-surface-container-lowest via-surface-container-low/60 to-secondary-container/25 rounded-3xl p-6 sm:p-8 border border-outline-variant/40 shadow-sm"
    >
      <div className="flex flex-col sm:flex-row items-center gap-6">
        {/* Figure Avatar / Portrait */}
        <div className="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl overflow-hidden relative shrink-0 bg-surface-container border border-outline-variant/40 shadow-xs">
          {imageUrl ? (
            <Image
              src={imageUrl}
              alt={figure.name}
              fill
              sizes="112px"
              className="object-cover"
            />
          ) : (
            <div className="w-full h-full flex items-center justify-center text-primary bg-surface-container-high">
              <span className="material-symbols-outlined text-[36px]">
                self_improvement
              </span>
            </div>
          )}
        </div>

        {/* Figure Details */}
        <div className="flex-1 min-w-0 text-center sm:text-left space-y-1.5">
          <div className="flex flex-wrap items-center justify-center sm:justify-start gap-2">
            <span className="font-label-caps text-label-caps text-primary uppercase">
              Liên Hệ Phật · Bồ Tát
            </span>
            <span className="px-2.5 py-0.5 rounded-full bg-surface-container text-on-surface-variant font-label-md text-[11px]">
              {typeLabel}
            </span>
          </div>

          <h2 className="font-headline-lg text-xl sm:text-2xl font-bold text-on-surface">
            {figure.name}
          </h2>

          {figure.original_name && (
            <p className="font-quote-editorial text-sm text-outline italic">
              {figure.original_name}
            </p>
          )}

          {figure.short_description && (
            <p className="font-body-sm text-body-sm text-on-surface-variant line-clamp-2 max-w-2xl">
              {figure.short_description}
            </p>
          )}
        </div>

        {/* Action Link */}
        <div className="shrink-0">
          <Link
            href={`/phat-bo-tat/${figure.slug}`}
            className="px-5 py-2.5 rounded-full bg-primary text-on-primary hover:bg-on-primary-container transition-colors font-label-md text-label-md font-semibold inline-flex items-center gap-1.5 shadow-xs"
          >
            <span>Xem hạnh nguyện</span>
            <span className="material-symbols-outlined text-[16px]">
              arrow_forward
            </span>
          </Link>
        </div>
      </div>
    </section>
  );
}
