"use client";

import React from "react";
import { ProductCategory } from "@/types/shop";

interface ProductCategoryFilterProps {
  categories: ProductCategory[];
  selectedCategory: string;
  onSelectCategory: (categorySlug: string) => void;
}

export function ProductCategoryFilter({
  categories,
  selectedCategory,
  onSelectCategory,
}: ProductCategoryFilterProps) {
  const totalProductsCount = categories.reduce(
    (sum, c) => sum + (c.products_count || 0),
    0
  );

  return (
    <div className="w-full overflow-x-auto pb-2 scrollbar-none">
      <div className="flex items-center gap-2 min-w-max">
        {/* All Categories Option */}
        <button
          type="button"
          onClick={() => onSelectCategory("all")}
          className={`inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs sm:text-sm font-medium transition-all cursor-pointer ${
            selectedCategory === "all"
              ? "bg-primary text-on-primary shadow-xs"
              : "bg-surface-container-lowest text-on-surface hover:bg-surface-container border border-outline-variant/40"
          }`}
        >
          <span>Tất cả</span>
          {totalProductsCount > 0 && (
            <span
              className={`text-[10px] px-1.5 py-0.2 rounded-full font-mono ${
                selectedCategory === "all"
                  ? "bg-on-primary/20 text-on-primary"
                  : "bg-surface-container-high text-on-surface-variant"
              }`}
            >
              {totalProductsCount}
            </span>
          )}
        </button>

        {/* Dynamic Categories */}
        {categories.map((cat) => {
          const isSelected = selectedCategory === cat.slug;
          return (
            <button
              key={cat.id}
              type="button"
              onClick={() => onSelectCategory(cat.slug)}
              className={`inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs sm:text-sm font-medium transition-all cursor-pointer ${
                isSelected
                  ? "bg-primary text-on-primary shadow-xs"
                  : "bg-surface-container-lowest text-on-surface hover:bg-surface-container border border-outline-variant/40"
              }`}
            >
              <span>{cat.name}</span>
              {typeof cat.products_count === "number" && (
                <span
                  className={`text-[10px] px-1.5 py-0.2 rounded-full font-mono ${
                    isSelected
                      ? "bg-on-primary/20 text-on-primary"
                      : "bg-surface-container-high text-on-surface-variant"
                  }`}
                >
                  {cat.products_count}
                </span>
              )}
            </button>
          );
        })}
      </div>
    </div>
  );
}
