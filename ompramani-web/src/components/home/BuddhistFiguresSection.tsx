import React from "react";
import Link from "next/link";
import Image from "next/image";
import { BuddhistFigure } from "@/types/api";
import { resolveMediaUrl } from "@/lib/utils/media";

interface Props {
  figures?: BuddhistFigure[];
}

export function BuddhistFiguresSection({ figures }: Props) {
  // If no figures exist in backend, do not display the section
  if (!figures || figures.length === 0) {
    return null;
  }

  return (
    <div className="pt-2">
      <div className="flex items-center justify-between mb-6">
        <div>
          <span className="font-label-caps text-label-caps text-primary uppercase">
            Tướng Hảo Trang Nghiêm
          </span>
          <h2 className="font-headline-xl text-headline-xl text-on-surface font-semibold tracking-tight">
            Thư Viện Phật &amp; Bồ Tát
          </h2>
        </div>
        <Link
          href="/phat-bo-tat"
          className="font-label-lg text-label-lg text-primary hover:underline flex items-center gap-1"
        >
          <span>Chiêm bái toàn bộ</span>
          <span className="material-symbols-outlined text-[18px]">
            chevron_right
          </span>
        </Link>
      </div>

      <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
        {figures.map((figure: BuddhistFigure) => {
          const rawMedia = figure.thumbnail || figure.image_url;
          const imageUrl = resolveMediaUrl(rawMedia);

          return (
            <Link
              key={figure.id}
              href={`/phat-bo-tat/${figure.slug}`}
              className="bg-surface-container-lowest rounded-2xl overflow-hidden shadow-xs hover:shadow-lg transition-all duration-300 group flex flex-col border border-outline-variant/40 hover:border-primary/50"
            >
              <div className="aspect-[3/4] bg-surface-container-low overflow-hidden relative">
                {imageUrl ? (
                  <Image
                    src={imageUrl}
                    alt={figure.name}
                    fill
                    sizes="(max-width: 768px) 50vw, 25vw"
                    className="object-cover object-top group-hover:scale-105 transition-transform duration-500"
                  />
                ) : (
                  <div className="w-full h-full flex items-center justify-center bg-surface-container text-primary">
                    <span className="material-symbols-outlined text-[48px]">
                      self_improvement
                    </span>
                  </div>
                )}
                <div className="absolute inset-0 bg-gradient-to-t from-black/45 via-transparent to-transparent pointer-events-none opacity-80 group-hover:opacity-60 transition-opacity" />
              </div>

              <div className="p-4 text-center flex-1 flex flex-col justify-between">
                <div>
                  <h3 className="font-headline-md text-base sm:text-lg font-bold text-on-surface group-hover:text-primary transition-colors line-clamp-1">
                    {figure.name}
                  </h3>
                  {figure.original_name && (
                    <p className="font-label-md text-xs text-outline mt-0.5 line-clamp-1 italic">
                      {figure.original_name}
                    </p>
                  )}
                  {figure.short_description && (
                    <p className="font-body-sm text-xs text-on-surface-variant/90 mt-2 line-clamp-2 leading-relaxed">
                      {figure.short_description}
                    </p>
                  )}
                </div>
              </div>
            </Link>
          );
        })}
      </div>
    </div>
  );
}
