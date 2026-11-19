import React from "react";
import Link from "next/link";

export function CartEmptyState() {
  return (
    <div className="p-12 sm:p-16 text-center bg-surface-container-lowest rounded-3xl border border-outline-variant/40 space-y-4 max-w-lg mx-auto shadow-xs">
      <div className="w-16 h-16 mx-auto rounded-full bg-surface-container-high text-primary flex items-center justify-center">
        <span className="material-symbols-outlined text-[36px]">
          remove_shopping_cart
        </span>
      </div>

      <div className="space-y-1.5">
        <h3 className="font-headline-sm text-lg sm:text-xl font-serif font-bold text-on-surface">
          Giỏ Hàng Của Bạn Đang Trống
        </h3>
        <p className="font-body-md text-xs sm:text-sm text-on-surface-variant max-w-sm mx-auto leading-relaxed">
          Quý đạo hữu chưa chọn vật phẩm nào. Hãy ghé qua Cửa Hàng để thỉnh các loại tràng hạt, pháp khí hoặc kinh sách phù hợp cho việc tu học.
        </p>
      </div>

      <div className="pt-3">
        <Link
          href="/cua-hang"
          className="inline-flex items-center gap-2 px-6 py-3 rounded-full text-xs sm:text-sm font-label-md font-semibold bg-primary hover:bg-primary/90 text-on-primary transition-all shadow-xs"
        >
          <span className="material-symbols-outlined text-[18px]">storefront</span>
          <span>Khám phá Cửa Hàng Vật Phẩm</span>
        </Link>
      </div>
    </div>
  );
}
