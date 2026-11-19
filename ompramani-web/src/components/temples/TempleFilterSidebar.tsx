"use client";

import React from "react";
import { TempleFilterFacets, TempleType } from "@/types/temple";

interface TempleFilterSidebarProps {
  facets: TempleFilterFacets | null;
  selectedType?: TempleType;
  selectedProvince?: string;
  selectedTradition?: string | number;
  selectedSort?: "relevance" | "name_asc" | "name_desc" | "newest";
  onTypeChange: (type?: TempleType) => void;
  onProvinceChange: (province?: string) => void;
  onTraditionChange: (tradition?: string) => void;
  onSortChange: (sort?: "relevance" | "name_asc" | "name_desc" | "newest") => void;
  onReset: () => void;
  hasActiveFilters: boolean;
}

export function TempleFilterSidebar({
  facets,
  selectedType,
  selectedProvince,
  selectedTradition,
  selectedSort = "relevance",
  onTypeChange,
  onProvinceChange,
  onTraditionChange,
  onSortChange,
  onReset,
  hasActiveFilters,
}: TempleFilterSidebarProps) {
  return (
    <aside
      aria-label="Bộ lọc tìm kiếm địa điểm Phật giáo"
      className="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/40 space-y-6 shadow-xs"
    >
      {/* Sidebar Header */}
      <div className="flex items-center justify-between pb-4 border-b border-outline-variant/30">
        <div className="flex items-center gap-2">
          <span className="material-symbols-outlined text-[20px] text-primary">
            tune
          </span>
          <h2 className="font-headline-sm text-headline-sm font-semibold text-on-surface">
            Bộ Lọc
          </h2>
        </div>

        {hasActiveFilters && (
          <button
            type="button"
            onClick={onReset}
            className="font-label-sm text-label-sm text-primary hover:underline font-medium"
          >
            Đặt lại
          </button>
        )}
      </div>

      {/* 1. Sắp xếp kết quả */}
      <div className="space-y-2">
        <label
          htmlFor="temple-sort-select"
          className="font-label-md text-label-md font-semibold text-on-surface block"
        >
          Sắp xếp theo
        </label>
        <select
          id="temple-sort-select"
          value={selectedSort}
          onChange={(e) =>
            onSortChange(
              e.target.value as "relevance" | "name_asc" | "name_desc" | "newest"
            )
          }
          className="w-full px-3.5 py-2.5 bg-surface border border-outline-variant/60 rounded-xl text-on-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition-all"
        >
          <option value="relevance">Độ phù hợp nhất</option>
          <option value="name_asc">Tên tự viện (A → Z)</option>
          <option value="name_desc">Tên tự viện (Z → A)</option>
          <option value="newest">Mới cập nhật</option>
        </select>
      </div>

      {/* 2. Phân loại cơ sở (TempleType) */}
      <div className="space-y-2.5">
        <label className="font-label-md text-label-md font-semibold text-on-surface block">
          Phân loại cơ sở
        </label>
        <div className="space-y-1.5">
          <button
            type="button"
            onClick={() => onTypeChange(undefined)}
            className={`w-full text-left px-3 py-2 rounded-lg font-body-sm text-body-sm transition-colors flex items-center justify-between ${
              !selectedType
                ? "bg-primary/10 text-primary font-semibold"
                : "text-on-surface-variant hover:bg-surface-container"
            }`}
          >
            <span>Tất cả loại hình</span>
            {!selectedType && (
              <span className="material-symbols-outlined text-[16px]">check</span>
            )}
          </button>

          {facets?.types?.map((typeItem) => {
            const isSelected = selectedType === typeItem.key;
            return (
              <button
                key={typeItem.key}
                type="button"
                onClick={() => onTypeChange(isSelected ? undefined : typeItem.key)}
                className={`w-full text-left px-3 py-2 rounded-lg font-body-sm text-body-sm transition-colors flex items-center justify-between ${
                  isSelected
                    ? "bg-primary/10 text-primary font-semibold"
                    : "text-on-surface-variant hover:bg-surface-container"
                }`}
              >
                <span>{typeItem.label}</span>
                {isSelected && (
                  <span className="material-symbols-outlined text-[16px]">check</span>
                )}
              </button>
            );
          })}
        </div>
      </div>

      {/* 3. Tỉnh / Thành phố */}
      <div className="space-y-2">
        <label
          htmlFor="temple-province-select"
          className="font-label-md text-label-md font-semibold text-on-surface block"
        >
          Tỉnh / Thành phố
        </label>
        <select
          id="temple-province-select"
          value={selectedProvince || ""}
          onChange={(e) => onProvinceChange(e.target.value || undefined)}
          className="w-full px-3.5 py-2.5 bg-surface border border-outline-variant/60 rounded-xl text-on-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition-all"
        >
          <option value="">Tất cả tỉnh thành</option>
          {facets?.provinces?.map((prov) => (
            <option key={prov} value={prov}>
              {prov}
            </option>
          ))}
        </select>
      </div>

      {/* 4. Truyền thống / Hệ phái */}
      <div className="space-y-2">
        <label
          htmlFor="temple-tradition-select"
          className="font-label-md text-label-md font-semibold text-on-surface block"
        >
          Truyền thống tu học
        </label>
        <select
          id="temple-tradition-select"
          value={selectedTradition || ""}
          onChange={(e) => onTraditionChange(e.target.value || undefined)}
          className="w-full px-3.5 py-2.5 bg-surface border border-outline-variant/60 rounded-xl text-on-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition-all"
        >
          <option value="">Tất cả hệ phái</option>
          {facets?.traditions?.map((trad) => (
            <option key={trad.slug} value={trad.slug}>
              {trad.name}
            </option>
          ))}
        </select>
      </div>
    </aside>
  );
}
