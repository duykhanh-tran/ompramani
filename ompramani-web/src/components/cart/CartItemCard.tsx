"use client";

import React from "react";
import Link from "next/link";
import Image from "next/image";
import { CartPreviewItem } from "@/types/shop";
import { QuantitySelector } from "@/components/shop/QuantitySelector";

interface CartItemCardProps {
  item: CartPreviewItem;
  onUpdateQuantity: (productId: string, quantity: number) => void;
  onRemove: (productId: string) => void;
}

export function CartItemCard({
  item,
  onUpdateQuantity,
  onRemove,
}: CartItemCardProps) {
  const isAvailable = item.is_available;
  const productHref = item.slug ? `/cua-hang/${item.slug}` : null;

  return (
    <article className="p-4 sm:p-5 rounded-3xl bg-surface-container-lowest border border-outline-variant/40 shadow-xs flex flex-col sm:flex-row items-start sm:items-center gap-4 transition-all">
      {/* Thumbnail */}
      <div className="relative w-20 h-20 sm:w-24 sm:h-24 rounded-2xl overflow-hidden bg-surface-container-low border border-outline-variant/30 shrink-0">
        {item.cover_image ? (
          <Image
            src={item.cover_image}
            alt={`Ảnh vật phẩm: ${item.name}`}
            fill
            className="object-cover"
            sizes="96px"
          />
        ) : (
          <div className="w-full h-full flex items-center justify-center text-primary/40 bg-surface-container-high/40">
            <span className="material-symbols-outlined text-[32px]">
              self_improvement
            </span>
          </div>
        )}
      </div>

      {/* Details */}
      <div className="flex-1 min-w-0 space-y-1.5">
        <h3 className="font-serif font-bold text-base text-on-surface leading-snug">
          {productHref ? (
            <Link
              href={productHref}
              className="hover:text-primary transition-colors focus:outline-hidden"
            >
              {item.name}
            </Link>
          ) : (
            <span>{item.name}</span>
          )}
        </h3>

        <div className="flex flex-wrap items-center gap-2 text-xs">
          <span className="text-on-surface-variant font-medium">Đơn giá:</span>
          <span className="font-medium text-on-surface font-mono">
            {item.formatted_unit_price}
          </span>
        </div>

        {/* Status and Error Badges */}
        {!isAvailable && (
          <div className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
            <span className="material-symbols-outlined text-[14px]">error</span>
            <span>{item.error_message || "Sản phẩm không còn khả dụng"}</span>
          </div>
        )}
      </div>

      {/* Quantity & Line Total */}
      <div className="flex sm:flex-col items-center sm:items-end justify-between w-full sm:w-auto gap-3 pt-3 sm:pt-0 border-t sm:border-t-0 border-outline-variant/30 shrink-0">
        <div className="flex items-center gap-3">
          <QuantitySelector
            quantity={item.quantity}
            max={item.available_stock ?? 99}
            onChange={(newQty) => onUpdateQuantity(item.product_id, newQty)}
          />

          <button
            type="button"
            onClick={() => onRemove(item.product_id)}
            className="p-2 rounded-xl text-on-surface-variant hover:text-error hover:bg-error-container/20 transition-colors cursor-pointer focus:outline-hidden focus:ring-1 focus:ring-error"
            aria-label={`Xóa ${item.name} khỏi giỏ hàng`}
            title="Xóa vật phẩm này"
          >
            <span className="material-symbols-outlined text-[20px]">delete</span>
          </button>
        </div>

        <div className="text-right">
          <span className="text-[11px] text-on-surface-variant block hidden sm:block">Thành tiền</span>
          <span className="font-serif font-bold text-base sm:text-lg text-amber-700 dark:text-amber-400">
            {item.formatted_line_subtotal}
          </span>
        </div>
      </div>
    </article>
  );
}
