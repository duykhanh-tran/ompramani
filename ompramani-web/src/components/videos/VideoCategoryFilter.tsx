"use client";

import React from "react";
import { VideoCategory, VideoCategoryItem } from "@/types/video";

interface VideoCategoryFilterProps {
  categories: VideoCategoryItem[];
  selectedCategory?: VideoCategory;
  onSelectCategory: (category?: VideoCategory) => void;
}

export function VideoCategoryFilter({
  categories,
  selectedCategory,
  onSelectCategory,
}: VideoCategoryFilterProps) {
  return (
    <div
      role="tablist"
      aria-label="Phân loại video Phật pháp"
      className="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none"
    >
      <button
        type="button"
        role="tab"
        aria-selected={!selectedCategory}
        onClick={() => onSelectCategory(undefined)}
        className={`px-4 py-2 rounded-full text-xs sm:text-sm font-label-md font-medium shrink-0 transition-colors cursor-pointer border ${
          !selectedCategory
            ? "bg-primary text-on-primary border-primary shadow-xs"
            : "bg-surface-container-lowest text-on-surface-variant border-outline-variant/50 hover:bg-surface-container-low hover:text-on-surface"
        }`}
      >
        Tất cả chuyên mục
      </button>

      {categories.map((cat) => {
        const isSelected = selectedCategory === cat.key;
        return (
          <button
            key={cat.key}
            type="button"
            role="tab"
            aria-selected={isSelected}
            onClick={() => onSelectCategory(isSelected ? undefined : cat.key)}
            className={`px-4 py-2 rounded-full text-xs sm:text-sm font-label-md font-medium shrink-0 transition-colors cursor-pointer border ${
              isSelected
                ? "bg-primary text-on-primary border-primary shadow-xs"
                : "bg-surface-container-lowest text-on-surface-variant border-outline-variant/50 hover:bg-surface-container-low hover:text-on-surface"
            }`}
          >
            {cat.label}
          </button>
        );
      })}
    </div>
  );
}
