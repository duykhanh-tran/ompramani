"use client";

import React, { useState, useEffect, useRef, useCallback } from "react";
import { useSearchParams, useRouter, usePathname } from "next/navigation";
import Image from "next/image";
import {
  ProductCategory,
  ProductSortOption,
  ProductSummary,
} from "@/types/shop";
import { getProductCategories, getProducts } from "@/lib/api/shop";
import { ShopHeader } from "@/components/shop/ShopHeader";
import { ProductSearchBar } from "@/components/shop/ProductSearchBar";
import { ProductCategoryFilter } from "@/components/shop/ProductCategoryFilter";
import { ProductSortSelector } from "@/components/shop/ProductSortSelector";
import { ProductGrid } from "@/components/shop/ProductGrid";
import { ProductPagination } from "@/components/shop/ProductPagination";
import { ProductEmptyState } from "@/components/shop/ProductEmptyState";

export function ShopCatalogClient() {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();

  // URL state
  const paramQ = searchParams.get("q") || "";
  const paramCategory = searchParams.get("category") || "all";
  const paramSort = (searchParams.get("sort") as ProductSortOption) || "latest";
  const paramPage = parseInt(searchParams.get("page") || "1", 10) || 1;

  // Local state
  const [categories, setCategories] = useState<ProductCategory[]>([]);
  const [products, setProducts] = useState<ProductSummary[]>([]);
  const [totalCount, setTotalCount] = useState<number>(0);
  const [currentPage, setCurrentPage] = useState<number>(paramPage);
  const [lastPage, setLastPage] = useState<number>(1);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [hasError, setHasError] = useState<boolean>(false);

  // Request counter to avoid race conditions
  const requestIdRef = useRef<number>(0);

  // Helper to sync state to URL
  const updateUrl = useCallback(
    (newParams: {
      q?: string;
      category?: string;
      sort?: ProductSortOption;
      page?: number;
    }) => {
      const sp = new URLSearchParams();

      const q = newParams.q !== undefined ? newParams.q : paramQ;
      const cat = newParams.category !== undefined ? newParams.category : paramCategory;
      const sort = newParams.sort !== undefined ? newParams.sort : paramSort;
      const page = newParams.page !== undefined ? newParams.page : currentPage;

      if (q && q.trim()) sp.set("q", q.trim());
      if (cat && cat !== "all") sp.set("category", cat);
      if (sort && sort !== "latest") sp.set("sort", sort);
      if (page && page > 1) sp.set("page", page.toString());

      const qs = sp.toString();
      router.push(`${pathname}${qs ? `?${qs}` : ""}`, { scroll: false });
    },
    [router, pathname, paramQ, paramCategory, paramSort, currentPage]
  );

  // Load categories once on mount
  useEffect(() => {
    let isMounted = true;
    getProductCategories()
      .then((cats) => {
        if (isMounted) {
          setCategories(cats);
        }
      })
      .catch((err) => {
        console.error("Failed to load shop categories:", err);
      });

    return () => {
      isMounted = false;
    };
  }, []);

  // Fetch products whenever search params change
  useEffect(() => {
    const currentReqId = ++requestIdRef.current;

    Promise.resolve().then(() => {
      if (currentReqId === requestIdRef.current) {
        setIsLoading(true);
        setHasError(false);
      }
    });

    getProducts({
      q: paramQ,
      category: paramCategory !== "all" ? paramCategory : undefined,
      sort: paramSort,
      page: paramPage,
      per_page: 12,
    })
      .then((res) => {
        if (currentReqId === requestIdRef.current) {
          setProducts(res.data || []);
          setTotalCount(res.meta?.total || 0);
          setCurrentPage(res.meta?.current_page || 1);
          setLastPage(res.meta?.last_page || 1);
          setIsLoading(false);
        }
      })
      .catch((err) => {
        if (currentReqId === requestIdRef.current) {
          console.error("Failed to fetch products:", err);
          setProducts([]);
          setHasError(true);
          setIsLoading(false);
        }
      });
  }, [paramQ, paramCategory, paramSort, paramPage]);

  const hasActiveFilters = Boolean(
    paramQ.trim() || (paramCategory && paramCategory !== "all")
  );

  const handleClearFilters = () => {
    updateUrl({ q: "", category: "all", page: 1 });
  };

  return (
    <div className="w-full">
      {/* Sacred Buddhist Shop Hero Banner - Full Width & Majestic Height */}
      <section className="relative w-full overflow-hidden min-h-[420px] sm:min-h-[500px] md:min-h-[560px] lg:min-h-[600px] flex items-center border-b border-outline-variant/30 group">
        {/* 1. Ảnh nằm dưới cùng (tràn mép 100vw) */}
        <Image
          src="/images/shop-banner.jpg"
          alt="Cửa Hàng Vật Phẩm Phật Giáo"
          fill
          priority
          sizes="100vw"
          className="object-cover object-[center_38%] group-hover:scale-102 transition-transform duration-700"
        />

        {/* Lớp gradient tự nhiên loang dần từ trái sang phải để tôn chữ, không tạo viền hộp */}
        <div className="absolute inset-0 bg-gradient-to-r from-stone-950/80 via-stone-900/45 via-45% to-transparent pointer-events-none" />
        <div className="absolute inset-0 bg-gradient-to-t from-stone-950/45 via-transparent to-transparent pointer-events-none" />

        {/* Căn lề nội dung theo khung chuẩn 1360px của trang */}
        <div className="max-w-[1360px] mx-auto px-4 sm:px-6 w-full py-12 sm:py-16 md:py-20 relative z-10">
          {/* Chữ nằm trực tiếp trên banner (bỏ hoàn toàn background của chữ) */}
          <div className="max-w-xl sm:max-w-2xl space-y-4">

            <h1 className="font-headline-xl text-3xl sm:text-5xl lg:text-6xl font-bold text-white tracking-tight font-serif drop-shadow-[0_2px_8px_rgba(0,0,0,0.7)] leading-[1.15]">
              Cửa Hàng Vật Phẩm Phật Giáo
            </h1>
            <p className="font-body-md text-base sm:text-lg text-white/90 leading-relaxed drop-shadow-[0_1px_4px_rgba(0,0,0,0.8)] max-w-xl font-normal">
              Tuyển chọn tràng hạt, chuông mõ pháp khí, kinh sách và pháp phục mộc mạc. Phụng sự đại chúng trên bước đường tinh tấn, thanh tịnh.
            </p>


          </div>
        </div>
      </section>

      {/* Nội dung danh mục vật phẩm */}
      <div className="max-w-[1360px] mx-auto px-4 sm:px-6 py-8 space-y-6">
        {/* Filter and Search controls */}
        <div className="space-y-4 bg-surface-container-low/40 p-4 sm:p-5 rounded-3xl border border-outline-variant/30">
          <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div className="flex-1">
              <ProductSearchBar
                value={paramQ}
                onChange={(newQ) => updateUrl({ q: newQ, page: 1 })}
              />
            </div>
            <ProductSortSelector
              currentSort={paramSort}
              onSortChange={(newSort) => updateUrl({ sort: newSort, page: 1 })}
            />
          </div>

          <ProductCategoryFilter
            categories={categories}
            selectedCategory={paramCategory}
            onSelectCategory={(slug) => updateUrl({ category: slug, page: 1 })}
          />
        </div>

        {/* Catalog stats */}
        <div className="flex items-center justify-between text-xs text-on-surface-variant px-1">
          <div>
            {!isLoading && (
              <span>
                Hiển thị <strong className="text-on-surface font-semibold">{products.length}</strong> /{" "}
                {totalCount} vật phẩm
              </span>
            )}
          </div>
        </div>

        {/* Products Grid or Empty state */}
        {hasError ? (
          <div className="p-8 text-center text-error bg-error-container/20 rounded-2xl border border-error/30">
            Có lỗi xảy ra khi tải danh mục vật phẩm. Vui lòng thử lại sau.
          </div>
        ) : !isLoading && products.length === 0 ? (
          <ProductEmptyState
            hasFilters={hasActiveFilters}
            onClearFilters={handleClearFilters}
          />
        ) : (
          <ProductGrid products={products} isLoading={isLoading} />
        )}

        {/* Pagination */}
        {!isLoading && lastPage > 1 && (
          <ProductPagination
            currentPage={currentPage}
            lastPage={lastPage}
            onPageChange={(page) => {
              updateUrl({ page });
              window.scrollTo({ top: 0, behavior: "smooth" });
            }}
          />
        )}
      </div>
    </div>
  );
}
