import React, { Suspense } from "react";
import type { Metadata } from "next";
import Link from "next/link";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { SearchPageHeader } from "@/components/search/SearchPageHeader";
import { SearchMainInput } from "@/components/search/SearchMainInput";
import { SearchFilters } from "@/components/search/SearchFilters";
import { SearchResultList } from "@/components/search/SearchResultList";
import { SearchPagination } from "@/components/search/SearchPagination";
import { SearchEmptyState } from "@/components/search/SearchEmptyState";
import { SearchErrorState } from "@/components/search/SearchErrorState";
import { searchContent } from "@/lib/api/search";
import { getTraditions } from "@/lib/api/sutras";
import { SearchResponse, Tradition } from "@/types/api";

interface TimKiemPageProps {
  searchParams: Promise<{
    q?: string;
    type?: string;
    tradition?: string;
    page?: string;
    sort?: "relevance" | "newest";
  }>;
}

export async function generateMetadata({
  searchParams,
}: TimKiemPageProps): Promise<Metadata> {
  const { q } = await searchParams;
  const cleanQ = q ? q.trim().replace(/[<>]/g, "") : "";

  return {
    title: cleanQ ? `Tìm kiếm “${cleanQ}” | OmPraMani` : "Tìm Kiếm | OmPraMani",
    description:
      "Tra cứu toàn diện Kinh tạng, Thần chú, Tôn tượng Chư Phật & Bồ Tát, Bài viết pháp thoại và phương tiện tu tập trên OmPraMani.",
    robots: {
      index: false,
      follow: false,
    },
  };
}

export default async function TimKiemPage({ searchParams }: TimKiemPageProps) {
  const resolvedParams = await searchParams;
  const rawQ = resolvedParams.q ? resolvedParams.q.trim() : "";
  const currentType = resolvedParams.type || "all";
  const currentTradition = resolvedParams.tradition;
  const currentSort = resolvedParams.sort || "relevance";
  const currentPage = Number(resolvedParams.page) || 1;

  let traditions: Tradition[] = [];
  let searchRes: SearchResponse = {
    data: [],
    meta: {
      query: rawQ,
      total: 0,
      page: currentPage,
      per_page: 12,
    },
  };
  let hasError = false;
  let errorMessage: string | undefined;

  // Always fetch traditions for filter options
  try {
    traditions = await getTraditions();
  } catch (err) {
    console.error("Lỗi tải traditions:", err);
  }

  // Only call Search API if query meets minimum length (2 chars)
  if (rawQ.length >= 2) {
    try {
      searchRes = await searchContent({
        q: rawQ,
        type: currentType,
        tradition: currentTradition,
        sort: currentSort,
        page: currentPage,
        per_page: 12,
      });
    } catch (err) {
      console.error("Lỗi tìm kiếm API:", err);
      hasError = true;
      errorMessage =
        err instanceof Error
          ? err.message
          : "Hiện chưa thể kết nối đến máy chủ tìm kiếm. Xin vui lòng thử lại sau.";
    }
  }

  const results = searchRes.data || [];
  const meta = searchRes.meta;
  const hasActiveFilters = Boolean(
    (currentType && currentType !== "all") || currentTradition
  );

  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="w-full bg-surface flex-1 pt-16">
        <div className="max-w-[1120px] mx-auto px-4 sm:px-6 py-8 space-y-8">
          {/* Breadcrumb */}
          <nav
            aria-label="Breadcrumb"
            className="flex items-center gap-2 text-[13px] font-label-md text-outline"
          >
            <Link href="/" className="hover:text-primary transition-colors">
              Trang chủ
            </Link>
            <span className="material-symbols-outlined text-[14px]">
              chevron_right
            </span>
            <span className="text-on-surface font-semibold">Tìm Kiếm</span>
          </nav>

          {/* Heading */}
          <SearchPageHeader query={rawQ} totalResults={meta.total} />

          {/* Large Main Search Input */}
          <Suspense
            fallback={
              <div className="h-14 w-full max-w-3xl mx-auto bg-surface-container rounded-full animate-pulse" />
            }
          >
            <SearchMainInput />
          </Suspense>

          {/* Filters Bar (Only show when there is a search or if user previously selected type) */}
          <div className="py-2 border-y border-outline-variant/40">
            <Suspense
              fallback={
                <div className="h-12 w-full bg-surface-container rounded-xl animate-pulse" />
              }
            >
              <SearchFilters
                traditions={traditions}
                currentType={currentType}
                currentTradition={currentTradition}
                currentSort={currentSort}
              />
            </Suspense>
          </div>

          {/* Main Results / Empty / Error State */}
          {hasError ? (
            <SearchErrorState message={errorMessage} />
          ) : rawQ.length < 2 ? (
            <SearchEmptyState query={rawQ} hasFilters={hasActiveFilters} />
          ) : results.length === 0 ? (
            <SearchEmptyState query={rawQ} hasFilters={hasActiveFilters} />
          ) : (
            <div className="space-y-8">
              <SearchResultList items={results} query={rawQ} />

              {meta.total > meta.per_page && (
                <Suspense fallback={null}>
                  <SearchPagination meta={meta} />
                </Suspense>
              )}
            </div>
          )}
        </div>
      </main>

      <Footer />
    </div>
  );
}
