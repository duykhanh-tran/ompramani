import React, { Suspense } from "react";
import type { Metadata } from "next";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import PersonalReadingLibrary from "@/components/sutra/PersonalReadingLibrary";

export const metadata: Metadata = {
  title: "Tủ Kinh Cá Nhân | OmPraMani",
  description: "Quản lý tiến trình đọc kinh, kinh đã lưu và các phẩm đã đánh dấu",
  robots: {
    index: false,
    follow: true,
  },
};

export default function ThuVienKinhPage() {
  return (
    <div className="min-h-screen flex flex-col bg-surface">
      <Header />
      <main className="flex-1 px-4 sm:px-6 py-24 sm:py-28">
        <Suspense
          fallback={
            <div className="w-full max-w-4xl mx-auto p-6 space-y-6 animate-pulse">
              <div className="h-40 rounded-3xl bg-surface-container-low" />
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="h-32 rounded-2xl bg-surface-container-low" />
                <div className="h-32 rounded-2xl bg-surface-container-low" />
              </div>
            </div>
          }
        >
          <PersonalReadingLibrary />
        </Suspense>
      </main>
      <Footer />
    </div>
  );
}
