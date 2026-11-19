"use client";

import React from "react";
import Link from "next/link";
import { useCart } from "@/context/CartContext";

export function CartHeaderBadge() {
  const { totalQuantity, isHydrated } = useCart();
  const count = isHydrated ? totalQuantity : 0;
  const label = count > 0 ? `Giỏ hàng, ${count} sản phẩm` : "Giỏ hàng";

  return (
    <Link
      href="/gio-hang"
      className="relative w-8 h-8 rounded-full border border-outline-variant/60 flex items-center justify-center text-on-surface-variant hover:text-primary hover:border-primary transition-colors focus:outline-hidden focus:ring-1 focus:ring-primary"
      aria-label={label}
      title={label}
    >
      <span className="material-symbols-outlined text-[18px]">
        shopping_bag
      </span>

      {count > 0 && (
        <span
          className="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 bg-amber-600 text-white text-[10px] font-mono font-bold rounded-full flex items-center justify-center shadow-xs border border-surface animate-in zoom-in-75"
          aria-hidden="true"
        >
          {count > 99 ? "99+" : count}
        </span>
      )}
    </Link>
  );
}
