"use client";

import React from "react";

interface ProductPaginationProps {
  currentPage: number;
  lastPage: number;
  onPageChange: (page: number) => void;
}

export function ProductPagination({
  currentPage,
  lastPage,
  onPageChange,
}: ProductPaginationProps) {
  if (lastPage <= 1) {
    return null;
  }

  const getPageNumbers = () => {
    const pages: (number | string)[] = [];
    const delta = 1;

    for (let i = 1; i <= lastPage; i++) {
      if (
        i === 1 ||
        i === lastPage ||
        (i >= currentPage - delta && i <= currentPage + delta)
      ) {
        pages.push(i);
      } else if (pages[pages.length - 1] !== "...") {
        pages.push("...");
      }
    }

    return pages;
  };

  const pages = getPageNumbers();

  return (
    <nav
      role="navigation"
      aria-label="Phân trang vật phẩm"
      className="flex items-center justify-center gap-1.5 py-8"
    >
      {/* Previous Button */}
      <button
        type="button"
        disabled={currentPage <= 1}
        onClick={() => onPageChange(currentPage - 1)}
        className="inline-flex items-center gap-1 px-3.5 py-2 rounded-xl text-xs font-label-md font-medium text-on-surface bg-surface-container-lowest border border-outline-variant/50 hover:bg-surface-container-low disabled:opacity-40 disabled:pointer-events-none transition-colors cursor-pointer shadow-xs"
      >
        <span className="material-symbols-outlined text-[16px]">chevron_left</span>
        <span className="hidden sm:inline">Trước</span>
      </button>

      {/* Page Numbers */}
      <div className="flex items-center gap-1">
        {pages.map((p, idx) => {
          if (p === "...") {
            return (
              <span
                key={`ellipsis-${idx}`}
                className="w-8 h-8 flex items-center justify-center text-xs text-on-surface-variant font-medium select-none"
              >
                ...
              </span>
            );
          }

          const pageNum = p as number;
          const isActive = pageNum === currentPage;

          return (
            <button
              key={`page-${pageNum}`}
              type="button"
              onClick={() => onPageChange(pageNum)}
              aria-current={isActive ? "page" : undefined}
              className={`w-9 h-9 rounded-xl text-xs font-mono font-medium transition-colors cursor-pointer flex items-center justify-center ${
                isActive
                  ? "bg-primary text-on-primary font-bold shadow-xs"
                  : "bg-surface-container-lowest text-on-surface border border-outline-variant/50 hover:bg-surface-container-low"
              }`}
            >
              {pageNum}
            </button>
          );
        })}
      </div>

      {/* Next Button */}
      <button
        type="button"
        disabled={currentPage >= lastPage}
        onClick={() => onPageChange(currentPage + 1)}
        className="inline-flex items-center gap-1 px-3.5 py-2 rounded-xl text-xs font-label-md font-medium text-on-surface bg-surface-container-lowest border border-outline-variant/50 hover:bg-surface-container-low disabled:opacity-40 disabled:pointer-events-none transition-colors cursor-pointer shadow-xs"
      >
        <span className="hidden sm:inline">Sau</span>
        <span className="material-symbols-outlined text-[16px]">chevron_right</span>
      </button>
    </nav>
  );
}
