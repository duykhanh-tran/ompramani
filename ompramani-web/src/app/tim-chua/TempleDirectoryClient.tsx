"use client";

import React, { useState, useEffect, useRef, useCallback } from "react";
import { useRouter, useSearchParams, usePathname } from "next/navigation";
import Image from "next/image";
import {
  TempleFilterFacets,
  TempleSummary,
  TempleType,
} from "@/types/temple";
import { getTempleFilters, getTemples } from "@/lib/api/temples";
import { TempleSearchBar } from "@/components/temples/TempleSearchBar";
import { TempleCard } from "@/components/temples/TempleCard";
import { TempleFilterSidebar } from "@/components/temples/TempleFilterSidebar";
import { TempleFilterDrawer } from "@/components/temples/TempleFilterDrawer";
import { TempleActiveFilterChips } from "@/components/temples/TempleActiveFilterChips";
import { TemplePagination } from "@/components/temples/TemplePagination";

interface TempleDirectoryClientProps {
  initialFacets?: TempleFilterFacets | null;
}

export function TempleDirectoryClient({
  initialFacets = null,
}: TempleDirectoryClientProps) {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();

  // URL Query Parameters
  const paramQ = searchParams.get("q") || "";
  const paramType = (searchParams.get("type") as TempleType) || undefined;
  const paramProvince = searchParams.get("province") || undefined;
  const paramCountry = searchParams.get("country") || undefined;
  const paramTradition = searchParams.get("tradition") || undefined;
  const paramSort =
    (searchParams.get("sort") as
      | "relevance"
      | "name_asc"
      | "name_desc"
      | "newest") || "relevance";
  const paramPage = parseInt(searchParams.get("page") || "1", 10) || 1;

  // Local State
  const [facets, setFacets] = useState<TempleFilterFacets | null>(initialFacets);
  const [temples, setTemples] = useState<TempleSummary[]>([]);
  const [totalCount, setTotalCount] = useState<number>(0);
  const [currentPage, setCurrentPage] = useState<number>(paramPage);
  const [lastPage, setLastPage] = useState<number>(1);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [hasError, setHasError] = useState<boolean>(false);
  const [isMobileDrawerOpen, setIsMobileDrawerOpen] = useState<boolean>(false);

  // Stale-request guard counter
  const requestIdRef = useRef<number>(0);

  // Update URL helper
  const updateUrl = useCallback(
    (newParams: {
      q?: string;
      type?: string;
      province?: string;
      country?: string;
      tradition?: string;
      sort?: string;
      page?: number;
    }) => {
      const sp = new URLSearchParams();

      const q = newParams.q !== undefined ? newParams.q : paramQ;
      const type = newParams.type !== undefined ? newParams.type : paramType;
      const province =
        newParams.province !== undefined ? newParams.province : paramProvince;
      const country =
        newParams.country !== undefined ? newParams.country : paramCountry;
      const tradition =
        newParams.tradition !== undefined ? newParams.tradition : paramTradition;
      const sort = newParams.sort !== undefined ? newParams.sort : paramSort;
      const page = newParams.page !== undefined ? newParams.page : currentPage;

      if (q && q.trim()) sp.set("q", q.trim());
      if (type) sp.set("type", type);
      if (province) sp.set("province", province);
      if (country) sp.set("country", country);
      if (tradition) sp.set("tradition", tradition);
      if (sort && sort !== "relevance") sp.set("sort", sort);
      if (page && page > 1) sp.set("page", page.toString());

      const qs = sp.toString();
      router.push(`${pathname}${qs ? `?${qs}` : ""}`);
    },
    [router, pathname, paramQ, paramType, paramProvince, paramCountry, paramTradition, paramSort, currentPage]
  );

  // Load Facets once
  useEffect(() => {
    if (!facets) {
      getTempleFilters().then((f) => {
        if (f) setFacets(f);
      });
    }
  }, [facets]);

  // Main Data Fetcher
  const fetchTemples = useCallback(async () => {
    const currentReqId = ++requestIdRef.current;
    setIsLoading(true);
    setHasError(false);

    try {
      const res = await getTemples({
        q: paramQ || undefined,
        type: paramType,
        province: paramProvince,
        country: paramCountry,
        tradition: paramTradition,
        sort: paramSort,
        page: paramPage,
        per_page: 12,
      });

      // Ignore stale responses
      if (currentReqId !== requestIdRef.current) {
        return;
      }

      setTemples(res.data || []);
      setTotalCount(res.meta?.total || 0);
      setCurrentPage(res.meta?.current_page || paramPage);
      setLastPage(res.meta?.last_page || 1);
    } catch (err) {
      if (currentReqId === requestIdRef.current) {
        console.error("Failed to fetch temples:", err);
        setHasError(true);
      }
    } finally {
      if (currentReqId === requestIdRef.current) {
        setIsLoading(false);
      }
    }
  }, [paramQ, paramType, paramProvince, paramCountry, paramTradition, paramSort, paramPage]);

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    fetchTemples();
  }, [fetchTemples]);

  // Handlers for search & filters
  const handleSearchChange = (query: string) => {
    updateUrl({ q: query, page: 1 });
  };

  const handleTypeChange = (type?: TempleType) => {
    updateUrl({ type: type || "", page: 1 });
  };

  const handleProvinceChange = (province?: string) => {
    updateUrl({ province: province || "", page: 1 });
  };

  const handleTraditionChange = (tradition?: string) => {
    updateUrl({ tradition: tradition || "", page: 1 });
  };

  const handleSortChange = (
    sort?: "relevance" | "name_asc" | "name_desc" | "newest"
  ) => {
    updateUrl({ sort: sort || "relevance", page: 1 });
  };

  const handlePageChange = (page: number) => {
    updateUrl({ page });
    // Scroll to top of results smoothly
    window.scrollTo({ top: 120, behavior: "smooth" });
  };

  const handleClearAll = () => {
    router.push(pathname);
  };

  // Active filters count for mobile drawer button badge
  const activeFiltersCount = [
    Boolean(paramType),
    Boolean(paramProvince),
    Boolean(paramCountry),
    Boolean(paramTradition),
    Boolean(paramSort && paramSort !== "relevance"),
  ].filter(Boolean).length;

  return (
    <div className="w-full">
      {/* Sacred Temple Directory Hero Banner - Full Width & Majestic Height */}
      <section className="relative w-full overflow-hidden min-h-[420px] sm:min-h-[500px] md:min-h-[560px] lg:min-h-[600px] flex items-center border-b border-outline-variant/30 group">
        {/* 1. Ảnh nằm dưới cùng (tràn mép 100vw) */}
        <Image
          src="/images/temple-directory-banner.jpg"
          alt="Chùa Việt Nam"
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
            <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-amber-300 shadow-xs">
              <span className="w-2 h-2 rounded-full bg-amber-400 animate-pulse" />
              <span className="font-label-caps text-xs uppercase tracking-wider font-semibold">
                Thư Viện Địa Điểm Phật Giáo
              </span>
            </div>
            <h1 className="font-headline-xl text-3xl sm:text-5xl lg:text-6xl font-bold text-white tracking-tight font-serif drop-shadow-[0_2px_8px_rgba(0,0,0,0.7)] leading-[1.15]">
              Chùa Việt Nam
            </h1>
            <p className="font-body-md text-base sm:text-lg text-white/90 leading-relaxed drop-shadow-[0_1px_4px_rgba(0,0,0,0.8)] max-w-xl font-normal">
              Khám phá danh lam tự viện, thiền viện, tịnh xá và các cơ sở Phật giáo có thông tin được biên tập và xác minh nguồn cội chính thống.
            </p>


          </div>
        </div>
      </section>

      {/* Main Layout: Filter Sidebar + Content */}
      <div className="max-w-[1360px] mx-auto px-4 sm:px-6 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
          {/* Desktop Sidebar (3 cols) */}
          <div className="hidden lg:block lg:col-span-3 sticky top-20">
            <TempleFilterSidebar
              facets={facets}
              selectedType={paramType}
              selectedProvince={paramProvince}
              selectedTradition={paramTradition}
              selectedSort={paramSort}
              onTypeChange={handleTypeChange}
              onProvinceChange={handleProvinceChange}
              onTraditionChange={handleTraditionChange}
              onSortChange={handleSortChange}
              onReset={handleClearAll}
              hasActiveFilters={Boolean(
                paramType || paramProvince || paramTradition || paramSort !== "relevance"
              )}
            />
          </div>

          {/* Content Area (9 cols on desktop) */}
          <main className="lg:col-span-9 space-y-6">
            {/* Mobile Filter Button & Active Summary */}
            <div className="flex items-center justify-between gap-4 flex-wrap pb-2 border-b border-outline-variant/30">
              {/* Live Result Count */}
              <div
                aria-live="polite"
                className="font-label-md text-label-md text-on-surface-variant font-medium"
              >
                {isLoading ? (
                  <span>Đang tìm kiếm...</span>
                ) : (
                  <span>
                    Tìm thấy{" "}
                    <strong className="text-on-surface font-semibold">
                      {totalCount}
                    </strong>{" "}
                    địa điểm
                  </span>
                )}
              </div>

              {/* Mobile Drawer Trigger Button */}
              <button
                type="button"
                onClick={() => setIsMobileDrawerOpen(true)}
                className="lg:hidden inline-flex items-center gap-1.5 px-3.5 py-2 bg-surface-container-high rounded-xl text-on-surface font-label-md text-label-md font-medium hover:bg-surface-container-highest transition-colors shadow-2xs"
              >
                <span className="material-symbols-outlined text-[18px] text-primary">
                  tune
                </span>
                <span>Bộ lọc</span>
                {activeFiltersCount > 0 && (
                  <span className="w-5 h-5 rounded-full bg-primary text-on-primary text-[11px] font-bold flex items-center justify-center ml-1">
                    {activeFiltersCount}
                  </span>
                )}
              </button>
            </div>

            {/* Active Filter Chips */}
            <TempleActiveFilterChips
              q={paramQ}
              type={paramType}
              province={paramProvince}
              country={paramCountry}
              tradition={paramTradition}
              facets={facets}
              onRemoveQuery={() => updateUrl({ q: "", page: 1 })}
              onRemoveType={() => updateUrl({ type: "", page: 1 })}
              onRemoveProvince={() => updateUrl({ province: "", page: 1 })}
              onRemoveCountry={() => updateUrl({ country: "", page: 1 })}
              onRemoveTradition={() => updateUrl({ tradition: "", page: 1 })}
              onClearAll={handleClearAll}
            />

            {/* Results State Management */}
            {hasError ? (
              /* Error State */
              <div className="p-12 text-center bg-surface-container-lowest rounded-2xl border border-outline-variant/40 space-y-4">
                <span className="material-symbols-outlined text-[48px] text-error/60">
                  cloud_off
                </span>
                <h3 className="font-headline-md text-headline-md font-semibold text-on-surface">
                  Chưa thể tải danh sách địa điểm
                </h3>
                <p className="font-body-sm text-body-sm text-on-surface-variant max-w-md mx-auto">
                  Đã xảy ra lỗi kết nối đến máy chủ. Vui lòng kiểm tra lại đường truyền và thử lại.
                </p>
                <button
                  type="button"
                  onClick={fetchTemples}
                  className="px-4 py-2 bg-primary text-on-primary font-label-md text-label-md font-medium rounded-xl hover:bg-primary/90 transition-colors shadow-xs"
                >
                  Thử lại
                </button>
              </div>
            ) : isLoading ? (
              /* Loading Skeleton Grid */
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 animate-pulse">
                {[...Array(6)].map((_, i) => (
                  <div
                    key={`skeleton-${i}`}
                    className="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 h-80 flex flex-col overflow-hidden"
                  >
                    <div className="h-44 bg-surface-container-high/60 w-full" />
                    <div className="p-5 space-y-3 flex-1">
                      <div className="h-5 bg-surface-container-high/70 rounded w-3/4" />
                      <div className="h-4 bg-surface-container-high/50 rounded w-1/2" />
                      <div className="h-3 bg-surface-container-high/40 rounded w-full" />
                    </div>
                  </div>
                ))}
              </div>
            ) : temples.length === 0 ? (
              /* Empty Results */
              <div className="p-12 text-center bg-surface-container-lowest rounded-2xl border border-outline-variant/40 space-y-4">
                <span className="material-symbols-outlined text-[48px] text-on-surface-variant/40">
                  search_off
                </span>
                <h3 className="font-headline-md text-headline-md font-semibold text-on-surface">
                  Chưa tìm thấy địa điểm phù hợp
                </h3>
                <p className="font-body-sm text-body-sm text-on-surface-variant max-w-md mx-auto">
                  {paramQ
                    ? `Không tìm thấy cơ sở phù hợp với từ khóa "${paramQ}". Bạn hãy thử tìm tên không dấu hoặc nới lỏng các bộ lọc.`
                    : "Hiện chưa có cơ sở Phật giáo nào khớp với bộ lọc đang chọn."}
                </p>
                <button
                  type="button"
                  onClick={handleClearAll}
                  className="px-4 py-2 bg-surface-container-high text-on-surface font-label-md text-label-md font-medium rounded-xl hover:bg-surface-container-highest transition-colors shadow-2xs"
                >
                  Xóa tất cả bộ lọc
                </button>
              </div>
            ) : (
              /* Temple Grid */
              <>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                  {temples.map((temple) => (
                    <TempleCard key={temple.public_id} temple={temple} />
                  ))}
                </div>

                {/* Pagination */}
                <TemplePagination
                  currentPage={currentPage}
                  lastPage={lastPage}
                  onPageChange={handlePageChange}
                />
              </>
            )}

            {/* Dataset Coverage Disclaimer */}
            <footer className="pt-6 border-t border-outline-variant/20 text-center">
              <p className="font-body-xs text-body-xs text-on-surface-variant/70 italic max-w-2xl mx-auto">
                Danh sách được cập nhật và kiểm duyệt theo từng giai đoạn. Việc chưa
                có thông tin của một ngôi chùa trong hệ thống không có nghĩa địa điểm
                đó không tồn tại.
              </p>
            </footer>
          </main>
        </div>
      </div>

      {/* Mobile Filter Drawer */}
      <TempleFilterDrawer
        isOpen={isMobileDrawerOpen}
        onClose={() => setIsMobileDrawerOpen(false)}
        facets={facets}
        selectedType={paramType}
        selectedProvince={paramProvince}
        selectedTradition={paramTradition}
        selectedSort={paramSort}
        onTypeChange={handleTypeChange}
        onProvinceChange={handleProvinceChange}
        onTraditionChange={handleTraditionChange}
        onSortChange={handleSortChange}
        onReset={handleClearAll}
        hasActiveFilters={Boolean(
          paramType || paramProvince || paramTradition || paramSort !== "relevance"
        )}
      />
    </div>
  );
}
