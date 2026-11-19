import React, { Suspense } from "react";
import type { Metadata } from "next";
import Link from "next/link";
import Image from "next/image";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { ArticleCard } from "@/components/article/ArticleCard";
import { CategoryTabs } from "@/components/article/CategoryTabs";
import { ArticleSearchBox } from "@/components/article/ArticleSearchBox";
import { ArticlePagination } from "@/components/article/ArticlePagination";
import { getArticles, getArticleCategories } from "@/lib/api/articles";
import { Article, PaginatedResponse } from "@/types/api";

export const metadata: Metadata = {
  title: "Thư Viện Bài Viết & Pháp Thoại | OmPraMani",
  description:
    "Khám phá các bài viết Phật học, giáo lý ứng dụng, câu chuyện đạo và hướng dẫn thực hành thiền tập trên nền tảng Phật giáo số OmPraMani.",
  alternates: {
    canonical: "/bai-viet",
  },
  openGraph: {
    title: "Thư Viện Bài Viết & Pháp Thoại | OmPraMani",
    description: "Khám phá các bài viết Phật học, giáo lý ứng dụng, câu chuyện đạo và hướng dẫn thực hành thiền tập trên nền tảng Phật giáo số OmPraMani.",
    url: "/bai-viet",
    siteName: "OmPraMani",
    locale: "vi_VN",
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Thư Viện Bài Viết & Pháp Thoại | OmPraMani",
    description: "Khám phá các bài viết Phật học, giáo lý ứng dụng, câu chuyện đạo và hướng dẫn thực hành thiền tập trên nền tảng Phật giáo số OmPraMani.",
  },
};

interface BaiVietPageProps {
  searchParams: Promise<{
    category?: string;
    tag?: string;
    search?: string;
    sort?: "newest" | "oldest";
    page?: string;
  }>;
}

export default async function BaiVietPage({ searchParams }: BaiVietPageProps) {
  const resolvedParams = await searchParams;
  const currentCategory = resolvedParams.category;
  const currentTag = resolvedParams.tag;
  const currentSearch = resolvedParams.search;
  const currentSort = resolvedParams.sort || "newest";
  const currentPage = Number(resolvedParams.page) || 1;

  const [categories, articlesRes] = await Promise.all([
    getArticleCategories(),
    getArticles({
      category: currentCategory,
      tag: currentTag,
      search: currentSearch,
      sort: currentSort,
      page: currentPage,
      per_page: 12,
    }).catch(
      (): PaginatedResponse<Article> => ({
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

  const articles = articlesRes.data || [];
  const meta = articlesRes.meta;

  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="w-full bg-surface flex-1 pt-16">
        {/* Sacred Buddhist Articles Hero Banner - Full Width & Majestic Height */}
        <section className="relative w-full overflow-hidden min-h-[420px] sm:min-h-[500px] md:min-h-[560px] lg:min-h-[600px] flex items-center border-b border-outline-variant/30 group">
          {/* 1. Ảnh nằm dưới cùng (tràn mép 100vw) */}
          <Image
            src="/images/articles-banner.jpg"
            alt="Thư Viện Bài Viết & Pháp Thoại"
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
                Thư Viện Bài Viết &amp; Pháp Thoại
              </h1>
              <p className="font-body-md text-base sm:text-lg text-white/90 leading-relaxed drop-shadow-[0_1px_4px_rgba(0,0,0,0.8)] max-w-xl font-normal">
                Nơi chia sẻ các bài viết nghiên cứu Phật học, giáo lý cốt lõi,
                pháp thoại ứng dụng chuyển hóa khổ đau và nuôi dưỡng tâm từ bi trong đời sống thường nhật.
              </p>
            </div>
          </div>
        </section>

        {/* Nội dung danh mục bài viết */}
        <div className="max-w-[1360px] mx-auto px-4 sm:px-6 py-8 space-y-8">
          {/* Filter & Search Toolbar */}
          <div className="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4 py-4 border-b border-outline-variant/40">
            <Suspense
              fallback={
                <div className="h-10 w-72 bg-surface-container rounded-full animate-pulse" />
              }
            >
              <CategoryTabs
                categories={categories}
                currentCategory={currentCategory}
                currentTag={currentTag}
              />
            </Suspense>

            <Suspense
              fallback={
                <div className="h-10 w-64 bg-surface-container rounded-full animate-pulse" />
              }
            >
              <ArticleSearchBox />
            </Suspense>
          </div>

          {/* Articles Grid or Empty State */}
          {articles.length > 0 ? (
            <section aria-label="Danh sách bài viết">
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                {articles.map((article) => (
                  <ArticleCard key={article.id} article={article} variant="standard" />
                ))}
              </div>

              {meta && (
                <Suspense fallback={null}>
                  <ArticlePagination meta={meta} />
                </Suspense>
              )}
            </section>
          ) : (
            <div className="bg-surface-container-lowest rounded-3xl p-12 text-center space-y-4 max-w-lg mx-auto border border-outline-variant/30 my-8">
              <div className="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center mx-auto text-primary">
                <span className="material-symbols-outlined text-[32px]">
                  menu_book
                </span>
              </div>
              <h2 className="font-headline-lg text-headline-lg font-semibold text-on-surface">
                Không tìm thấy bài viết phù hợp
              </h2>
              <p className="font-body-sm text-body-sm text-on-surface-variant">
                {currentSearch || currentCategory || currentTag
                  ? "Không có bài viết nào khớp với điều kiện tìm kiếm hoặc bộ lọc hiện tại. Xin hoan hỷ thử lại với từ khóa hoặc danh mục khác."
                  : "Hiện chưa có bài viết nào được xuất bản trong chuyên mục này."}
              </p>
              {(currentSearch || currentCategory || currentTag) && (
                <Link
                  href="/bai-viet"
                  className="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-full bg-primary text-on-primary font-label-md text-label-md font-semibold hover:bg-on-primary-container transition-colors shadow-xs"
                >
                  <span className="material-symbols-outlined text-[18px]">
                    restart_alt
                  </span>
                  <span>Xem tất cả bài viết</span>
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
