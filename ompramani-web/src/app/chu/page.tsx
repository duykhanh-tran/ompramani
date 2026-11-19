import React, { Suspense } from "react";
import type { Metadata } from "next";
import Link from "next/link";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { MantraCard } from "@/components/mantra/MantraCard";
import { MantraFilters } from "@/components/mantra/MantraFilters";
import { MantraSearchBox } from "@/components/mantra/MantraSearchBox";
import { MantraPagination } from "@/components/mantra/MantraPagination";
import { getTraditions } from "@/lib/api/sutras";
import { getBuddhistFigures } from "@/lib/api/figures";
import { getMantras } from "@/lib/api/mantras";
import { BuddhistFigure, Mantra, PaginatedResponse } from "@/types/api";

export const metadata: Metadata = {
  title: "Thư Viện Thần Chú & Đà La Ni | OmPraMani",
  description:
    "Kho tàng thần chú, chân ngôn và đà la ni trang nghiêm với đầy đủ nguyên văn, phiên âm và hướng dẫn hành trì.",
};

interface ChuPageProps {
  searchParams: Promise<{
    tradition?: string;
    figure?: string;
    search?: string;
    page?: string;
  }>;
}

export default async function ChuPage({ searchParams }: ChuPageProps) {
  const resolvedParams = await searchParams;
  const currentTradition = resolvedParams.tradition;
  const currentFigure = resolvedParams.figure;
  const currentSearch = resolvedParams.search;
  const currentPage = Number(resolvedParams.page) || 1;

  const [traditions, figuresRes, mantrasRes] = await Promise.all([
    getTraditions(),
    getBuddhistFigures({ per_page: 24 }).catch(
      (): PaginatedResponse<BuddhistFigure> => ({
        data: [],
      })
    ),
    getMantras({
      tradition: currentTradition,
      figure: currentFigure,
      search: currentSearch,
      page: currentPage,
      per_page: 12,
    }).catch(
      (): PaginatedResponse<Mantra> => ({
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
  const mantras = mantrasRes.data || [];
  const meta = mantrasRes.meta;

  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main className="w-full bg-surface flex-1 pt-16">
        <div className="max-w-[1360px] mx-auto px-4 sm:px-6 py-8 space-y-8">
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
            <span className="text-on-surface font-semibold">Thư Viện Chú</span>
          </nav>

          {/* Heading & Intro */}
          <div className="space-y-2">
            <div className="flex items-center gap-2">
              <span className="w-2 h-2 rounded-full bg-primary" />
              <span className="font-label-caps text-label-caps text-primary uppercase">
                Chân Ngôn Mật Tạng
              </span>
            </div>
            <h1 className="font-headline-xl text-3xl sm:text-4xl font-bold text-on-surface tracking-tight">
              Thư Viện Thần Chú &amp; Đà La Ni
            </h1>
            <p className="font-body-md text-on-surface-variant max-w-2xl">
              Tuyển tập các bản thần chú, chân ngôn và đà la ni thanh tịnh với
              đầy đủ nguyên văn Phạn ngữ, Hán văn, phiên âm và hướng dẫn hành
              trì chuẩn mực.
            </p>
          </div>

          {/* Filter & Search Toolbar */}
          <div className="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4 py-4 border-y border-outline-variant/40">
            <Suspense
              fallback={
                <div className="h-10 w-64 bg-surface-container rounded-full animate-pulse" />
              }
            >
              <MantraFilters
                traditions={traditions}
                figures={figures}
                currentTradition={currentTradition}
                currentFigure={currentFigure}
              />
            </Suspense>

            <Suspense
              fallback={
                <div className="h-10 w-64 bg-surface-container rounded-full animate-pulse" />
              }
            >
              <MantraSearchBox />
            </Suspense>
          </div>

          {/* Mantra Grid / Empty State */}
          {mantras.length > 0 ? (
            <section aria-label="Danh sách thần chú">
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                {mantras.map((mantra) => (
                  <MantraCard key={mantra.id} mantra={mantra} />
                ))}
              </div>

              {meta && (
                <Suspense fallback={null}>
                  <MantraPagination meta={meta} />
                </Suspense>
              )}
            </section>
          ) : (
            <div className="bg-surface-container-lowest rounded-3xl p-12 text-center space-y-4 max-w-lg mx-auto border border-outline-variant/30 my-8">
              <div className="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center mx-auto text-primary">
                <span className="material-symbols-outlined text-[32px]">
                  auto_awesome
                </span>
              </div>
              <h2 className="font-headline-lg text-headline-lg font-semibold text-on-surface">
                Không tìm thấy thần chú phù hợp
              </h2>
              <p className="font-body-sm text-body-sm text-on-surface-variant">
                {currentSearch || currentTradition || currentFigure
                  ? "Không có thần chú nào thỏa mãn điều kiện lọc hoặc từ khóa tìm kiếm. Xin hoan hỷ thử lại với từ khóa khác."
                  : "Hiện chưa có thần chú nào được xuất bản trong mục này."}
              </p>
              {(currentSearch || currentTradition || currentFigure) && (
                <Link
                  href="/chu"
                  className="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-full bg-primary text-on-primary font-label-md text-label-md font-semibold hover:bg-on-primary-container transition-colors shadow-xs"
                >
                  <span className="material-symbols-outlined text-[18px]">
                    restart_alt
                  </span>
                  <span>Xem tất cả thần chú</span>
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
