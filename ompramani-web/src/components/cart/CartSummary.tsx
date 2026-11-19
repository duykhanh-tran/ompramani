"use client";

import React, { useState } from "react";
import Link from "next/link";

interface CartSummaryProps {
  formattedSubtotal: string;
  totalQuantity: number;
  isValid: boolean;
  onClearCart: () => void;
}

export function CartSummary({
  formattedSubtotal,
  totalQuantity,
  isValid,
  onClearCart,
}: CartSummaryProps) {
  const [showConfirmClear, setShowConfirmClear] = useState(false);

  return (
    <div className="p-6 rounded-3xl bg-surface-container-low/70 border border-outline-variant/40 space-y-5 sticky top-24 shadow-xs">
      <h2 className="font-serif text-lg font-bold text-on-surface border-b border-outline-variant/30 pb-3">
        Tóm Tắt Đơn Hàng
      </h2>

      <div className="space-y-3 text-sm">
        <div className="flex justify-between items-center text-on-surface-variant">
          <span>Tổng số lượng vật phẩm:</span>
          <span className="font-mono font-medium text-on-surface">{totalQuantity}</span>
        </div>

        <div className="flex justify-between items-baseline pt-2 border-t border-outline-variant/20">
          <div>
            <span className="font-medium text-on-surface block">Tạm tính tiền hàng:</span>
            <span className="text-[11px] text-on-surface-variant">(Chưa bao gồm cước vận chuyển)</span>
          </div>
          <span className="font-serif font-bold text-2xl text-amber-700 dark:text-amber-400">
            {formattedSubtotal}
          </span>
        </div>
      </div>

      {/* Mindful Disclaimer */}
      <div className="p-3.5 rounded-2xl bg-amber-50/70 dark:bg-amber-950/20 border border-amber-200/40 dark:border-amber-800/30 text-xs text-on-surface-variant leading-relaxed">
        <strong className="text-on-surface font-semibold">Lưu ý chánh niệm: </strong>
        Đây là số tiền tạm tính từ hệ thống. Giá cả và tình trạng sẵn có của vật phẩm sẽ được xác thực chính xác khi quý Phật tử tiến hành đặt hàng.
      </div>

      {!isValid && (
        <div className="p-3 rounded-2xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 text-xs text-rose-700 dark:text-rose-300">
          Một số vật phẩm trong giỏ hàng hiện không đủ số lượng hoặc tạm ngừng phát hành. Vui lòng điều chỉnh hoặc xóa trước khi tiếp tục.
        </div>
      )}

      {/* Checkout CTA */}
      <div className="space-y-3 pt-2">
        {isValid && totalQuantity > 0 ? (
          <Link
            href="/thanh-toan"
            className="w-full py-3.5 px-6 rounded-2xl font-label-lg font-semibold bg-primary hover:bg-primary/90 text-on-primary shadow-sm hover:shadow-md transition-all flex items-center justify-center gap-2 text-center text-sm cursor-pointer"
          >
            <span>Tiến Hành Đặt Hàng</span>
            <span className="material-symbols-outlined text-[18px]">arrow_forward</span>
          </Link>
        ) : (
          <button
            type="button"
            disabled
            className="w-full py-3.5 px-6 rounded-2xl font-label-lg font-semibold bg-primary/20 text-on-surface/50 cursor-not-allowed border border-primary/20 flex items-center justify-center text-center text-sm"
          >
            Tiến Hành Đặt Hàng
          </button>
        )}

        <Link
          href="/cua-hang"
          className="w-full inline-flex items-center justify-center gap-1.5 py-2.5 px-4 rounded-2xl text-xs font-medium text-on-surface-variant hover:text-primary hover:bg-surface-container transition-colors"
        >
          <span className="material-symbols-outlined text-[16px]">arrow_back</span>
          <span>Tiếp tục thỉnh vật phẩm</span>
        </Link>
      </div>

      {/* Clear Cart Action */}
      <div className="pt-3 border-t border-outline-variant/30 text-center">
        {showConfirmClear ? (
          <div className="space-y-2">
            <p className="text-xs text-on-surface-variant">
              Bạn có chắc chắn muốn xóa tất cả vật phẩm trong giỏ?
            </p>
            <div className="flex items-center justify-center gap-2">
              <button
                type="button"
                onClick={() => {
                  onClearCart();
                  setShowConfirmClear(false);
                }}
                className="px-3 py-1.5 rounded-xl text-xs font-semibold bg-rose-600 text-white hover:bg-rose-700 transition-colors cursor-pointer"
              >
                Xác nhận xóa
              </button>
              <button
                type="button"
                onClick={() => setShowConfirmClear(false)}
                className="px-3 py-1.5 rounded-xl text-xs font-medium text-on-surface-variant bg-surface-container hover:bg-surface-container-high transition-colors cursor-pointer"
              >
                Hủy
              </button>
            </div>
          </div>
        ) : (
          <button
            type="button"
            onClick={() => setShowConfirmClear(true)}
            className="text-xs text-on-surface-variant/70 hover:text-error transition-colors cursor-pointer inline-flex items-center gap-1"
          >
            <span className="material-symbols-outlined text-[14px]">delete_sweep</span>
            <span>Xóa toàn bộ giỏ hàng</span>
          </button>
        )}
      </div>
    </div>
  );
}
