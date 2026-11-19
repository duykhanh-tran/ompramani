"use client";

import React from "react";
import { ProductSortOption } from "@/types/shop";

interface ProductSortSelectorProps {
  currentSort: ProductSortOption;
  onSortChange: (sort: ProductSortOption) => void;
}

export function ProductSortSelector({
  currentSort,
  onSortChange,
}: ProductSortSelectorProps) {
  return (
    <div className="flex items-center gap-1.5 shrink-0">
      <span className="text-xs text-on-surface-variant hidden sm:inline">Sắp xếp:</span>
      <select
        value={currentSort}
        onChange={(e) => onSortChange(e.target.value as ProductSortOption)}
        className="px-3 py-2 rounded-xl text-xs sm:text-sm font-medium bg-surface-container-lowest border border-outline-variant/40 text-on-surface focus:outline-hidden focus:border-primary cursor-pointer shadow-xs"
        aria-label="Sắp xếp sản phẩm"
      >
        <option value="latest">Mới nhất</option>
        <option value="price_asc">Giá: Thấp đến cao</option>
        <option value="price_desc">Giá: Cao đến thấp</option>
      </select>
    </div>
  );
}
