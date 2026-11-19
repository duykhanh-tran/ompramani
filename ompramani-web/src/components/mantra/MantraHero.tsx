import React from "react";
import Link from "next/link";
import Image from "next/image";
import { Mantra } from "@/types/api";
import { resolveMediaUrl } from "@/lib/utils/media";

interface MantraHeroProps {
  mantra: Mantra;
}

export function MantraHero({ mantra }: MantraHeroProps) {
  const figure = mantra.figure || mantra.buddhist_figure;
  const rawImage = mantra.thumbnail || figure?.thumbnail || mantra.image_url;
  const imageUrl = resolveMediaUrl(rawImage);

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
        <Link href="/chu" className="hover:text-primary transition-colors">
          Thư Viện Chú
        </Link>
        <span className="material-symbols-outlined text-[14px]">
          chevron_right
        </span>
        <span className="text-on-surface font-semibold truncate max-w-xs sm:max-w-md">
          {mantra.title}
        </span>
      </nav>

      {/* Main Mantra Hero Info */}
      <div className="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-outline-variant/30 shadow-sm flex flex-col md:flex-row gap-6 items-start relative overflow-hidden">
        <div className="absolute -right-12 -top-12 w-56 h-56 rounded-full bg-secondary-container/20 blur-3xl pointer-events-none" />

        {/* Mantra Thumbnail / Sacred Emblem */}
        <div className="w-full md:w-48 h-48 rounded-2xl overflow-hidden relative shrink-0 bg-surface-container-low border border-outline-variant/30">
          {imageUrl ? (
            <Image
              src={imageUrl}
              alt={mantra.title}
              fill
              sizes="(max-width: 768px) 100vw, 192px"
              className="object-cover"
              priority
            />
          ) : (
            <div className="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-surface-container via-surface-container-high to-secondary-container/40 text-primary p-4 text-center">
              <div className="w-16 h-16 rounded-full bg-surface-container-lowest/80 border border-outline-variant/40 flex items-center justify-center shadow-xs mb-2.5">
                <span className="material-symbols-outlined text-[36px] text-primary">
                  auto_awesome
                </span>
              </div>
              <span className="font-headline-md text-xs font-semibold text-on-surface line-clamp-2">
                {mantra.title}
              </span>
            </div>
          )}
        </div>

        {/* Info Column */}
        <div className="flex-1 space-y-4 min-w-0 z-10">
          <div className="flex flex-wrap items-center gap-2">
            {mantra.tradition && (
              <Link
                href={`/chu?tradition=${mantra.tradition.slug}`}
                className="px-3 py-1 rounded-full bg-surface-container text-primary hover:bg-primary hover:text-on-primary transition-colors font-label-md text-label-md font-semibold"
              >
                {mantra.tradition.name}
              </Link>
            )}

            {figure && (
              <Link
                href={`/phat-bo-tat/${figure.slug}`}
                className="px-3.5 py-1 rounded-full bg-secondary-container text-on-secondary-container hover:bg-primary hover:text-on-primary transition-colors font-label-md text-label-md font-semibold flex items-center gap-1.5"
              >
                <span className="material-symbols-outlined text-[15px]">
                  self_improvement
                </span>
                <span>Bổn tôn: {figure.name}</span>
              </Link>
            )}
          </div>

          <div>
            <h1 className="font-headline-xl text-2xl sm:text-3xl lg:text-4xl font-bold text-on-surface tracking-tight">
              {mantra.title}
            </h1>
            {mantra.original_title && (
              <p className="font-quote-editorial text-primary italic text-base sm:text-lg mt-1">
                {mantra.original_title}
              </p>
            )}
          </div>

          {mantra.short_description && (
            <p className="font-body-md text-body-md text-on-surface-variant max-w-3xl leading-relaxed">
              {mantra.short_description}
            </p>
          )}

          {/* Practice Presentation CTA (scrolls cleanly to reader without fake tracking) */}
          <div className="pt-1 flex flex-wrap items-center gap-3">
            <a
              href="#mantra-reader"
              className="px-5 py-2.5 rounded-full bg-primary text-on-primary hover:bg-on-primary-container transition-all font-label-lg text-label-lg font-semibold inline-flex items-center gap-2 shadow-xs"
            >
              <span className="material-symbols-outlined text-[18px]">
                menu_book
              </span>
              <span>Bắt đầu trì chú</span>
            </a>

            {figure && (
              <Link
                href={`/phat-bo-tat/${figure.slug}`}
                className="px-4 py-2.5 rounded-full bg-surface-container-low hover:bg-surface-container text-on-surface hover:text-primary transition-all font-label-md text-label-md font-medium border border-outline-variant/40 inline-flex items-center gap-1.5"
              >
                <span>Xem hạnh nguyện {figure.name}</span>
                <span className="material-symbols-outlined text-[16px]">
                  arrow_forward
                </span>
              </Link>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
