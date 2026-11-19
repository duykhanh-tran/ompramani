import React, { Suspense } from "react";
import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { FigureDetailUnified } from "@/components/figure/FigureDetailUnified";
import { getBuddhistFigure } from "@/lib/api/figures";
import { resolveMediaUrl } from "@/lib/utils/media";
import { SITE_CONFIG } from "@/lib/seo/config";

interface FigureDetailPageProps {
  params: Promise<{
    slug: string;
  }>;
}

export async function generateMetadata({
  params,
}: FigureDetailPageProps): Promise<Metadata> {
  const { slug } = await params;
  const figure = await getBuddhistFigure(slug);

  if (!figure) {
    return {
      title: "Phật & Bồ Tát | OmPraMani",
      description: "Thư viện Phật & Bồ Tát trên nền tảng Phật giáo số OmPraMani.",
    };
  }

  const imageUrl = resolveMediaUrl(figure.thumbnail);
  const title = `${figure.name} | OmPraMani`;
  const description =
    figure.short_description ||
    `Tìm hiểu tiểu sử, đại nguyện và pháp môn hành trì của ${figure.name}.`;

  return {
    title,
    description,
    alternates: {
      canonical: `/phat-bo-tat/${figure.slug}`,
    },
    openGraph: {
      title,
      description,
      url: `/phat-bo-tat/${figure.slug}`,
      siteName: SITE_CONFIG.siteName,
      locale: SITE_CONFIG.locale,
      type: "article",
      images: imageUrl ? [{ url: imageUrl }] : undefined,
    },
    twitter: {
      card: "summary_large_image",
      title,
      description,
      images: imageUrl ? [imageUrl] : undefined,
    },
  };
}

export default async function FigureDetailPage({
  params,
}: FigureDetailPageProps) {
  const { slug } = await params;
  const figure = await getBuddhistFigure(slug);

  if (!figure) {
    notFound();
  }

  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="w-full bg-surface flex-1 pt-16">
        <Suspense
          fallback={
            <div className="max-w-md mx-auto py-24 text-center">
              <div className="w-10 h-10 border-3 border-primary border-t-transparent rounded-full animate-spin mx-auto mb-4" />
              <p className="font-sans text-xs text-[#735d45]">Đang tải thông tin Đức Phật &amp; Bồ Tát...</p>
            </div>
          }
        >
          <FigureDetailUnified figure={figure} />
        </Suspense>
      </main>

      <Footer />
    </div>
  );
}
