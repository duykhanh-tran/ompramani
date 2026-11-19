import React, { Suspense } from "react";
import type { Metadata } from "next";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { TempleDirectoryClient } from "./TempleDirectoryClient";

export const metadata: Metadata = {
  title: "Chùa Việt Nam — Danh Lam Tự Viện & Đạo Tràng | OmPraMani",
  description:
    "Khám phá danh lam tự viện, thiền viện, tịnh xá và đạo tràng Phật giáo với thông tin đã được xác minh chính thống.",
  alternates: {
    canonical: "/tim-chua",
  },
  openGraph: {
    title: "Chùa Việt Nam — Danh Lam Tự Viện & Đạo Tràng | OmPraMani",
    description: "Khám phá danh lam tự viện, thiền viện, tịnh xá và đạo tràng Phật giáo với thông tin đã được xác minh chính thống.",
    url: "/tim-chua",
    siteName: "OmPraMani",
    locale: "vi_VN",
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Chùa Việt Nam — Danh Lam Tự Viện & Đạo Tràng | OmPraMani",
    description: "Khám phá danh lam tự viện, thiền viện, tịnh xá và đạo tràng Phật giáo với thông tin đã được xác minh chính thống.",
  },
};

export default function TempleDirectoryPage() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="w-full flex-1 pt-16 pb-16">
        <Suspense
          fallback={
            <div className="p-12 text-center text-on-surface-variant font-body-md">
              Đang tải thư viện địa điểm Phật giáo...
            </div>
          }
        >
          <TempleDirectoryClient />
        </Suspense>
      </main>

      <Footer />
    </div>
  );
}
