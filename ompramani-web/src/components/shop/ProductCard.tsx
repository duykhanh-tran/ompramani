import React from "react";
import Link from "next/link";
import { ProductSummary } from "@/types/shop";
import { resolveMediaUrl } from "@/lib/utils/media";
import { SafeImage } from "@/components/common/SafeImage";

interface ProductCardProps {
  product: ProductSummary;
}

export function ProductCard({ product }: ProductCardProps) {
  const productUrl = `/cua-hang/${product.slug}`;
  const resolvedCover = resolveMediaUrl(product.cover_image);

  return (
    <Link
      href={productUrl}
      className="product-card group relative bg-surface-container-lowest rounded-2xl border border-outline-variant/40 hover:border-primary/50 shadow-xs hover:shadow-lg transition-all duration-300 flex flex-col h-full overflow-hidden text-left w-full"
      aria-label={`Xem chi tiết vật phẩm: ${product.name}`}
    >
      {/* Top Image Container */}
      <div className="relative aspect-square w-full bg-surface-container-high overflow-hidden">
        <SafeImage
          src={resolvedCover}
          alt={`Ảnh vật phẩm: ${product.name}`}
          fill
          className="object-cover transition-transform duration-500 ease-out group-hover:scale-105"
          sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 25vw"
          fallback={
            <div className="w-full h-full flex flex-col items-center justify-center text-primary/40 p-4">
              <span className="material-symbols-outlined text-[48px]">
                self_improvement
              </span>
              <span className="text-xs text-on-surface-variant/70 italic mt-1 text-center">
                Pháp Bảo Phật Giáo
              </span>
            </div>
          }
        />

        {/* Top-Left Category Badge */}
        {product.category?.name && (
          <span className="absolute top-2.5 left-2.5 z-10 bg-black/75 backdrop-blur-xs text-white text-[11px] font-medium px-2.5 py-1 rounded-md shadow-xs">
            {product.category.name}
          </span>
        )}

        {/* Top-Right Stock Status Badge */}
        <span
          className={`absolute top-2.5 right-2.5 z-10 text-white text-[11px] font-medium px-2.5 py-0.5 rounded-full shadow-xs ${
            product.is_available ? "bg-[#00ac7c]" : "bg-neutral-600"
          }`}
        >
          {product.is_available ? "Còn hàng" : "Hết hàng"}
        </span>
      </div>

      {/* Body Content */}
      <div className="p-4 pt-3.5 pb-4 flex flex-col flex-1 relative overflow-hidden min-h-[152px]">
        {/* Khối Giá phát hành: Nằm ở trên cùng, hiện ra mượt mà khi hover */}
        <div className="card-price-hover absolute top-3.5 left-4 right-4 z-10 pointer-events-none">
          <span className="text-[11px] text-on-surface-variant block font-medium leading-none mb-1">
            Giá phát hành
          </span>
          <span className="text-base sm:text-lg font-bold text-amber-600 dark:text-amber-400 font-serif leading-none tracking-tight">
            {product.formatted_price || "Tùy hỷ phát tâm"}
          </span>
        </div>

        {/* Khối Tiêu đề & Mô tả: Tụt xuống khi di chuột vào để giá hiện lên */}
        <div className="card-text-slide flex flex-col">
          <h3
            className="font-serif font-bold text-[15px] sm:text-base text-on-surface line-clamp-2 leading-snug group-hover:text-primary transition-colors duration-200"
            title={product.name}
          >
            {product.name}
          </h3>

          <p className="text-xs text-on-surface-variant line-clamp-2 mt-1.5 leading-relaxed">
            {product.short_description || product.name}
          </p>
        </div>
      </div>
    </Link>
  );
}
