import React, { Suspense } from "react";
import type { Metadata } from "next";
import Link from "next/link";
import Image from "next/image";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { BuddhistFigureCard } from "@/components/figure/BuddhistFigureCard";
import { FigureFilters } from "@/components/figure/FigureFilters";
import { FigureSearchBox } from "@/components/figure/FigureSearchBox";
import { FigurePagination } from "@/components/figure/FigurePagination";
import { getTraditions } from "@/lib/api/sutras";
import { getBuddhistFigures } from "@/lib/api/figures";
import { BuddhistFigure, PaginatedResponse } from "@/types/api";

export const metadata: Metadata = {
  title: "Thư Viện Phật & Bồ Tát | OmPraMani",
  description:
    "Chiêm bái tôn dung, hạnh nguyện và đại nguyện của Chư Phật, Bồ Tát, Thánh Tăng trong truyền thống Phật giáo.",
  alternates: {
    canonical: "/phat-bo-tat",
  },
  openGraph: {
    title: "Thư Viện Phật & Bồ Tát | OmPraMani",
    description: "Chiêm bái tôn dung, hạnh nguyện và đại nguyện của Chư Phật, Bồ Tát, Thánh Tăng trong truyền thống Phật giáo.",
    url: "/phat-bo-tat",
    siteName: "OmPraMani",
    locale: "vi_VN",
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Thư Viện Phật & Bồ Tát | OmPraMani",
    description: "Chiêm bái tôn dung, hạnh nguyện và đại nguyện của Chư Phật, Bồ Tát, Thánh Tăng trong truyền thống Phật giáo.",
  },
};

interface PhatBoTatPageProps {
  searchParams: Promise<{
    type?: string;
    tradition?: string;
    search?: string;
    page?: string;
  }>;
}

export default async function PhatBoTatPage({
  searchParams,
}: PhatBoTatPageProps) {
  const resolvedParams = await searchParams;
  const currentType = resolvedParams.type;
  const currentTradition = resolvedParams.tradition;
  const currentSearch = resolvedParams.search;
  const currentPage = Number(resolvedParams.page) || 1;

  const [traditions, figuresRes] = await Promise.all([
    getTraditions(),
    getBuddhistFigures({
      type: currentType,
      tradition: currentTradition,
      search: currentSearch,
      page: currentPage,
      per_page: 12,
    }).catch(
      (): PaginatedResponse<BuddhistFigure> => ({
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

  const figures = figuresRes.data || [];
  const meta = figuresRes.meta;

  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="w-full bg-surface flex-1 pt-16">
        {/* Sacred Buddhist Figures Hero Banner - Full Width & Majestic Height */}
        <section className="relative w-full overflow-hidden min-h-[420px] sm:min-h-[500px] md:min-h-[560px] lg:min-h-[600px] flex items-center border-b border-outline-variant/30 group">
          {/* 1. Ảnh nằm dưới cùng (tràn mép 100vw) */}
          <Image
            src="/images/buddhist-figures-banner.jpg"
            alt="Thư Viện Phật & Bồ Tát"
            fill
            priority
            sizes="100vw"
            className="object-cover object-[center_35%] group-hover:scale-102 transition-transform duration-700"
          />

          {/* Lớp gradient tự nhiên loang dần từ trái sang phải để tôn chữ, không tạo viền hộp */}
          <div className="absolute inset-0 bg-gradient-to-r from-stone-950/75 via-stone-900/40 via-45% to-transparent pointer-events-none" />
          <div className="absolute inset-0 bg-gradient-to-t from-stone-950/40 via-transparent to-transparent pointer-events-none" />

          {/* Căn lề nội dung theo khung chuẩn 1360px của trang */}
          <div className="max-w-[1360px] mx-auto px-4 sm:px-6 w-full py-12 sm:py-16 md:py-20 relative z-10">
            {/* Chữ nằm trực tiếp trên banner (bỏ hoàn toàn background của chữ) */}
            <div className="max-w-xl sm:max-w-2xl space-y-4">

              <h1 className="font-headline-xl text-3xl sm:text-5xl lg:text-6xl font-bold text-white tracking-tight font-serif drop-shadow-[0_2px_8px_rgba(0,0,0,0.7)] leading-[1.15]">
                Thư Viện Phật &amp; Bồ Tát
              </h1>
              <p className="font-body-md text-base sm:text-lg text-white/90 leading-relaxed drop-shadow-[0_1px_4px_rgba(0,0,0,0.8)] max-w-xl font-normal">
                Chiêm bái tôn dung, tìm hiểu bổn tích, đại nguyện từ bi và pháp
                môn hành trì của Chư Phật, Chư Đại Bồ Tát cùng Thánh Tăng các
                truyền thống Phật giáo.
              </p>
            </div>
          </div>
        </section>

        {/* Nội dung danh mục Phật & Bồ Tát */}
        <div className="max-w-[1360px] mx-auto px-4 sm:px-6 py-8 space-y-8">
          {/* Filter & Search Toolbar */}
          <div className="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4 py-4 border-b border-outline-variant/40">
            <Suspense
              fallback={
                <div className="h-10 w-64 bg-surface-container rounded-full animate-pulse" />
              }
            >
              <FigureFilters
                traditions={traditions}
                currentType={currentType}
                currentTradition={currentTradition}
              />
            </Suspense>

            <Suspense
              fallback={
                <div className="h-10 w-64 bg-surface-container rounded-full animate-pulse" />
              }
            >
              <FigureSearchBox />
            </Suspense>
          </div>

          {/* Figure Grid / Empty State */}
          {figures.length > 0 ? (
            <section aria-label="Danh sách Phật và Bồ Tát">
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                {figures.map((figure) => (
                  <BuddhistFigureCard key={figure.id} figure={figure} />
                ))}
              </div>

              {meta && (
                <Suspense fallback={null}>
                  <FigurePagination meta={meta} />
                </Suspense>
              )}
            </section>
          ) : (
            <div className="bg-surface-container-lowest rounded-3xl p-12 text-center space-y-4 max-w-lg mx-auto border border-outline-variant/30 my-8">
              <div className="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center mx-auto text-primary">
                <span className="material-symbols-outlined text-[32px]">
                  self_improvement
                </span>
              </div>
              <h2 className="font-headline-lg text-headline-lg font-semibold text-on-surface">
                Không tìm thấy tôn tượng phù hợp
              </h2>
              <p className="font-body-sm text-body-sm text-on-surface-variant">
                {currentSearch || currentType || currentTradition
                  ? "Không có hồ sơ Phật hoặc Bồ Tát nào khớp với điều kiện lọc hiện tại. Xin hoan hỷ thử lại với từ khóa hoặc bộ lọc khác."
                  : "Hiện chưa có hồ sơ Phật & Bồ Tát nào được xuất bản trong mục này."}
              </p>
              {(currentSearch || currentType || currentTradition) && (
                <Link
                  href="/phat-bo-tat"
                  className="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-full bg-primary text-on-primary font-label-md text-label-md font-semibold hover:bg-on-primary-container transition-colors shadow-xs"
                >
                  <span className="material-symbols-outlined text-[18px]">
                    restart_alt
                  </span>
                  <span>Xem tất cả Phật &amp; Bồ Tát</span>
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
