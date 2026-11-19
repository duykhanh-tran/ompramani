"use client";

import React, { useEffect } from "react";
import { TempleFilterFacets, TempleType } from "@/types/temple";

interface TempleFilterDrawerProps {
  isOpen: boolean;
  onClose: () => void;
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

export function TempleFilterDrawer({
  isOpen,
  onClose,
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
}: TempleFilterDrawerProps) {
  // Lock body scroll when drawer is open
  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = "hidden";
    } else {
      document.body.style.overflow = "";
    }
    return () => {
      document.body.style.overflow = "";
    };
  }, [isOpen]);

  // Handle ESC key
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === "Escape" && isOpen) {
        onClose();
      }
    };
    window.addEventListener("keydown", handleKeyDown);
    return () => window.removeEventListener("keydown", handleKeyDown);
  }, [isOpen, onClose]);

  if (!isOpen) {
    return null;
  }

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-label="Bảng bộ lọc tìm kiếm trên thiết bị di động"
      className="fixed inset-0 z-50 flex flex-col justify-end lg:hidden"
    >
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"
        onClick={onClose}
        aria-hidden="true"
      />

      {/* Drawer Container */}
      <div className="relative bg-surface rounded-t-3xl max-h-[85vh] flex flex-col w-full shadow-2xl border-t border-outline-variant/40 animate-in slide-in-from-bottom duration-250">
        {/* Drawer Header */}
        <div className="p-4 px-6 border-b border-outline-variant/30 flex items-center justify-between shrink-0">
          <div className="flex items-center gap-2">
            <span className="material-symbols-outlined text-primary text-[22px]">
              tune
            </span>
            <h2 className="font-headline-sm text-headline-sm font-semibold text-on-surface">
              Bộ Lọc Địa Điểm
            </h2>
          </div>

          <button
            type="button"
            onClick={onClose}
            aria-label="Đóng bảng lọc"
            className="p-1.5 rounded-full text-on-surface-variant hover:text-on-surface hover:bg-surface-container transition-colors"
          >
            <span className="material-symbols-outlined text-[20px]">close</span>
          </button>
        </div>

        {/* Scrollable Filters */}
        <div className="p-6 overflow-y-auto space-y-6 flex-1">
          {/* Sắp xếp */}
          <div className="space-y-2">
            <label
              htmlFor="drawer-sort-select"
              className="font-label-md text-label-md font-semibold text-on-surface block"
            >
              Sắp xếp theo
            </label>
            <select
              id="drawer-sort-select"
              value={selectedSort}
              onChange={(e) =>
                onSortChange(
                  e.target.value as "relevance" | "name_asc" | "name_desc" | "newest"
                )
              }
              className="w-full px-3.5 py-3 bg-surface-container-lowest border border-outline-variant/60 rounded-xl text-on-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition-all"
            >
              <option value="relevance">Độ phù hợp nhất</option>
              <option value="name_asc">Tên tự viện (A → Z)</option>
              <option value="name_desc">Tên tự viện (Z → A)</option>
              <option value="newest">Mới cập nhật</option>
            </select>
          </div>

          {/* Phân loại cơ sở */}
          <div className="space-y-2.5">
            <label className="font-label-md text-label-md font-semibold text-on-surface block">
              Phân loại cơ sở
            </label>
            <div className="grid grid-cols-2 gap-2">
              <button
                type="button"
                onClick={() => onTypeChange(undefined)}
                className={`px-3 py-2.5 rounded-xl font-label-md text-label-md transition-all text-center border ${
                  !selectedType
                    ? "bg-primary text-on-primary border-primary font-semibold shadow-xs"
                    : "bg-surface-container-lowest text-on-surface-variant border-outline-variant/40 hover:bg-surface-container"
                }`}
              >
                Tất cả
              </button>

              {facets?.types?.map((typeItem) => {
                const isSelected = selectedType === typeItem.key;
                return (
                  <button
                    key={typeItem.key}
                    type="button"
                    onClick={() => onTypeChange(isSelected ? undefined : typeItem.key)}
                    className={`px-3 py-2.5 rounded-xl font-label-md text-label-md transition-all text-center border ${
                      isSelected
                        ? "bg-primary text-on-primary border-primary font-semibold shadow-xs"
                        : "bg-surface-container-lowest text-on-surface-variant border-outline-variant/40 hover:bg-surface-container"
                    }`}
                  >
                    {typeItem.label}
                  </button>
                );
              })}
            </div>
          </div>

          {/* Tỉnh / Thành phố */}
          <div className="space-y-2">
            <label
              htmlFor="drawer-province-select"
              className="font-label-md text-label-md font-semibold text-on-surface block"
            >
              Tỉnh / Thành phố
            </label>
            <select
              id="drawer-province-select"
              value={selectedProvince || ""}
              onChange={(e) => onProvinceChange(e.target.value || undefined)}
              className="w-full px-3.5 py-3 bg-surface-container-lowest border border-outline-variant/60 rounded-xl text-on-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition-all"
            >
              <option value="">Tất cả tỉnh thành</option>
              {facets?.provinces?.map((prov) => (
                <option key={prov} value={prov}>
                  {prov}
                </option>
              ))}
            </select>
          </div>

          {/* Truyền thống */}
          <div className="space-y-2">
            <label
              htmlFor="drawer-tradition-select"
              className="font-label-md text-label-md font-semibold text-on-surface block"
            >
              Truyền thống tu học
            </label>
            <select
              id="drawer-tradition-select"
              value={selectedTradition || ""}
              onChange={(e) => onTraditionChange(e.target.value || undefined)}
              className="w-full px-3.5 py-3 bg-surface-container-lowest border border-outline-variant/60 rounded-xl text-on-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition-all"
            >
              <option value="">Tất cả hệ phái</option>
              {facets?.traditions?.map((trad) => (
                <option key={trad.slug} value={trad.slug}>
                  {trad.name}
                </option>
              ))}
            </select>
          </div>
        </div>

        {/* Drawer Actions */}
        <div className="p-4 px-6 border-t border-outline-variant/30 flex items-center gap-3 shrink-0 bg-surface">
          {hasActiveFilters && (
            <button
              type="button"
              onClick={onReset}
              className="px-4 py-3 border border-outline-variant text-on-surface font-label-md text-label-md font-medium rounded-xl hover:bg-surface-container transition-colors w-1/3"
            >
              Đặt lại
            </button>
          )}

          <button
            type="button"
            onClick={onClose}
            className="flex-1 py-3 bg-primary text-on-primary font-label-md text-label-md font-semibold rounded-xl hover:bg-primary/90 transition-colors shadow-xs"
          >
            Áp dụng bộ lọc
          </button>
        </div>
      </div>
    </div>
  );
}
