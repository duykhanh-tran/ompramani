"use client";

import React, { useState } from "react";
import Image from "next/image";

interface ProductGalleryProps {
  images?: string[];
  productName: string;
}

export function ProductGallery({ images = [], productName }: ProductGalleryProps) {
  const [selectedIndex, setSelectedIndex] = useState(0);

  const validImages = images.filter((img) => typeof img === "string" && img.trim().length > 0);
  const activeImage = validImages[selectedIndex] || validImages[0] || null;

  return (
    <div className="flex flex-col gap-3 w-full">
      {/* Main Image Stage */}
      <div className="relative aspect-square w-full rounded-3xl overflow-hidden bg-surface-container-low border border-outline-variant/40 shadow-xs">
        {activeImage ? (
          <Image
            src={activeImage}
            alt={`Ảnh chi tiết: ${productName}`}
            fill
            priority
            className="object-cover transition-opacity duration-200"
            sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 40vw"
          />
        ) : (
          <div className="w-full h-full flex flex-col items-center justify-center bg-radial from-surface-container-high/40 to-surface-container-low text-primary/40 p-8">
            <span className="material-symbols-outlined text-[64px]">
              self_improvement
            </span>
            <span className="text-xs font-label-md text-on-surface-variant/70 italic mt-2 text-center">
              Hình ảnh đang được cập nhật
            </span>
          </div>
        )}
      </div>

      {/* Thumbnails row if multiple images exist */}
      {validImages.length > 1 && (
        <div
          role="tablist"
          aria-label="Hình ảnh vật phẩm"
          className="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none"
        >
          {validImages.map((img, idx) => {
            const isSelected = selectedIndex === idx;
            return (
              <button
                key={`thumb-${idx}`}
                type="button"
                role="tab"
                aria-selected={isSelected}
                aria-label={`Xem ảnh ${idx + 1} của ${productName}`}
                onClick={() => setSelectedIndex(idx)}
                className={`relative w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden border-2 transition-all shrink-0 cursor-pointer focus:outline-hidden focus:ring-2 focus:ring-primary ${
                  isSelected
                    ? "border-primary shadow-xs ring-2 ring-primary/20 scale-102"
                    : "border-outline-variant/40 opacity-70 hover:opacity-100 hover:border-primary/50"
                }`}
              >
                <Image
                  src={img}
                  alt={`Thu nhỏ ảnh ${idx + 1}`}
                  fill
                  className="object-cover"
                  sizes="80px"
                />
              </button>
            );
          })}
        </div>
      )}
    </div>
  );
}
