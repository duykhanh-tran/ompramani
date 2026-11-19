"use client";

import React from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { Tradition } from "@/types/api";

interface SearchFiltersProps {
  traditions: Tradition[];
  currentType?: string;
  currentTradition?: string;
  currentSort?: string;
}

const SEARCH_TYPE_TABS = [
  { key: "all", label: "Tất cả", icon: "auto_stories" },
  { key: "sutra", label: "Kinh Điển", icon: "menu_book" },
  { key: "mantra", label: "Thần Chú", icon: "graphic_eq" },
  { key: "figure", label: "Phật · Bồ Tát", icon: "self_improvement" },
  { key: "article", label: "Bài Viết", icon: "article" },
  { key: "feature", label: "Pháp Khí · Tiện Ích", icon: "spa" },
];

export function SearchFilters({
  traditions,
  currentType = "all",
  currentTradition,
  currentSort = "relevance",
}: SearchFiltersProps) {
  const searchParams = useSearchParams();

  const createFilterUrl = (updates: {
    type?: string;
    tradition?: string;
    sort?: string;
  }) => {
    const params = new URLSearchParams(searchParams.toString());

    if (updates.type !== undefined) {
      if (updates.type && updates.type !== "all") {
        params.set("type", updates.type);
      } else {
        params.delete("type");
      }
    }

    if (updates.tradition !== undefined) {
      if (updates.tradition) {
        params.set("tradition", updates.tradition);
      } else {
        params.delete("tradition");
      }
    }

    if (updates.sort !== undefined) {
      if (updates.sort && updates.sort !== "relevance") {
        params.set("sort", updates.sort);
      } else {
        params.delete("sort");
      }
    }

    params.delete("page"); // Reset page on filter changes
    const str = params.toString();
    return str ? `/tim-kiem?${str}` : "/tim-kiem";
  };

  const showTraditionFilter =
    traditions.length > 0 &&
    (currentType === "all" ||
      currentType === "sutra" ||
      currentType === "figure" ||
      currentType === "mantra");

  return (
    <div className="space-y-4">
      {/* Row 1: Type Tabs */}
      <div
        className="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none"
        role="tablist"
        aria-label="Lọc theo loại nội dung"
      >
        {SEARCH_TYPE_TABS.map((tab) => {
          const isActive = (currentType || "all") === tab.key;
          return (
            <Link
              key={tab.key}
              href={createFilterUrl({ type: tab.key })}
              role="tab"
              aria-selected={isActive}
              className={`shrink-0 inline-flex items-center gap-1.5 px-4 py-2 rounded-full font-label-md text-xs sm:text-sm transition-all ${
                isActive
                  ? "bg-primary text-on-primary font-semibold shadow-xs"
                  : "bg-surface-container-low text-on-surface hover:bg-surface-container hover:text-primary border border-outline-variant/40"
              }`}
            >
              <span className="material-symbols-outlined text-[18px]">
                {tab.icon}
              </span>
              <span>{tab.label}</span>
            </Link>
          );
        })}
      </div>

      {/* Row 2: Secondary Filters (Traditions & Sort) */}
      <div className="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-outline-variant/30">
        {/* Tradition Pills */}
        {showTraditionFilter ? (
          <div className="flex flex-wrap items-center gap-1.5 text-xs">
            <span className="text-outline font-label-md mr-1 flex items-center gap-1">
              <span className="material-symbols-outlined text-[16px]">
                account_tree
              </span>
              Hệ phái:
            </span>

            <Link
              href={createFilterUrl({ tradition: "" })}
              className={`px-3 py-1 rounded-full font-label-sm transition-colors ${
                !currentTradition
                  ? "bg-secondary-container text-on-secondary-container font-semibold"
                  : "bg-surface-container-lowest text-on-surface-variant hover:text-primary border border-outline-variant/30"
              }`}
            >
              Tất cả hệ phái
            </Link>

            {traditions.map((tradition) => {
              const isActive = currentTradition === tradition.slug;
              return (
                <Link
                  key={tradition.id}
                  href={createFilterUrl({ tradition: tradition.slug })}
                  className={`px-3 py-1 rounded-full font-label-sm transition-colors ${
                    isActive
                      ? "bg-secondary-container text-on-secondary-container font-semibold"
                      : "bg-surface-container-lowest text-on-surface-variant hover:text-primary border border-outline-variant/30"
                  }`}
                >
                  {tradition.name}
                </Link>
              );
            })}
          </div>
        ) : (
          <div />
        )}

        {/* Sort Options */}
        <div className="flex items-center gap-1 text-xs">
          <span className="text-outline font-label-md mr-1">Sắp xếp:</span>
          <Link
            href={createFilterUrl({ sort: "relevance" })}
            className={`px-2.5 py-1 rounded-md transition-colors ${
              currentSort === "relevance"
                ? "bg-surface-container text-primary font-medium"
                : "text-outline hover:text-on-surface"
            }`}
          >
            Liên quan nhất
          </Link>
          <span className="text-outline/40">·</span>
          <Link
            href={createFilterUrl({ sort: "newest" })}
            className={`px-2.5 py-1 rounded-md transition-colors ${
              currentSort === "newest"
                ? "bg-surface-container text-primary font-medium"
                : "text-outline hover:text-on-surface"
            }`}
          >
            Mới nhất
          </Link>
        </div>
      </div>
    </div>
  );
}
