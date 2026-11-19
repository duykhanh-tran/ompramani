import React from "react";
import { ProductSummary } from "@/types/shop";
import { ProductCard } from "./ProductCard";

interface ProductGridProps {
  products: ProductSummary[];
  isLoading?: boolean;
}

export function ProductGrid({ products, isLoading }: ProductGridProps) {
  if (isLoading) {
    return (
      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        {Array.from({ length: 8 }).map((_, i) => (
          <div
            key={`product-skeleton-${i}`}
            className="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 overflow-hidden animate-pulse flex flex-col h-full"
          >
            <div className="aspect-square w-full bg-surface-container-high" />
            <div className="p-4 space-y-3 flex-1 flex flex-col">
              <div className="h-5 bg-surface-container-high rounded-md w-3/4" />
              <div className="h-3.5 bg-surface-container-high rounded-md w-full" />
              <div className="h-3.5 bg-surface-container-high rounded-md w-2/3" />
              <div className="mt-auto pt-3 border-t border-outline-variant/30 flex justify-between items-center">
                <div className="h-4 bg-surface-container-high rounded-md w-20" />
                <div className="h-6 bg-surface-container-high rounded-md w-16" />
              </div>
            </div>
          </div>
        ))}
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
      {products.map((product) => (
        <ProductCard key={product.public_id} product={product} />
      ))}
    </div>
  );
}
