"use client";

import React from "react";
import { TempleFilterFacets, TempleType } from "@/types/temple";

interface TempleActiveFilterChipsProps {
  q?: string;
  type?: TempleType;
  province?: string;
  country?: string;
  tradition?: string | number;
  facets?: TempleFilterFacets | null;
  onRemoveQuery: () => void;
  onRemoveType: () => void;
  onRemoveProvince: () => void;
  onRemoveCountry: () => void;
  onRemoveTradition: () => void;
  onClearAll: () => void;
}

export function TempleActiveFilterChips({
  q,
  type,
  province,
  country,
  tradition,
  facets,
  onRemoveQuery,
  onRemoveType,
  onRemoveProvince,
  onRemoveCountry,
  onRemoveTradition,
  onClearAll,
}: TempleActiveFilterChipsProps) {
  const hasActiveFilters = Boolean(q || type || province || country || tradition);

  if (!hasActiveFilters) {
    return null;
  }

  // Find human-readable label for selected type
  const typeLabel = facets?.types.find((t) => t.key === type)?.label || type;

  // Find human-readable label for selected tradition
  const traditionLabel =
    facets?.traditions.find(
      (tr) =>
        tr.slug === tradition ||
        (typeof tradition === "number" && tr.id === tradition) ||
        (typeof tradition === "string" && tr.id.toString() === tradition)
    )?.name || tradition;

  return (
    <div className="flex items-center gap-2 flex-wrap pt-2">
      <span className="font-label-sm text-label-sm text-on-surface-variant font-medium mr-1">
        Đang lọc theo:
      </span>

      {q && (
        <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-surface-container-high text-on-surface rounded-full font-label-sm text-label-sm shadow-2xs">
          <span>Từ khóa: &ldquo;{q}&rdquo;</span>
          <button
            type="button"
            onClick={onRemoveQuery}
            aria-label={`Bỏ lọc từ khóa ${q}`}
            className="hover:text-primary transition-colors flex items-center justify-center"
          >
            <span className="material-symbols-outlined text-[15px]">close</span>
          </button>
        </span>
      )}

      {type && (
        <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-surface-container-high text-on-surface rounded-full font-label-sm text-label-sm shadow-2xs">
          <span>Cơ sở: {typeLabel}</span>
          <button
            type="button"
            onClick={onRemoveType}
            aria-label={`Bỏ lọc loại hình ${typeLabel}`}
            className="hover:text-primary transition-colors flex items-center justify-center"
          >
            <span className="material-symbols-outlined text-[15px]">close</span>
          </button>
        </span>
      )}

      {province && (
        <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-surface-container-high text-on-surface rounded-full font-label-sm text-label-sm shadow-2xs">
          <span>Tỉnh/Thành: {province}</span>
          <button
            type="button"
            onClick={onRemoveProvince}
            aria-label={`Bỏ lọc tỉnh thành ${province}`}
            className="hover:text-primary transition-colors flex items-center justify-center"
          >
            <span className="material-symbols-outlined text-[15px]">close</span>
          </button>
        </span>
      )}

      {country && country !== "VN" && (
        <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-surface-container-high text-on-surface rounded-full font-label-sm text-label-sm shadow-2xs">
          <span>Quốc gia: {country}</span>
          <button
            type="button"
            onClick={onRemoveCountry}
            aria-label={`Bỏ lọc quốc gia ${country}`}
            className="hover:text-primary transition-colors flex items-center justify-center"
          >
            <span className="material-symbols-outlined text-[15px]">close</span>
          </button>
        </span>
      )}

      {tradition && (
        <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-surface-container-high text-on-surface rounded-full font-label-sm text-label-sm shadow-2xs">
          <span>Hệ phái: {traditionLabel}</span>
          <button
            type="button"
            onClick={onRemoveTradition}
            aria-label={`Bỏ lọc hệ phái ${traditionLabel}`}
            className="hover:text-primary transition-colors flex items-center justify-center"
          >
            <span className="material-symbols-outlined text-[15px]">close</span>
          </button>
        </span>
      )}

      <button
        type="button"
        onClick={onClearAll}
        className="font-label-sm text-label-sm text-primary hover:underline font-medium ml-1"
      >
        Xóa tất cả
      </button>
    </div>
  );
}
