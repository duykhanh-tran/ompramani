import React from "react";
import Link from "next/link";
import Image from "next/image";
import { Mantra } from "@/types/api";
import { resolveMediaUrl } from "@/lib/utils/media";

interface MantraCardProps {
  mantra: Mantra;
}

export function MantraCard({ mantra }: MantraCardProps) {
  const figure = mantra.figure || mantra.buddhist_figure;
  const rawImage = mantra.thumbnail || figure?.thumbnail || mantra.image_url;
  const imageUrl = resolveMediaUrl(rawImage);

  return (
    <Link
      href={`/chu/${mantra.slug}`}
      className="bg-surface-container-lowest rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-all group flex flex-col border border-outline-variant/30"
    >
      {/* Thumbnail or Sacred Fallback */}
      <div className="h-48 bg-surface-container-low overflow-hidden relative">
        {imageUrl ? (
          <Image
            src={imageUrl}
            alt={mantra.title}
            fill
            sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
            className="object-cover group-hover:scale-105 transition-transform duration-500"
          />
        ) : (
          <div className="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-surface-container via-surface-container-high to-secondary-container/40 text-primary p-6">
            <div className="w-14 h-14 rounded-full bg-surface-container-lowest/80 border border-outline-variant/40 flex items-center justify-center shadow-xs mb-2.5">
              <span className="material-symbols-outlined text-[32px] text-primary">
                auto_awesome
              </span>
            </div>
            <span className="font-headline-md text-sm text-center text-on-surface line-clamp-1 font-medium">
              {mantra.title}
            </span>
          </div>
        )}

        {/* Badges: Tradition & Related Figure */}
        <div className="absolute top-3 left-3 right-3 flex flex-wrap items-center justify-between gap-1.5 pointer-events-none">
          {mantra.tradition && (
            <span className="px-3 py-1 rounded-full bg-surface/90 backdrop-blur-md text-on-surface font-label-md text-[11px] font-medium shadow-xs">
              {mantra.tradition.name}
            </span>
          )}

          {figure && (
            <span className="px-2.5 py-1 rounded-full bg-secondary-container/95 backdrop-blur-md text-on-secondary-container font-label-md text-[11px] font-semibold shadow-xs flex items-center gap-1">
              <span className="material-symbols-outlined text-[13px]">
                self_improvement
              </span>
              <span className="truncate max-w-[140px]">
                {figure.name}
              </span>
            </span>
          )}
        </div>
      </div>

      {/* Card Content */}
      <div className="p-5 flex flex-col justify-between flex-1">
        <div>
          <h3 className="font-headline-md text-headline-md font-semibold text-on-surface group-hover:text-primary transition-colors">
            {mantra.title}
          </h3>
          {mantra.original_title && (
            <p className="font-quote-editorial text-[14px] text-outline italic mt-0.5 line-clamp-1">
              {mantra.original_title}
            </p>
          )}
          {mantra.short_description && (
            <p className="font-body-sm text-body-sm text-on-surface-variant mt-2.5 line-clamp-3">
              {mantra.short_description}
            </p>
          )}
        </div>

        <div className="mt-5 pt-3 border-t border-outline-variant/30 flex items-center justify-between font-label-md text-label-md">
          <span className="text-outline">
            {mantra.figure ? mantra.figure.name : "Đà La Ni · Mật Chú"}
          </span>
          <span className="text-primary font-medium group-hover:underline flex items-center gap-0.5 shrink-0">
            Đọc &amp; trì tụng
            <span className="material-symbols-outlined text-[16px]">
              arrow_forward
            </span>
          </span>
        </div>
      </div>
    </Link>
  );
}
