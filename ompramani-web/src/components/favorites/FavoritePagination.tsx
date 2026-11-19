"use client";

import React from "react";

interface FavoritePaginationProps {
  currentPage: number;
  lastPage: number;
  onPageChange: (page: number) => void;
  disabled?: boolean;
}

export function FavoritePagination({
  currentPage,
  lastPage,
  onPageChange,
  disabled = false,
}: FavoritePaginationProps) {
  if (lastPage <= 1) return null;

  return (
    <nav
      aria-label="Phân trang danh sách yêu thích"
      className="flex items-center justify-center gap-2 pt-6"
    >
      <button
        type="button"
        disabled={currentPage <= 1 || disabled}
        onClick={() => onPageChange(currentPage - 1)}
        className="px-3.5 py-1.5 rounded-xl border border-outline-variant/30 text-xs font-label-md font-medium text-on-surface hover:bg-surface-container-low disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
      >
        Trang trước
      </button>

      <span className="text-xs font-label-md text-on-surface-variant px-2">
        Trang <span className="font-semibold text-on-surface">{currentPage}</span> / {lastPage}
      </span>

      <button
        type="button"
        disabled={currentPage >= lastPage || disabled}
        onClick={() => onPageChange(currentPage + 1)}
        className="px-3.5 py-1.5 rounded-xl border border-outline-variant/30 text-xs font-label-md font-medium text-on-surface hover:bg-surface-container-low disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
      >
        Trang sau
      </button>
    </nav>
  );
}
