import React, { Suspense } from "react";
import type { Metadata } from "next";
import Link from "next/link";
import Image from "next/image";
import { redirect } from "next/navigation";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { getBuddhistFigures } from "@/lib/api/figures";
import { getMantra } from "@/lib/api/mantras";
import { resolveMediaUrl } from "@/lib/utils/media";
import { BuddhistFigure } from "@/types/api";

interface TriChuPageProps {
  searchParams: Promise<{
    mantra?: string;
    figure?: string;
  }>;
}

export const metadata: Metadata = {
  title: "Trì Chú — Phương Tiện Tu Tập OmPraMani",
  description:
    "Thực hành trì tụng thần chú chư Phật và Bồ Tát trang nghiêm, định tâm với âm thanh trì niệm thanh tịnh.",
  alternates: {
    canonical: "/tri-chu",
  },
  openGraph: {
    title: "Trì Chú — Phương Tiện Tu Tập OmPraMani",
    description: "Thực hành trì tụng thần chú chư Phật và Bồ Tát trang nghiêm, định tâm với âm thanh trì niệm thanh tịnh.",
    url: "/tri-chu",
    siteName: "OmPraMani",
    locale: "vi_VN",
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Trì Chú — Phương Tiện Tu Tập OmPraMani",
    description: "Thực hành trì tụng thần chú chư Phật và Bồ Tát trang nghiêm, định tâm với âm thanh trì niệm thanh tịnh.",
  },
};

