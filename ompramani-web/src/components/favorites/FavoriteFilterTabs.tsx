"use client";

import React from "react";
import { FavoriteContentType } from "@/types/favorite";

interface FavoriteFilterTabsProps {
  currentType: FavoriteContentType;
  onSelectType: (type: FavoriteContentType) => void;
  disabled?: boolean;
}

const TABS: Array<{
  type: FavoriteContentType;
  label: string;
  icon: string;
}> = [
  { type: "all", label: "Tất cả", icon: "bookmarks" },
  { type: "sutra", label: "Kinh Phật", icon: "auto_stories" },
  { type: "article", label: "Bài viết", icon: "menu_book" },
  { type: "video", label: "Video", icon: "play_circle" },
];

export function FavoriteFilterTabs({
  currentType,
  onSelectType,
  disabled = false,
}: FavoriteFilterTabsProps) {
  return (
    <nav
      aria-label="Bộ lọc nội dung yêu thích"
      className="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none"
    >
      {TABS.map((tab) => {
        const isActive = currentType === tab.type;
        return (
          <button
            key={tab.type}
            type="button"
            disabled={disabled}
            onClick={() => onSelectType(tab.type)}
            className={`inline-flex items-center gap-2 px-4 py-2 rounded-2xl font-label-md text-sm font-medium transition-all shrink-0 cursor-pointer ${
              isActive
                ? "bg-primary text-on-primary shadow-xs"
                : "bg-surface-container-low hover:bg-surface-container text-on-surface-variant hover:text-on-surface border border-outline-variant/30"
            }`}
          >
            <span className="material-symbols-outlined text-[18px]">
              {tab.icon}
            </span>
            <span>{tab.label}</span>
          </button>
        );
      })}
    </nav>
  );
}
