"use client";

import React, { useState, useEffect, useCallback, useMemo } from "react";
import { TempleMediaItem } from "@/types/temple";
import { resolveMediaUrl } from "@/lib/utils/media";
import { SafeImage } from "@/components/common/SafeImage";

interface TempleGalleryProps {
  images?: TempleMediaItem[];
  templeName: string;
}

interface GalleryItem extends TempleMediaItem {
  resolvedUrl: string;
}

export function TempleGallery({ images = [], templeName }: TempleGalleryProps) {
  const [activeIndex, setActiveIndex] = useState<number | null>(null);

  // Pre-resolve and filter valid images with non-empty URLs
  const validImages = useMemo<GalleryItem[]>(() => {
    if (!images || !Array.isArray(images)) return [];
    return images
      .map((img) => {
        const resolved = resolveMediaUrl(img.file_path);
        if (!resolved) return null;
        return {
          ...img,
          resolvedUrl: resolved,
        };
      })
      .filter((img): img is GalleryItem => img !== null);
  }, [images]);

  const isOpen = activeIndex !== null;
  const currentImage = isOpen && validImages[activeIndex] ? validImages[activeIndex] : null;

  const handleClose = useCallback(() => {
    setActiveIndex(null);
  }, []);

  const handlePrev = useCallback(() => {
    if (validImages.length === 0) return;
    setActiveIndex((prev) => (prev === null || prev === 0 ? validImages.length - 1 : prev - 1));
  }, [validImages.length]);

  const handleNext = useCallback(() => {
    if (validImages.length === 0) return;
    setActiveIndex((prev) => (prev === null || prev === validImages.length - 1 ? 0 : prev + 1));
  }, [validImages.length]);

  // Keyboard navigation for Lightbox
  useEffect(() => {
    if (!isOpen) return;

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === "Escape") {
        e.preventDefault();
        handleClose();
      } else if (e.key === "ArrowLeft") {
        e.preventDefault();
        handlePrev();
      } else if (e.key === "ArrowRight") {
        e.preventDefault();
        handleNext();
      }
    };

    window.addEventListener("keydown", handleKeyDown);
    return () => window.removeEventListener("keydown", handleKeyDown);
  }, [isOpen, handleClose, handlePrev, handleNext]);

  if (validImages.length === 0) {
    return null;
  }

  return (
    <section aria-labelledby="gallery-heading" className="space-y-4">
      <div className="flex items-center justify-between gap-2 border-b border-outline-variant/30 pb-3">
        <h2
          id="gallery-heading"
          className="font-headline-sm text-lg sm:text-xl font-serif font-bold text-on-surface flex items-center gap-2"
        >
          <span className="material-symbols-outlined text-primary text-[22px]">
            photo_library
          </span>
          Thư Viện Hình Ảnh
        </h2>

        <span className="text-xs text-on-surface-variant font-label-sm">
          {validImages.length} hình ảnh
        </span>
      </div>

      {/* Grid Thumbnail view */}
      <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 sm:gap-4">
        {validImages.map((img, idx) => (
          <button
            key={img.id || idx}
            type="button"
            onClick={() => setActiveIndex(idx)}
            className="group relative aspect-4/3 rounded-2xl overflow-hidden border border-outline-variant/30 bg-surface-container-low hover:border-primary/50 transition-all focus:outline-hidden focus:ring-2 focus:ring-primary focus:ring-offset-2 cursor-pointer shadow-xs"
            aria-label={`Xem ảnh ${idx + 1}: ${img.caption || img.alt_text || templeName}`}
          >
            <SafeImage
              src={img.resolvedUrl}
              alt={img.alt_text || `Hình ảnh tự viện ${templeName}`}
              fill
              className="object-cover group-hover:scale-105 transition-transform duration-300"
              sizes="(max-width: 640px) 50vw, (max-width: 768px) 33vw, 25vw"
              fallbackIcon="temple_buddhist"
            />

            <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-2.5">
              <span className="text-white text-xs font-label-sm line-clamp-1">
                {img.caption || img.alt_text || "Phóng to"}
              </span>
            </div>

            {img.is_cover && (
              <span className="absolute top-2 left-2 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-black/60 text-white backdrop-blur-xs">
                Ảnh bìa
              </span>
            )}
          </button>
        ))}
      </div>

      {/* Accessible Lightbox Modal */}
      {isOpen && currentImage && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/85 backdrop-blur-sm p-4 animate-in fade-in duration-200"
          role="dialog"
          aria-modal="true"
          aria-label={`Ảnh ${activeIndex + 1} của ${validImages.length} - ${templeName}`}
        >
          {/* Close button */}
          <button
            type="button"
            onClick={handleClose}
            className="absolute top-4 right-4 z-10 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors cursor-pointer focus:outline-hidden focus:ring-2 focus:ring-white"
            aria-label="Đóng thư viện ảnh"
          >
            <span className="material-symbols-outlined text-[24px]">close</span>
          </button>

          {/* Navigation Prev Button */}
          {validImages.length > 1 && (
            <button
              type="button"
              onClick={handlePrev}
              className="absolute left-4 top-1/2 -translate-y-1/2 z-10 w-11 h-11 rounded-full bg-white/10 hover:bg-white/25 text-white flex items-center justify-center transition-colors cursor-pointer focus:outline-hidden focus:ring-2 focus:ring-white"
              aria-label="Xem ảnh trước"
            >
              <span className="material-symbols-outlined text-[28px]">chevron_left</span>
            </button>
          )}

          {/* Image & Caption Container */}
          <div className="relative max-w-4xl max-h-[85vh] w-full flex flex-col items-center justify-center">
            <div className="relative w-full h-[60vh] sm:h-[70vh] rounded-2xl overflow-hidden">
              <SafeImage
                src={currentImage.resolvedUrl}
                alt={currentImage.alt_text || `Ảnh ${templeName}`}
                fill
                priority
                className="object-contain"
                sizes="(max-width: 1024px) 100vw, 1024px"
                fallbackIcon="temple_buddhist"
              />
            </div>

            {/* Caption & Attribution bar */}
            <div className="w-full text-center mt-3 text-white/90 space-y-1 px-4">
              <p className="font-body-md text-sm sm:text-base font-medium">
                {currentImage.caption || currentImage.alt_text || templeName}
              </p>

              <div className="flex items-center justify-center gap-3 text-xs text-white/70 flex-wrap">
                <span>
                  {activeIndex + 1} / {validImages.length}
                </span>

                {currentImage.credit && (
                  <span>Nhiếp ảnh / Nguồn: {currentImage.credit}</span>
                )}

                {currentImage.license_info && (
                  <span className="px-1.5 py-0.2 bg-white/10 rounded text-[10px]">
                    {currentImage.license_info}
                  </span>
                )}
              </div>
            </div>
          </div>

          {/* Navigation Next Button */}
          {validImages.length > 1 && (
            <button
              type="button"
              onClick={handleNext}
              className="absolute right-4 top-1/2 -translate-y-1/2 z-10 w-11 h-11 rounded-full bg-white/10 hover:bg-white/25 text-white flex items-center justify-center transition-colors cursor-pointer focus:outline-hidden focus:ring-2 focus:ring-white"
              aria-label="Xem ảnh kế tiếp"
            >
              <span className="material-symbols-outlined text-[28px]">chevron_right</span>
            </button>
          )}
        </div>
      )}
    </section>
  );
}
