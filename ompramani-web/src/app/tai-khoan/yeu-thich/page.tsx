import React, { Suspense } from "react";
import type { Metadata } from "next";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { FavoritesLibraryClient } from "./FavoritesLibraryClient";

export const metadata: Metadata = {
  title: "Nội Dung Yêu Thích | OmPraMani",
  description: "Những nội dung kinh Phật, bài viết và video bạn đã lưu để thuận tiện xem lại.",
  robots: {
    index: false,
    follow: true,
  },
};

export default function YeuThichPage() {
  return (
    <div className="min-h-screen flex flex-col bg-surface">
      <Header />
      <main className="flex-1 px-4 sm:px-6 py-24 sm:py-28">
        <Suspense
          fallback={
            <div className="w-full max-w-6xl mx-auto p-6 space-y-6 animate-pulse">
              <div className="h-32 rounded-3xl bg-surface-container-low" />
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div className="h-72 rounded-3xl bg-surface-container-low" />
                <div className="h-72 rounded-3xl bg-surface-container-low" />
                <div className="h-72 rounded-3xl bg-surface-container-low" />
              </div>
            </div>
          }
        >
          <FavoritesLibraryClient />
        </Suspense>
      </main>
      <Footer />
    </div>
  );
}
