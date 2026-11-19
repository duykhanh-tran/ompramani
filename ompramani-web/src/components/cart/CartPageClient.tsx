"use client";

import React, { useEffect, useState, useRef } from "react";
import Link from "next/link";
import { useCart } from "@/context/CartContext";
import { CartPreviewData } from "@/types/shop";
import { previewCart } from "@/lib/api/shop";
import { CartItemCard } from "./CartItemCard";
import { CartSummary } from "./CartSummary";
import { CartEmptyState } from "./CartEmptyState";

export function CartPageClient() {
  const { items, isHydrated, updateQuantity, removeItem, clearCart } = useCart();
  const [previewData, setPreviewData] = useState<CartPreviewData | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  const previewReqIdRef = useRef<number>(0);

  useEffect(() => {
    if (!isHydrated || items.length === 0) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setPreviewData(null);
      return;
    }

    const reqId = ++previewReqIdRef.current;

    Promise.resolve().then(() => {
      if (reqId === previewReqIdRef.current) {
        setIsLoading(true);
        setErrorMsg(null);
      }
    });

    previewCart(items)
      .then((data) => {
        if (reqId === previewReqIdRef.current) {
          setPreviewData(data);
          setIsLoading(false);
        }
      })
      .catch((err) => {
        if (reqId === previewReqIdRef.current) {
          console.error("Cart preview failed:", err);
          setErrorMsg("Không thể xác thực giá và tình trạng tồn kho lúc này. Vui lòng thử lại sau.");
          setIsLoading(false);
        }
      });
  }, [items, isHydrated]);

  // Loading skeleton during initial hydration
  if (!isHydrated) {
    return (
      <div className="space-y-6 animate-pulse">
        <div className="h-8 bg-surface-container-high rounded-xl w-48" />
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
          <div className="lg:col-span-8 space-y-4">
            <div className="h-28 bg-surface-container-high rounded-3xl" />
            <div className="h-28 bg-surface-container-high rounded-3xl" />
          </div>
          <div className="lg:col-span-4">
            <div className="h-64 bg-surface-container-high rounded-3xl" />
          </div>
        </div>
      </div>
    );
  }

  if (items.length === 0) {
    return (
      <div className="space-y-6">
        <div className="mb-4">
          <h1 className="font-serif text-2xl sm:text-3xl font-bold text-on-surface">
            Giỏ Hàng Vật Phẩm
          </h1>
        </div>
        <CartEmptyState />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header & Breadcrumbs */}
      <div className="space-y-2">
        <nav aria-label="Đường dẫn trang" className="flex items-center gap-2 text-xs sm:text-sm text-on-surface-variant">
          <Link href="/" className="hover:text-primary transition-colors">
            Trang chủ
          </Link>
          <span className="material-symbols-outlined text-[14px]">chevron_right</span>
          <Link href="/cua-hang" className="hover:text-primary transition-colors">
            Cửa hàng
          </Link>
          <span className="material-symbols-outlined text-[14px]">chevron_right</span>
          <span className="text-on-surface font-medium">Giỏ hàng</span>
        </nav>

        <h1 className="font-serif text-2xl sm:text-3xl md:text-4xl font-bold text-on-surface tracking-tight">
          Giỏ Hàng Vật Phẩm
        </h1>
      </div>

      {errorMsg && (
        <div className="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 text-xs sm:text-sm text-rose-800 dark:text-rose-200">
          {errorMsg}
        </div>
      )}

      {/* Main Grid: Item List (left) + Summary (right) */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        {/* Cart Items List */}
        <div className="lg:col-span-8 space-y-4">
          {previewData?.items.map((item) => (
            <CartItemCard
              key={item.product_id}
              item={item}
              onUpdateQuantity={updateQuantity}
              onRemove={removeItem}
            />
          ))}

          {/* Skeleton overlay while re-previewing */}
          {isLoading && !previewData && (
            <div className="space-y-4 animate-pulse">
              <div className="h-28 bg-surface-container-high rounded-3xl" />
              <div className="h-28 bg-surface-container-high rounded-3xl" />
            </div>
          )}
        </div>

        {/* Order Summary Sidebar */}
        <div className="lg:col-span-4">
          <CartSummary
            formattedSubtotal={previewData?.formatted_subtotal || "0 ₫"}
            totalQuantity={previewData?.total_quantity || 0}
            isValid={previewData?.is_valid ?? true}
            onClearCart={clearCart}
          />
        </div>
      </div>
    </div>
  );
}
