"use client";

import React, { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { ProductDetail, ProductSummary } from "@/types/shop";
import { useCart } from "@/context/CartContext";
import { ProductGallery } from "./ProductGallery";
import { QuantitySelector } from "./QuantitySelector";
import { ProductCard } from "./ProductCard";

interface ProductDetailClientProps {
  product: ProductDetail;
  relatedProducts?: ProductSummary[];
}

export function ProductDetailClient({
  product,
  relatedProducts = [],
}: ProductDetailClientProps) {
  const router = useRouter();
  const { addItem } = useCart();
  const [quantity, setQuantity] = useState(1);
  const [feedback, setFeedback] = useState<string | null>(null);

  const isTracked = product.inventory_mode === "tracked";
  const stockCount = isTracked ? product.stock_quantity ?? 0 : null;
  const isOutOfStock = isTracked && (stockCount === 0 || !product.is_available);
  const maxAllowed = isTracked && stockCount ? Math.min(99, stockCount) : 99;

  const handleAddToCart = () => {
    if (isOutOfStock) return;

    const result = addItem(product.public_id, quantity);
    setFeedback(result.message);

    // Auto-hide feedback after 4 seconds
    setTimeout(() => {
      setFeedback(null);
    }, 4000);
  };

  const handleBuyNow = () => {
    if (isOutOfStock) return;

    addItem(product.public_id, quantity);
    router.push("/thanh-toan");
  };

  return (
    <div className="space-y-8">
      {/* Breadcrumb Navigation */}
      <nav aria-label="Đường dẫn trang" className="flex items-center gap-2 text-xs sm:text-sm text-on-surface-variant">
        <Link href="/" className="hover:text-primary transition-colors">
          Trang chủ
        </Link>
        <span className="material-symbols-outlined text-[14px]">chevron_right</span>
        <Link href="/cua-hang" className="hover:text-primary transition-colors">
          Cửa hàng
        </Link>
        {product.category && (
          <>
            <span className="material-symbols-outlined text-[14px]">chevron_right</span>
            <Link
              href={`/cua-hang?category=${product.category.slug}`}
              className="hover:text-primary transition-colors"
            >
              {product.category.name}
            </Link>
          </>
        )}
        <span className="material-symbols-outlined text-[14px]">chevron_right</span>
        <span className="text-on-surface font-medium truncate max-w-[200px] sm:max-w-[300px]">
          {product.name}
        </span>
      </nav>

      {/* Main Product Layout: Image takes the majority of space (7/12 cols) */}
      <div className="grid grid-cols-1 md:grid-cols-12 gap-8 lg:gap-12 items-start">
        {/* Left Column: Image Gallery (Dominant width: 7/12) */}
        <div className="md:col-span-7 lg:col-span-7">
          <ProductGallery
            images={product.gallery_images && product.gallery_images.length > 0
              ? product.gallery_images
              : product.cover_image ? [product.cover_image] : []}
            productName={product.name}
          />
        </div>

        {/* Right Column: Information, Pricing & Action Buttons (5/12) */}
        <div className="md:col-span-5 lg:col-span-5 space-y-6">
          <div className="space-y-2">
            {/* Category tag removed per user request */}
            <h1 className="font-serif text-2xl sm:text-3xl lg:text-4xl font-bold text-on-surface tracking-tight leading-snug">
              {product.name}
            </h1>
            {product.sku && (
              <p className="text-xs font-mono text-on-surface-variant">
                Mã vật phẩm (SKU): {product.sku}
              </p>
            )}
          </div>

          {/* Pricing & Stock Card */}
          <div className="p-5 rounded-3xl bg-surface-container-low/60 border border-outline-variant/30 space-y-3">
            <div className="flex items-baseline gap-3">
              <span className="text-xs text-on-surface-variant font-medium">Giá phát hành:</span>
              <span className="font-serif font-bold text-2xl sm:text-3xl text-amber-700 dark:text-amber-400">
                {product.formatted_price || "Tùy hỷ phát tâm"}
              </span>
            </div>

            <div className="flex items-center gap-2 pt-1 border-t border-outline-variant/20 text-xs">
              <span className="text-on-surface-variant font-medium">Tình trạng:</span>
              {isOutOfStock ? (
                <span className="inline-flex items-center gap-1 font-semibold text-rose-600 dark:text-rose-400">
                  <span className="w-2 h-2 rounded-full bg-rose-600" />
                  Tạm hết hàng
                </span>
              ) : (
                <span className="inline-flex items-center gap-1 font-semibold text-emerald-600 dark:text-emerald-400">
                  <span className="w-2 h-2 rounded-full bg-emerald-600" />
                  {isTracked && stockCount ? `Còn hàng (sẵn sàng ${stockCount} vật phẩm)` : "Sẵn có"}
                </span>
              )}
            </div>
          </div>

          {/* Short description if available */}
          {product.short_description && (
            <p className="text-sm text-on-surface-variant leading-relaxed">
              {product.short_description}
            </p>
          )}

          {/* Quantity and Actions */}
          <div className="space-y-4 pt-2">
            <div className="flex items-center gap-3">
              <span className="text-xs font-medium text-on-surface-variant">Số lượng:</span>
              <QuantitySelector
                quantity={quantity}
                max={maxAllowed}
                onChange={setQuantity}
                disabled={isOutOfStock}
              />
            </div>

            {/* Both Action Buttons on the same row with clear visual distinction */}
            <div className="flex items-center gap-3 pt-1 flex-wrap sm:flex-nowrap">
              {/* Nút 1: Thêm vào giỏ hàng (Phong cách Tông Trầm Đồng Trang Nghiêm) */}
              <button
                type="button"
                disabled={isOutOfStock}
                onClick={handleAddToCart}
                className="h-[40px] px-5 rounded-[10px] text-xs sm:text-sm font-semibold bg-amber-800 hover:bg-amber-900 text-white shadow-xs hover:shadow-sm transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-40 disabled:pointer-events-none"
              >
                <span className="material-symbols-outlined text-[18px]">add_shopping_cart</span>
                <span>{isOutOfStock ? "Tạm hết hàng" : "Thêm vào giỏ hàng"}</span>
              </button>

              {/* Nút 2: Mua ngay (Sử dụng CSS từ Uiverse.io theo yêu cầu) */}
              <button
                type="button"
                disabled={isOutOfStock}
                onClick={handleBuyNow}
                className="Btn disabled:opacity-40 disabled:pointer-events-none"
                aria-label="Mua ngay"
              >
                <svg className="svgIcon" viewBox="0 0 16 16" fill="currentColor">
                  <path d="M0 2.5A.5.5 0 0 1 .5 2H2a.5.5 0 0 1 .485.379L2.89 4H14.5a.5.5 0 0 1 .485.621l-1.5 6A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.485-.379L1.61 3H.5a.5.5 0 0 1-.5-.5zM3.14 5l1.25 5h8.22l1.25-5H3.14zM5 13a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm7 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2z" />
                </svg>
                <span>Mua ngay</span>
              </button>
            </div>

            {/* Feedback notification toast */}
            {feedback && (
              <div
                role="status"
                className="p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800 text-xs sm:text-sm text-emerald-800 dark:text-emerald-200 flex items-center justify-between gap-3 animate-in fade-in"
              >
                <div className="flex items-center gap-2">
                  <span className="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-[20px]">
                    check_circle
                  </span>
                  <span>{feedback}</span>
                </div>
                <Link
                  href="/gio-hang"
                  className="font-semibold underline hover:no-underline text-emerald-900 dark:text-emerald-100 shrink-0"
                >
                  Xem giỏ hàng →
                </Link>
              </div>
            )}
          </div>

          {/* Buddhist Ethical Shopping Reminder Banner */}
          <div className="p-4 rounded-2xl bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200/40 dark:border-amber-800/30 flex items-start gap-3">
            <span className="material-symbols-outlined text-amber-600 dark:text-amber-400 text-[20px] shrink-0 mt-0.5">
              spa
            </span>
            <div className="text-xs text-on-surface-variant leading-relaxed">
              <strong className="font-semibold text-on-surface">Phương tiện tu học: </strong>
              Mỗi vật phẩm được tuyển chọn để trợ duyên cho việc huân tu chánh niệm, thanh tịnh thân tâm. OmPraMani luôn giữ tâm chân thành, minh bạch nguồn gốc và không gắn mác thần thánh hóa.
            </div>
          </div>
        </div>
      </div>

      {/* Detailed Description Section */}
      {product.description && (
        <section className="pt-8 border-t border-outline-variant/30 space-y-4">
          <h2 className="font-serif text-xl sm:text-2xl font-bold text-on-surface">
            Mô tả &amp; Hướng dẫn sử dụng
          </h2>
          <div className="p-6 rounded-3xl bg-surface-container-lowest border border-outline-variant/40 text-sm sm:text-base text-on-surface-variant leading-relaxed whitespace-pre-line shadow-xs">
            {product.description}
          </div>
        </section>
      )}

      {/* Related Products Section (Đồ vật liên quan tới sản phẩm) */}
      {relatedProducts && relatedProducts.length > 0 && (
        <section className="pt-10 border-t border-outline-variant/30 space-y-6">
          <div className="flex items-center justify-between">
            <div>
              <span className="font-label-caps text-xs text-primary uppercase tracking-wider block font-semibold mb-1">
                Phương tiện trợ duyên
              </span>
              <h2 className="font-serif text-xl sm:text-2xl font-bold text-on-surface">
                Vật Phẩm Liên Quan
              </h2>
            </div>
            <Link
              href="/cua-hang"
              className="text-xs sm:text-sm font-medium text-primary hover:underline flex items-center gap-1"
            >
              <span>Xem tất cả</span>
              <span className="material-symbols-outlined text-[16px]">chevron_right</span>
            </Link>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            {relatedProducts.slice(0, 4).map((item) => (
              <ProductCard key={item.public_id} product={item} />
            ))}
          </div>
        </section>
      )}
    </div>
  );
}
