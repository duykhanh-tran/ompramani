import React, { Suspense } from "react";
import type { Metadata } from "next";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { VideoLibraryClient } from "./VideoLibraryClient";

export const metadata: Metadata = {
  title: "Thư Viện Video Phật Pháp — OmPraMani",
  description:
    "Khám phá các pháp thoại, bài giảng và nội dung giáo dục Phật giáo được tuyển chọn từ những nguồn xác minh trên OmPraMani.",
  alternates: {
    canonical: "/video-phat-phap",
  },
  openGraph: {
    title: "Thư Viện Video Phật Pháp — OmPraMani",
    description:
      "Tuyển tập pháp thoại, hướng dẫn thiền và giáo lý Phật giáo chính thống.",
    url: "/video-phat-phap",
    siteName: "OmPraMani",
    locale: "vi_VN",
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Thư Viện Video Phật Pháp — OmPraMani",
    description: "Tuyển tập pháp thoại, hướng dẫn thiền và giáo lý Phật giáo chính thống.",
  },
};

export default function VideoLibraryPage() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="w-full flex-1 pt-16 pb-16">
        <Suspense
          fallback={
            <div className="p-12 text-center text-on-surface-variant font-body-md">
              Đang tải thư viện video Phật pháp...
            </div>
          }
        >
          <VideoLibraryClient />
        </Suspense>
      </main>

      <Footer />
    </div>
  );
}
