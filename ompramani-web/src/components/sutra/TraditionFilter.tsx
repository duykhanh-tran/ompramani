"use client";

import React from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { Tradition } from "@/types/api";

interface TraditionFilterProps {
  traditions: Tradition[];
  currentTradition?: string;
}

export function TraditionFilter({
  traditions,
  currentTradition,
}: TraditionFilterProps) {
  const searchParams = useSearchParams();

  const createQueryString = (traditionSlug?: string) => {
    const params = new URLSearchParams(searchParams.toString());
    if (traditionSlug) {
      params.set("tradition", traditionSlug);
    } else {
      params.delete("tradition");
    }
    params.delete("page"); // Reset to page 1 on filter change
    const str = params.toString();
    return str ? `/kinh?${str}` : "/kinh";
  };

  return (
    <div className="flex flex-wrap items-center gap-2" role="tablist">
      {/* "Tất cả" chip */}
      <Link
        href={createQueryString()}
        role="tab"
        aria-selected={!currentTradition}
        className={`px-4 py-2 rounded-full font-label-md text-label-md transition-all ${
          !currentTradition
            ? "bg-primary text-on-primary shadow-sm font-semibold"
            : "bg-surface-container-low text-on-surface hover:bg-surface-container hover:text-primary border border-outline-variant/40"
        }`}
      >
        Tất cả
      </Link>

      {/* Dynamic traditions from backend */}
      {traditions.map((tradition) => {
        const isActive = currentTradition === tradition.slug;
        return (
          <Link
            key={tradition.id}
            href={createQueryString(tradition.slug)}
            role="tab"
            aria-selected={isActive}
            className={`px-4 py-2 rounded-full font-label-md text-label-md transition-all ${
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
  );
}
