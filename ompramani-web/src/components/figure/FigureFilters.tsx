"use client";

import React from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { Tradition } from "@/types/api";
import { FIGURE_TYPE_OPTIONS } from "@/lib/utils/sacred";

interface FigureFiltersProps {
  traditions: Tradition[];
  currentType?: string;
  currentTradition?: string;
}

export function FigureFilters({
  traditions,
  currentType,
  currentTradition,
}: FigureFiltersProps) {
  const searchParams = useSearchParams();

  const createFilterUrl = (updates: {
    type?: string;
    tradition?: string;
  }) => {
    const params = new URLSearchParams(searchParams.toString());

    if (updates.type !== undefined) {
      if (updates.type) {
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

    params.delete("page"); // Reset to page 1 on filter change
    const str = params.toString();
    return str ? `/phat-bo-tat?${str}` : "/phat-bo-tat";
  };

  return (
    <div className="flex flex-col gap-3">
      {/* Row 1: Figure Type Filter */}
      <div className="flex flex-wrap items-center gap-2" role="tablist" aria-label="Lọc theo phân vị Phật và Bồ Tát">
        <span className="text-[12px] font-label-md text-outline mr-1">
          Phân vị:
        </span>
        {FIGURE_TYPE_OPTIONS.map((opt) => {
          const isActive = (currentType || "") === opt.value;
          return (
            <Link
              key={opt.value || "all"}
              href={createFilterUrl({ type: opt.value })}
              role="tab"
              aria-selected={isActive}
              className={`px-4 py-1.5 rounded-full font-label-md text-label-md transition-all ${
                isActive
                  ? "bg-primary text-on-primary shadow-sm font-semibold"
                  : "bg-surface-container-low text-on-surface hover:bg-surface-container hover:text-primary border border-outline-variant/40"
              }`}
            >
              {opt.label}
            </Link>
          );
        })}
      </div>

      {/* Row 2: Tradition Filter (only rendered when traditions exist from backend) */}
      {traditions.length > 0 && (
        <div className="flex flex-wrap items-center gap-2" role="tablist" aria-label="Lọc theo truyền thống Phật giáo">
          <span className="text-[12px] font-label-md text-outline mr-1">
            Hệ phái:
          </span>
          <Link
            href={createFilterUrl({ tradition: "" })}
            role="tab"
            aria-selected={!currentTradition}
            className={`px-3.5 py-1 rounded-full font-label-md text-[12px] transition-all ${
              !currentTradition
                ? "bg-secondary-container text-on-secondary-container font-semibold shadow-xs"
                : "bg-surface-container-lowest text-on-surface-variant hover:text-primary border border-outline-variant/40"
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
                role="tab"
                aria-selected={isActive}
                className={`px-3.5 py-1 rounded-full font-label-md text-[12px] transition-all ${
                  isActive
                    ? "bg-secondary-container text-on-secondary-container font-semibold shadow-xs"
                    : "bg-surface-container-lowest text-on-surface-variant hover:text-primary border border-outline-variant/40"
                }`}
              >
                {tradition.name}
              </Link>
            );
          })}
        </div>
      )}
    </div>
  );
}
