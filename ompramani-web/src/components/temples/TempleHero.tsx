import React from "react";
import { TempleDetail } from "@/types/temple";
import { resolveMediaUrl } from "@/lib/utils/media";
import { TempleShareAction } from "./TempleShareAction";
import { SafeImage } from "@/components/common/SafeImage";

interface TempleHeroProps {
  temple: TempleDetail;
}

export function TempleHero({ temple }: TempleHeroProps) {
  const altNames = Array.isArray(temple.alternative_names)
    ? temple.alternative_names
        .map((a) => (typeof a === "string" ? a : a?.name))
        .filter(Boolean)
    : [];

  const coverUrl = resolveMediaUrl(temple.cover_image);

  return (
    <section aria-labelledby="temple-name" className="w-full">
      {/* Full-width Edge-to-Edge Hero Banner Image */}
      <div className="w-full h-[45vh] sm:h-[55vh] md:h-[65vh] lg:h-[72vh] min-h-[380px] max-h-[760px] relative overflow-hidden bg-surface-container-low shadow-sm">
        <SafeImage
          src={coverUrl}
          alt={`Ảnh quang cảnh ${temple.name}`}
          fill
          priority
          className="object-cover object-center"
          sizes="100vw"
          fallback={
            <div className="w-full h-full flex flex-col items-center justify-center text-primary/40 bg-radial from-surface-container-high/40 to-surface-container-low space-y-2 p-6 text-center">
              <span className="material-symbols-outlined text-[64px]">
                temple_buddhist
              </span>
              <span className="font-body-xs text-xs text-on-surface-variant/60 italic">
                Chưa có hình ảnh quang cảnh được xác minh
              </span>
            </div>
          }
        />
      </div>

      {/* Title, Badges & Introduction Container */}
      <div className="max-w-[1240px] mx-auto px-4 sm:px-6 pt-8 space-y-4">
        <div className="flex items-start justify-between gap-4 flex-wrap">
          <div className="space-y-3 flex-1 min-w-0">
            {/* Badges */}
            <div className="flex items-center gap-2 flex-wrap">
              <span className="px-3 py-1 bg-primary/10 text-primary rounded-full font-label-md text-xs font-semibold">
                {temple.place_type_label || "Chùa"}
              </span>

              {temple.public_access_status_label && (
                <span className="px-3 py-1 bg-surface-container-high text-on-surface-variant rounded-full font-label-sm text-xs font-medium">
                  {temple.public_access_status_label}
                </span>
              )}

              {temple.coordinates && (
                <span className="px-3 py-1 bg-emerald-950/80 text-emerald-300 rounded-full font-label-sm text-xs font-medium flex items-center gap-1">
                  <span className="material-symbols-outlined text-[14px]">
                    verified
                  </span>
                  Tọa độ đã xác minh
                </span>
              )}
            </div>

            {/* Title */}
            <h1
              id="temple-name"
              className="font-headline-xl text-2xl sm:text-3xl md:text-4xl font-serif font-bold text-on-surface tracking-tight leading-tight"
            >
              {temple.name}
            </h1>

            {altNames.length > 0 && (
              <p className="font-body-md text-sm sm:text-base text-on-surface-variant italic">
                Còn được gọi là: {altNames.join(", ")}
              </p>
            )}

            {temple.traditions && temple.traditions.length > 0 && (
              <div className="flex items-center gap-2 flex-wrap pt-1">
                <span className="font-label-sm text-xs text-on-surface-variant">
                  Truyền thống / Hệ phái:
                </span>
                {temple.traditions.map((tr) => (
                  <span
                    key={tr.id}
                    className="px-2.5 py-0.5 rounded-md text-xs font-medium bg-amber-950/10 text-amber-900 dark:bg-amber-900/30 dark:text-amber-200 border border-amber-800/20"
                  >
                    {tr.name}
                  </span>
                ))}
              </div>
            )}
          </div>

          {/* Share Action Button */}
          <div className="shrink-0 pt-1">
            <TempleShareAction slug={temple.slug} name={temple.name} />
          </div>
        </div>

        {temple.short_description && (
          <p className="font-body-md text-base text-on-surface/90 pt-3 leading-relaxed border-t border-outline-variant/30">
            {temple.short_description}
          </p>
        )}
      </div>
    </section>
  );
}
