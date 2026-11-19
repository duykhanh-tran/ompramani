"use client";

import React from "react";

interface ProductEmptyStateProps {
  hasFilters: boolean;
  onClearFilters: () => void;
}

export function ProductEmptyState({
  hasFilters,
  onClearFilters,
}: ProductEmptyStateProps) {
  return (
    <div className="p-12 sm:p-16 text-center bg-surface-container-lowest rounded-3xl border border-outline-variant/40 space-y-4 max-w-xl mx-auto shadow-xs">
      <div className="w-16 h-16 mx-auto rounded-full bg-surface-container-high text-primary flex items-center justify-center">
        <span className="material-symbols-outlined text-[36px]">
          {hasFilters ? "search_off" : "storefront"}
        </span>
      </div>

      <div className="space-y-1.5">
        <h3 className="font-headline-sm text-lg sm:text-xl font-serif font-bold text-on-surface">
          {hasFilters ? "Chưa Tìm Thấy Vật Phẩm Phù Hợp" : "Danh Mục Đang Được Cập Nhật"}
        </h3>
        <p className="font-body-md text-xs sm:text-sm text-on-surface-variant max-w-sm mx-auto leading-relaxed">
          {hasFilters
            ? "Thử tìm kiếm với từ khóa khác hoặc xóa bớt các bộ lọc danh mục để xem thêm các vật phẩm khác."
            : "Các vật phẩm tu học, pháp khí và kinh sách đang được tuyển chọn chuẩn mực trước khi xuất bản."}
        </p>
      </div>

      {hasFilters && (
        <div className="pt-2">
          <button
            type="button"
            onClick={onClearFilters}
            className="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-full text-xs font-label-md font-semibold bg-surface-container-high hover:bg-surface-container-highest text-on-surface transition-colors cursor-pointer"
          >
            <span className="material-symbols-outlined text-[16px]">restart_alt</span>
            <span>Xóa bộ lọc tìm kiếm</span>
          </button>
        </div>
      )}
    </div>
  );
}
