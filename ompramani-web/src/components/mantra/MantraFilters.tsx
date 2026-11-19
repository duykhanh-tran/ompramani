"use client";

import React from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { BuddhistFigure, Tradition } from "@/types/api";

interface MantraFiltersProps {
  traditions: Tradition[];
  figures: BuddhistFigure[];
  currentTradition?: string;
  currentFigure?: string;
}

export function MantraFilters({
  traditions,
  figures,
  currentTradition,
  currentFigure,
}: MantraFiltersProps) {
  const searchParams = useSearchParams();

  const createFilterUrl = (updates: {
    tradition?: string;
    figure?: string;
  }) => {
    const params = new URLSearchParams(searchParams.toString());

    if (updates.tradition !== undefined) {
      if (updates.tradition) {
        params.set("tradition", updates.tradition);
      } else {
        params.delete("tradition");
      }
    }

    if (updates.figure !== undefined) {
      if (updates.figure) {
        params.set("figure", updates.figure);
      } else {
        params.delete("figure");
      }
    }

    params.delete("page");
    const str = params.toString();
    return str ? `/chu?${str}` : "/chu";
  };

  return (
    <div className="flex flex-col gap-3">
      {/* Row 1: Tradition Filter */}
      <div
        className="flex flex-wrap items-center gap-2"
        role="tablist"
        aria-label="Lọc thần chú theo hệ phái"
      >
        <span className="text-[12px] font-label-md text-outline mr-1">
          Hệ phái:
        </span>
        <Link
          href={createFilterUrl({ tradition: "" })}
          role="tab"
          aria-selected={!currentTradition}
          className={`px-4 py-1.5 rounded-full font-label-md text-label-md transition-all ${
            !currentTradition
              ? "bg-primary text-on-primary shadow-sm font-semibold"
              : "bg-surface-container-low text-on-surface hover:bg-surface-container hover:text-primary border border-outline-variant/40"
          }`}
        >
          Tất cả
        </Link>

        {traditions.map((tradition) => {
          const isActive = currentTradition === tradition.slug;
          return (
            <Link
              key={tradition.id}
              href={createFilterUrl({ tradition: tradition.slug })}
              role="tab"
              aria-selected={isActive}
              className={`px-4 py-1.5 rounded-full font-label-md text-label-md transition-all ${
                isActive
                  ? "bg-primary text-on-primary shadow-sm font-semibold"
                  : "bg-surface-container-low text-on-surface hover:bg-surface-container hover:text-primary border border-outline-variant/40"
              }`}
            >
              {tradition.name}
            </Link>
          );
        })}
      </div>

      {/* Row 2: Related Buddhist Figure Filter */}
      {figures.length > 0 && (
        <div
          className="flex flex-wrap items-center gap-2"
          role="tablist"
          aria-label="Lọc thần chú theo Phật và Bồ Tát"
        >
          <span className="text-[12px] font-label-md text-outline mr-1">
            Bổn tôn:
          </span>
          <Link
            href={createFilterUrl({ figure: "" })}
            role="tab"
            aria-selected={!currentFigure}
            className={`px-3.5 py-1 rounded-full font-label-md text-[12px] transition-all ${
              !currentFigure
                ? "bg-secondary-container text-on-secondary-container font-semibold shadow-xs"
                : "bg-surface-container-lowest text-on-surface-variant hover:text-primary border border-outline-variant/40"
            }`}
          >
            Tất cả Chư Phật · Bồ Tát
          </Link>

          {figures.map((fig) => {
            const isActive =
              currentFigure === fig.slug ||
              currentFigure === fig.id.toString();
            return (
              <Link
                key={fig.id}
                href={createFilterUrl({ figure: fig.slug })}
                role="tab"
                aria-selected={isActive}
                className={`px-3.5 py-1 rounded-full font-label-md text-[12px] transition-all ${
                  isActive
                    ? "bg-secondary-container text-on-secondary-container font-semibold shadow-xs"
                    : "bg-surface-container-lowest text-on-surface-variant hover:text-primary border border-outline-variant/40"
                }`}
              >
                {fig.name}
              </Link>
            );
          })}
        </div>
      )}
    </div>
  );
}
