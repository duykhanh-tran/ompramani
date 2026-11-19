"use client";

import React from "react";

interface TemplePaginationProps {
  currentPage: number;
  lastPage: number;
  onPageChange: (page: number) => void;
}

export function TemplePagination({
  currentPage,
  lastPage,
  onPageChange,
}: TemplePaginationProps) {
  if (lastPage <= 1) {
    return null;
  }

  // Generate page numbers window
  const getPages = () => {
    const pages: (number | string)[] = [];
    const delta = 2;

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

  const pages = getPages();

  return (
    <nav
      aria-label="Phân trang danh mục chùa và đạo tràng"
      className="flex items-center justify-center gap-1.5 pt-8"
    >
      {/* Previous Button */}
      <button
        type="button"
        disabled={currentPage <= 1}
        onClick={() => onPageChange(currentPage - 1)}
        aria-label="Trang trước"
        className="p-2 rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
      >
        <span className="material-symbols-outlined text-[20px]">
          navigate_before
        </span>
      </button>

      {/* Page Numbers */}
      <div className="flex items-center gap-1">
        {pages.map((p, idx) => {
          if (p === "...") {
            return (
              <span
                key={`ellipsis-${idx}`}
                className="px-2.5 py-1 text-on-surface-variant font-body-sm text-body-sm select-none"
              >
                ...
              </span>
            );
          }

          const pageNum = p as number;
          const isCurrent = pageNum === currentPage;

          return (
            <button
              key={pageNum}
              type="button"
              onClick={() => onPageChange(pageNum)}
              aria-label={`Trang ${pageNum}`}
              aria-current={isCurrent ? "page" : undefined}
              className={`min-w-[38px] h-[38px] px-3 rounded-lg font-label-md text-label-md transition-all ${
                isCurrent
                  ? "bg-primary text-on-primary font-semibold shadow-xs"
                  : "border border-outline-variant/40 text-on-surface hover:bg-surface-container"
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
        aria-label="Trang sau"
        className="p-2 rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
      >
        <span className="material-symbols-outlined text-[20px]">
          navigate_next
        </span>
      </button>
    </nav>
  );
}
