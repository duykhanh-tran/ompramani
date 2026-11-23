import React from "react";
import Link from "next/link";
import { TempleSummary } from "@/types/temple";
import { resolveMediaUrl } from "@/lib/utils/media";
import { SafeImage } from "@/components/common/SafeImage";

interface TempleCardProps {
  temple: TempleSummary;
}

export function TempleCard({ temple }: TempleCardProps) {
  const coverUrl = resolveMediaUrl(temple.cover_image);

  return (
    <article className="bg-surface-container-lowest rounded-2xl overflow-hidden border border-outline-variant/40 hover:border-outline-variant/80 hover:shadow-md transition-all flex flex-col group h-full">
      {/* Cover Image or Neutral Spiritual Placeholder */}
      <div className="h-48 w-full relative overflow-hidden bg-surface-container">
        <SafeImage
          src={coverUrl}
          alt={temple.name}
          fill
          sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
          className="object-cover group-hover:scale-105 transition-transform duration-300"
          fallback={
            <div className="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-surface-container to-surface-container-high text-on-surface-variant/40 p-4 select-none">
              <span className="material-symbols-outlined text-[48px] text-primary/30 mb-1">
                temple_buddhist
              </span>
              <span className="font-label-sm text-label-sm tracking-wider uppercase opacity-70">
                {temple.place_type_label || "Tự Viện"}
              </span>
            </div>
          }
        />

        {/* Place Type Badge - sits on top layer */}
        <div className="absolute top-3 left-3 z-10 flex items-center gap-1.5 flex-wrap">
          <span className="px-2.5 py-1 bg-surface-container-lowest/90 backdrop-blur-md rounded-full font-label-sm text-label-sm font-semibold text-primary shadow-xs">
            {temple.place_type_label || "Chùa"}
          </span>
          {temple.has_verified_coordinates && (
            <span
              title="Tọa độ thực địa đã được ban biên tập xác minh"
              className="px-2 py-0.5 bg-emerald-950/80 text-emerald-300 backdrop-blur-md rounded-full font-label-xs text-[11px] flex items-center gap-0.5 shadow-xs"
            >
              <span className="material-symbols-outlined text-[13px]">verified</span>
              Đã kiểm chứng
            </span>
          )}
        </div>
      </div>

      {/* Content */}
      <div className="p-5 flex flex-col justify-between flex-1 space-y-4">
        <div className="space-y-2.5">
          <h3 className="font-headline-sm text-headline-sm font-semibold text-on-surface line-clamp-1 group-hover:text-primary transition-colors">
            <Link href={`/tim-chua/${temple.slug}`} className="hover:underline">
              {temple.name}
            </Link>
          </h3>

          {/* Formatted Address */}
          <div className="flex items-start gap-1.5 text-on-surface-variant font-body-sm text-body-sm">
            <span className="material-symbols-outlined text-[16px] text-primary shrink-0 mt-0.5">
              location_on
            </span>
            <span className="line-clamp-2">
              {temple.formatted_address || temple.province_name || "Việt Nam"}
            </span>
          </div>

          {/* Short Description */}
          {temple.short_description && (
            <p className="font-body-sm text-body-sm text-on-surface-variant/90 line-clamp-2 pt-0.5">
              {temple.short_description}
            </p>
          )}

          {/* Traditions */}
          {temple.traditions && temple.traditions.length > 0 && (
            <div className="flex items-center gap-1.5 flex-wrap pt-1">
              {temple.traditions.map((trad) => (
                <span
                  key={trad.id}
                  className="px-2 py-0.5 bg-surface-container rounded-md font-label-xs text-[11px] text-on-surface-variant font-medium"
                >
                  {trad.name}
                </span>
              ))}
            </div>
          )}
        </div>

        {/* Footer Card CTA */}
        <div className="pt-3 border-t border-outline-variant/30 flex items-center justify-between">
          <span className="font-label-sm text-label-sm text-outline">
            {temple.province_name ? temple.province_name : "Địa điểm công khai"}
          </span>

          <Link
            href={`/tim-chua/${temple.slug}`}
            className="font-label-md text-label-md text-primary font-medium hover:underline inline-flex items-center gap-1 group/btn"
          >
            <span>Chi tiết</span>
            <span className="material-symbols-outlined text-[16px] group-hover/btn:translate-x-0.5 transition-transform">
              arrow_forward
            </span>
          </Link>
        </div>
      </div>
    </article>
  );
}
