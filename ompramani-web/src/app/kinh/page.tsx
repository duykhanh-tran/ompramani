import React, { Suspense } from "react";
import type { Metadata } from "next";
import Link from "next/link";
import Image from "next/image";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { SutraCard } from "@/components/sutra/SutraCard";
import { TraditionFilter } from "@/components/sutra/TraditionFilter";
import { SutraSearchBox } from "@/components/sutra/SutraSearchBox";
import { SutraPagination } from "@/components/sutra/SutraPagination";
import { getTraditions, getSutras } from "@/lib/api/sutras";
import { PaginatedResponse, Sutra } from "@/types/api";

export const metadata: Metadata = {
  title: "Thư Viện Kinh Điển Phật Giáo | OmPraMani",
  description:
    "Kho tàng kinh điển trang nghiêm được biên tập theo truyền thống Bắc Truyền và Nam Truyền.",
  alternates: {
    canonical: "/kinh",
  },
  openGraph: {
    title: "Thư Viện Kinh Điển Phật Giáo | OmPraMani",
    description: "Kho tàng kinh điển trang nghiêm được biên tập theo truyền thống Bắc Truyền và Nam Truyền.",
    url: "/kinh",
    siteName: "OmPraMani",
    locale: "vi_VN",
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Thư Viện Kinh Điển Phật Giáo | OmPraMani",
    description: "Kho tàng kinh điển trang nghiêm được biên tập theo truyền thống Bắc Truyền và Nam Truyền.",
  },
};

interface KinhPageProps {
  searchParams: Promise<{
    tradition?: string;
    search?: string;
    page?: string;
  }>;
}

export default async function KinhPage({ searchParams }: KinhPageProps) {
  const resolvedParams = await searchParams;
  const currentTradition = resolvedParams.tradition;
  const currentSearch = resolvedParams.search;
  const currentPage = Number(resolvedParams.page) || 1;

  // Concurrent data fetching
  const [traditions, sutrasRes] = await Promise.all([
    getTraditions(),
    getSutras({
      tradition: currentTradition,
      search: currentSearch,
      page: currentPage,
      per_page: 12,
    }).catch(
      (): PaginatedResponse<Sutra> => ({
        data: [],
        meta: {
          current_page: 1,
          last_page: 1,
          per_page: 12,
          total: 0,
        },
      })
    ),
  ]);

  const sutras = sutrasRes.data || [];
  const meta = sutrasRes.meta;

  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="w-full bg-surface flex-1 pt-16">
        {/* Sacred Sutra Library Hero Banner - Full Width & Majestic Height */}
        <section className="relative w-full overflow-hidden min-h-[420px] sm:min-h-[500px] md:min-h-[560px] lg:min-h-[600px] flex items-center border-b border-outline-variant/30 group">
          {/* 1. Ảnh nằm dưới cùng (tràn mép 100vw) */}
          <Image
            src="/images/sutra-banner.jpg"
            alt="Thư Viện Kinh Phật"
            fill
            priority
            sizes="100vw"
            className="object-cover object-[center_35%] group-hover:scale-102 transition-transform duration-700"
          />

          {/* Lớp gradient tự nhiên loang dần từ trái sang phải để tôn chữ, không tạo viền hộp */}
          <div className="absolute inset-0 bg-gradient-to-r from-black/75 via-black/40 via-45% to-transparent pointer-events-none" />
          <div className="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent pointer-events-none" />

          {/* Căn lề nội dung theo khung chuẩn 1360px của trang */}
          <div className="max-w-[1360px] mx-auto px-4 sm:px-6 w-full py-12 sm:py-16 md:py-20 relative z-10">
            {/* Chữ nằm trực tiếp trên banner (bỏ hoàn toàn background của chữ) */}
            <div className="max-w-xl sm:max-w-2xl space-y-4">

              <h1 className="font-headline-xl text-3xl sm:text-5xl lg:text-6xl font-bold text-white tracking-tight font-serif drop-shadow-[0_2px_8px_rgba(0,0,0,0.7)] leading-[1.15]">
                Thư Viện Kinh Phật
              </h1>
              <p className="font-body-md text-base sm:text-lg text-white/90 leading-relaxed drop-shadow-[0_1px_4px_rgba(0,0,0,0.8)] max-w-xl font-normal">
                Kho tàng kinh điển trang nghiêm được biên tập theo truyền thống
                Bắc Truyền và Nam Truyền. Nơi lưu giữ lời dạy giải thoát của Đức
                Phật cho muôn đời sau.
              </p>
            </div>
          </div>
        </section>

        {/* Nội dung danh mục kinh văn */}
        <div className="max-w-[1360px] mx-auto px-4 sm:px-6 py-8 space-y-8">
          {/* Filter & Search Toolbar */}
          <div className="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 py-4 border-b border-outline-variant/40">
            <Suspense fallback={<div className="h-9 w-48 bg-surface-container rounded-full animate-pulse" />}>
              <TraditionFilter
                traditions={traditions}
                currentTradition={currentTradition}
              />
            </Suspense>

            <Suspense fallback={<div className="h-10 w-64 bg-surface-container rounded-full animate-pulse" />}>
              <SutraSearchBox />
            </Suspense>
          </div>

          {/* Sutra Grid / Empty state */}
          {sutras.length > 0 ? (
            <section aria-label="Danh sách kinh văn">
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                {sutras.map((sutra) => (
                  <SutraCard key={sutra.id} sutra={sutra} />
                ))}
              </div>

              {/* Pagination */}
              {meta && (
                <Suspense fallback={null}>
                  <SutraPagination meta={meta} />
                </Suspense>
              )}
            </section>
          ) : (
            <div className="bg-surface-container-lowest rounded-3xl p-12 text-center space-y-4 max-w-lg mx-auto border border-outline-variant/30 my-8">
              <div className="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center mx-auto text-primary">
                <span className="material-symbols-outlined text-[32px]">
                  search_off
                </span>
              </div>
              <h2 className="font-headline-lg text-headline-lg font-semibold text-on-surface">
                Không tìm thấy kinh văn phù hợp
              </h2>
              <p className="font-body-sm text-body-sm text-on-surface-variant">
                {currentSearch || currentTradition
                  ? "Không có bản kinh nào thỏa mãn điều kiện lọc hoặc từ khóa tìm kiếm. Xin hoan hỷ thử lại với từ khóa khác."
                  : "Hiện chưa có kinh điển nào được xuất bản trong mục này."}
              </p>
              {(currentSearch || currentTradition) && (
                <Link
                  href="/kinh"
                  className="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-full bg-primary text-on-primary font-label-md text-label-md font-semibold hover:bg-on-primary-container transition-colors shadow-xs"
                >
                  <span className="material-symbols-outlined text-[18px]">
                    restart_alt
                  </span>
                  <span>Xem tất cả kinh điển</span>
                </Link>
              )}
            </div>
          )}
        </div>
      </main>

      <Footer />
    </div>
  );
}