async function TriChuHub({ searchParams }: TriChuPageProps) {
  const params = await searchParams;

  // 1. If specific mantra requested via query, direct to figure's than-chu tab
  if (params.mantra) {
    const mantra = await getMantra(params.mantra);
    const figureSlug = mantra?.figure?.slug || mantra?.buddhist_figure?.slug;
    if (figureSlug) {
      redirect(`/phat-bo-tat/${encodeURIComponent(figureSlug)}?tab=than-chu&mantra=${encodeURIComponent(params.mantra)}`);
    }
    if (mantra?.slug) {
      redirect(`/chu/${encodeURIComponent(mantra.slug)}`);
    }
  }

  // 2. If specific figure requested via query, direct to figure's than-chu tab
  if (params.figure) {
    redirect(`/phat-bo-tat/${encodeURIComponent(params.figure)}?tab=than-chu`);
  }

  // 3. Load all published figures for selection
  let figures: BuddhistFigure[] = [];
  try {
    const res = await getBuddhistFigures({ per_page: 50 });
    figures = res.data || [];
  } catch (err) {
    console.warn("Could not load figures for Tri Chu hub:", err);
  }

  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="w-full flex-1 pt-20 pb-16 bg-surface">
        <div className="max-w-[1080px] mx-auto px-4 sm:px-6 space-y-8">
          {/* Breadcrumb Navigation */}
          <nav
            aria-label="Breadcrumb"
            className="flex items-center gap-2 text-[13px] font-label-md text-outline"
          >
            <Link href="/" className="hover:text-primary transition-colors">
              Trang chủ
            </Link>
            <span className="material-symbols-outlined text-[14px]">chevron_right</span>
            <span className="text-on-surface font-semibold">Trì Chú</span>
          </nav>

          {/* Header Banner */}
          <div className="text-center space-y-2 max-w-2xl mx-auto">
            <span className="font-mono text-xs uppercase tracking-widest text-[#8c7457]">
              Phương Tiện Tu Tập
            </span>
            <h1 className="font-serif text-3xl sm:text-4xl md:text-5xl font-bold text-[#2b1f14]">
              Không Gian Trì Chú
            </h1>
            <p className="font-sans text-xs sm:text-sm text-[#735d45] leading-relaxed max-w-lg mx-auto">
              Chọn Đức Phật hoặc Bồ Tát để bắt đầu thời khóa trì tụng chân ngôn, mật chú với âm thanh trợ niệm thanh tịnh.
            </p>
          </div>

          {/* Figures Directory List / Grid for User Selection */}
          {figures.length === 0 ? (
            <div className="bg-[#FAF7F0] border border-[#dfd2be] rounded-3xl p-10 text-center space-y-3">
              <span className="material-symbols-outlined text-4xl text-[#c49a45]/60">spa</span>
              <p className="font-sans text-sm text-[#735d45]">
                Đang cập nhật danh sách chư Phật và Bồ Tát...
              </p>
            </div>
          ) : (
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
              {figures.map((figure) => {
                const imgUrl = resolveMediaUrl(figure.thumbnail);
                const figureMantras = figure.mantras || [];

                return (
                  <Link
                    key={figure.id}
                    href={`/phat-bo-tat/${figure.slug}?tab=than-chu`}
                    className="group bg-[#FAF7F0] border border-[#dfd2be] rounded-3xl p-6 shadow-xs hover:shadow-md hover:border-[#b8860b]/60 transition-all flex flex-col items-center text-center space-y-4"
                  >
                    {/* Portrait Image */}
                    <div className="relative w-32 h-40 sm:w-36 sm:h-44 rounded-2xl overflow-hidden border-2 border-[#d4af37]/40 shadow-xs bg-[#f4ece0]">
                      {imgUrl ? (
                        <Image
                          src={imgUrl}
                          alt={figure.name}
                          fill
                          sizes="(max-width: 640px) 128px, 144px"
                          className="object-cover object-top group-hover:scale-105 transition-transform duration-300"
                        />
                      ) : (
                        <div className="w-full h-full flex items-center justify-center text-[#c49a45]/60">
                          <span className="material-symbols-outlined text-5xl">spa</span>
                        </div>
                      )}
                    </div>

                    {/* Figure Title & Info */}
                    <div className="space-y-1">
                      <h3 className="font-serif text-lg sm:text-xl font-bold text-[#2b1f14] group-hover:text-[#8f5a28] transition-colors">
                        {figure.name}
                      </h3>
                      {figure.original_name && (
                        <p className="font-sans text-xs text-[#8c7457] italic">
                          {figure.original_name}
                        </p>
                      )}
                    </div>

                    {/* Mantras Tag/Count if available */}
                    {figureMantras.length > 0 && (
                      <div className="flex flex-wrap items-center justify-center gap-1.5 pt-1">
                        {figureMantras.slice(0, 2).map((m) => (
                          <span
                            key={m.id}
                            className="px-2.5 py-0.5 rounded-full bg-[#f0eae1] border border-[#d8ccbc] text-[11px] text-[#6d573f] font-sans"
                          >
                            {m.title}
                          </span>
                        ))}
                        {figureMantras.length > 2 && (
                          <span className="text-[11px] text-[#8c7457] font-sans font-medium">
                            +{figureMantras.length - 2} chú
                          </span>
                        )}
                      </div>
                    )}

                    {/* CTA Button */}
                    <div className="pt-2 w-full">
                      <span className="w-full py-2.5 px-4 rounded-full bg-[#8f5a28] text-white text-xs sm:text-sm font-serif font-semibold shadow-2xs group-hover:bg-[#784617] transition-all flex items-center justify-center gap-1.5">
                        <span className="material-symbols-outlined text-base leading-none">play_arrow</span>
                        <span>Vào Trì Chú</span>
                      </span>
                    </div>
                  </Link>
                );
              })}
            </div>
          )}
        </div>
      </main>

      <Footer />
    </div>
  );
}

export default function TriChuPage(props: TriChuPageProps) {
  return (
    <Suspense
      fallback={
        <div className="min-h-screen bg-surface flex flex-col items-center justify-center p-6 text-center">
          <div className="w-12 h-12 rounded-full border-3 border-primary border-t-transparent animate-spin mb-4" />
          <p className="font-label-md text-sm text-outline">
            Đang tải không gian Trì Chú...
          </p>
        </div>
      }
    >
      <TriChuHub searchParams={props.searchParams} />
    </Suspense>
  );
}
