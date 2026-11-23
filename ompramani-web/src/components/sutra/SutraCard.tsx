"use client";

import React from "react";
import Link from "next/link";
import { Sutra } from "@/types/api";
import { resolveMediaUrl } from "@/lib/utils/media";
import { FavoriteButton } from "@/components/favorites/FavoriteButton";
import { SafeImage } from "@/components/common/SafeImage";

interface SutraCardProps {
  sutra: Sutra;
}

export function SutraCard({ sutra }: SutraCardProps) {
  const imageUrl = resolveMediaUrl(sutra.thumbnail);

  return (
    <Link
      href={`/kinh/${sutra.slug}`}
      className="bg-surface-container-lowest rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-all group flex flex-col border border-outline-variant/30"
    >
      {/* Thumbnail or Fallback */}
      <div className="h-60 sm:h-64 bg-[#F8F5F0] overflow-hidden relative flex items-center justify-center border-b border-outline-variant/20 pt-8 pb-3 px-3">
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
            {/* Full uncropped artwork - sits below the badges */}
            <div className="relative w-full h-full z-0">
              <SafeImage
                src={imageUrl}
                alt={sutra.title}
                fill
                sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
                className="object-contain group-hover:scale-[1.03] transition-transform duration-500 drop-shadow-sm"
                fallback={
                  <div className="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-surface-container to-surface-container-high text-primary p-6">
                    <span className="material-symbols-outlined text-[44px] mb-2 opacity-80">
                      menu_book
                    </span>
                    <span className="font-headline-md text-sm text-center text-on-surface line-clamp-1">
                      {sutra.title}
                    </span>
                  </div>
                }
              />
            </div>
          </>
        ) : (
          <div className="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-surface-container to-surface-container-high text-primary p-6">
            <span className="material-symbols-outlined text-[44px] mb-2 opacity-80">
              menu_book
            </span>
            <span className="font-headline-md text-sm text-center text-on-surface line-clamp-1">
              {sutra.title}
            </span>
          </div>
        )}

        {/* Tradition Badge */}
        {sutra.tradition && (
          <span className="absolute top-3 left-3 z-10 px-3 py-1 rounded-full bg-surface/90 backdrop-blur-md text-on-surface font-label-md text-label-md font-medium shadow-xs">
            {sutra.tradition.name}
          </span>
        )}

        {/* Favorite Button on top right */}
        <div
          className="absolute top-3 right-3 z-10"
          onClick={(e) => {
            e.preventDefault();
            e.stopPropagation();
          }}
        >
          <FavoriteButton
            contentType="sutra"
            contentPublicId={sutra.slug}
            variant="compact"
          />
        </div>
      </div>

      {/* Card Content */}
      <div className="p-5 flex flex-col justify-between flex-1">
        <div>
          <h3 className="font-headline-md text-headline-md font-semibold text-on-surface group-hover:text-primary transition-colors">
            {sutra.title}
          </h3>
          {sutra.original_title && (
            <p className="font-label-md text-label-md text-outline italic mt-0.5">
              {sutra.original_title}
            </p>
          )}
          {sutra.short_description && (
            <p className="font-body-sm text-body-sm text-on-surface-variant mt-2 line-clamp-3">
              {sutra.short_description}
            </p>
          )}
        </div>

        <div className="mt-5 pt-3 border-t border-outline-variant/30 flex items-center justify-between font-label-md text-label-md">
          <span className="text-outline">
            {sutra.translator
              ? `Dịch giả: ${sutra.translator}`
              : "Kinh điển"}
          </span>
          <span className="text-primary font-medium group-hover:underline flex items-center gap-0.5">
            Đọc kinh văn
            <span className="material-symbols-outlined text-[16px]">
              arrow_forward
            </span>
          </span>
        </div>
      </div>
    </Link>
  );
}
