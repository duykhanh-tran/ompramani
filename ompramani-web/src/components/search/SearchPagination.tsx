"use client";

import React from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { SearchMeta } from "@/types/api";

interface SearchPaginationProps {
  meta: SearchMeta;
}

export function SearchPagination({ meta }: SearchPaginationProps) {
  const searchParams = useSearchParams();

  const currentPage = meta.page || 1;
  const perPage = meta.per_page || 12;
  const lastPage = Math.max(1, Math.ceil(meta.total / perPage));

  if (lastPage <= 1) {
    return null;
  }

  const createPageUrl = (pageNumber: number) => {
    const params = new URLSearchParams(searchParams.toString());
    params.set("page", pageNumber.toString());
    return `/tim-kiem?${params.toString()}`;
  };

  const pages: (number | string)[] = [];
  for (let i = 1; i <= lastPage; i++) {
    if (i === 1 || i === lastPage || (i >= currentPage - 1 && i <= currentPage + 1)) {
      pages.push(i);
    } else if (pages[pages.length - 1] !== "...") {
      pages.push("...");
    }
  }

  return (
    <nav
      className="flex items-center justify-center gap-2 mt-12"
      aria-label="Phân trang kết quả tìm kiếm"
    >
      {currentPage > 1 ? (
        <Link
          href={createPageUrl(currentPage - 1)}
          className="px-3.5 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container text-on-surface hover:text-primary transition-all font-label-md text-label-md flex items-center gap-1 border border-outline-variant/40"
          aria-label="Trang trước"
        >
          <span className="material-symbols-outlined text-[18px]">
            chevron_left
          </span>
          <span className="hidden sm:inline">Trang trước</span>
        </Link>
      ) : (
        <span className="px-3.5 py-2 rounded-xl bg-surface-container/30 text-outline cursor-not-allowed font-label-md text-label-md flex items-center gap-1 border border-outline-variant/20">
          <span className="material-symbols-outlined text-[18px]">
            chevron_left
          </span>
          <span className="hidden sm:inline">Trang trước</span>
        </span>
      )}

      <div className="flex items-center gap-1">
        {pages.map((p, idx) => {
          if (p === "...") {
            return (
              <span
                key={`ellipsis-${idx}`}
                className="w-9 h-9 flex items-center justify-center text-outline font-label-md"
              >
                ...
              </span>
            );
          }

          const pageNum = Number(p);
          const isCurrent = pageNum === currentPage;

          return (
            <Link
              key={pageNum}
              href={createPageUrl(pageNum)}
              aria-current={isCurrent ? "page" : undefined}
              className={`w-9 h-9 rounded-xl flex items-center justify-center font-label-md text-label-md transition-all ${
                isCurrent
                  ? "bg-primary text-on-primary font-bold shadow-xs"
                  : "bg-surface-container-low hover:bg-surface-container text-on-surface hover:text-primary border border-outline-variant/30"
              }`}
            >
              {pageNum}
            </Link>
          );
        })}
      </div>

      {currentPage < lastPage ? (
        <Link
          href={createPageUrl(currentPage + 1)}
          className="px-3.5 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container text-on-surface hover:text-primary transition-all font-label-md text-label-md flex items-center gap-1 border border-outline-variant/40"
          aria-label="Trang tiếp"
        >
          <span className="hidden sm:inline">Trang tiếp</span>
          <span className="material-symbols-outlined text-[18px]">
            chevron_right
          </span>
        </Link>
      ) : (
        <span className="px-3.5 py-2 rounded-xl bg-surface-container/30 text-outline cursor-not-allowed font-label-md text-label-md flex items-center gap-1 border border-outline-variant/20">
          <span className="hidden sm:inline">Trang tiếp</span>
          <span className="material-symbols-outlined text-[18px]">
            chevron_right
          </span>
        </span>
      )}
    </nav>
  );
}
