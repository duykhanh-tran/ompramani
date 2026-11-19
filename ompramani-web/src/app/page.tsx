import React, { Suspense } from "react";
import type { Metadata } from "next";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { GreetingCalendarSection } from "@/components/home/GreetingCalendarSection";
import { HeroHighlightsSection } from "@/components/home/HeroHighlightsSection";
import { PracticeWheelSection } from "@/components/home/PracticeWheelSection";
import { ContinueReadingSection } from "@/components/home/ContinueReadingSection";
import { PrayerRoomsSection } from "@/components/home/PrayerRoomsSection";
import { BuddhistFiguresSection } from "@/components/home/BuddhistFiguresSection";
import { TemplesSection } from "@/components/home/TemplesSection";
import { ArticlesSection } from "@/components/home/ArticlesSection";
import { CommunitySection } from "@/components/home/CommunitySection";
import { getBuddhistFigures } from "@/lib/api/figures";
import { getArticles } from "@/lib/api/articles";
import { getTemples } from "@/lib/api/temples";
import { BuddhistFigure, Article } from "@/types/api";
import { TempleSummary } from "@/types/temple";

async function FiguresAndTemplesLoader() {
  let figures: BuddhistFigure[] = [];
  let temples: TempleSummary[] = [];

  try {
    const figuresRes = await getBuddhistFigures(4);
    if (figuresRes?.data && figuresRes.data.length > 0) {
      figures = figuresRes.data;
    }
  } catch (err) {
    console.warn("Could not fetch figures from API:", err);
  }

  try {
    const templesRes = await getTemples({ per_page: 3 });
    if (templesRes?.data && templesRes.data.length > 0) {
      temples = templesRes.data;
    }
  } catch (err) {
    console.warn("Could not fetch temples from API:", err);
  }

  if (figures.length === 0 && temples.length === 0) {
    return null;
  }

  return (
    <section className="space-y-8">
      {figures.length > 0 && <BuddhistFiguresSection figures={figures} />}
      {temples.length > 0 && <TemplesSection temples={temples} />}
    </section>
  );
}

async function ArticlesLoader() {
  let articles: Article[] = [];
  try {
    const articlesRes = await getArticles(4);
    if (articlesRes?.data && articlesRes.data.length > 0) {
      articles = articlesRes.data;
    }
  } catch (err) {
    console.warn("Could not fetch articles from API:", err);
  }

  if (articles.length === 0) {
    return null;
  }

  return <ArticlesSection articles={articles} />;
}

export const metadata: Metadata = {
  title: "OmPraMani — Cổng Thông Tin & Không Gian Tu Học Phật Giáo Số",
  description:
    "Không gian tu tập và đọc kinh Phật giáo trực tuyến. Cung cấp thư viện kinh điển Tịnh Độ, Thần chú, Đại nguyện Phật & Bồ Tát, Lịch vạn niên và Pháp bảo tu học trang nghiêm.",
  alternates: {
    canonical: "/",
  },
};

export default function HomePage() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="w-full bg-surface flex-1 pt-16">
        <div className="flex flex-col w-full space-y-12 py-8">
          {/* Top sections within standard container */}
          <div className="max-w-[1360px] mx-auto px-4 sm:px-6 w-full space-y-12">
            {/* 1. Greeting & Lunar-Solar Calendar */}
            <GreetingCalendarSection />

            {/* 2. Hero Highlight: Verse & Audio Mini Deck & Streak */}
            <HeroHighlightsSection />

            {/* 3. Dharma Practice Tools: Radial Dashboard */}
            <PracticeWheelSection />

            {/* 4. Continue Reading Sutras */}
            <ContinueReadingSection />
          </div>

          {/* 5. Online Prayer Halls - Full Bleed Edge-to-Edge */}
          <PrayerRoomsSection />

          {/* Bottom sections within standard container */}
          <div className="max-w-[1360px] mx-auto px-4 sm:px-6 w-full space-y-12">
            {/* 6. Buddhist Figures & Sacred Temples */}
            <Suspense
              fallback={
                <div className="space-y-8 py-4">
                  <div className="h-64 rounded-2xl bg-surface-container-low animate-pulse" />
                  <div className="h-64 rounded-2xl bg-surface-container-low animate-pulse" />
                </div>
              }
            >
              <FiguresAndTemplesLoader />
            </Suspense>

            {/* 7. Featured Articles & Global Community */}
            <section className="grid grid-cols-1 lg:grid-cols-12 gap-8 pt-4">
              <Suspense
                fallback={
                  <div className="lg:col-span-8 h-80 rounded-2xl bg-surface-container-low animate-pulse" />
                }
              >
                <ArticlesLoader />
              </Suspense>
              <CommunitySection />
            </section>
          </div>
        </div>
      </main>

      <Footer />
    </div>
  );
}
