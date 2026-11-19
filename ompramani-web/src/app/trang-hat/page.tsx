import React, { Suspense } from "react";
import type { Metadata } from "next";
import { MalaClient } from "./MalaClient";

export const metadata: Metadata = {
  title: "Tràng Hạt Điện Tử 108 Hạt — Pháp Khí Tu Tập | OmPraMani",
  description:
    "Công cụ tràng hạt điện tử 108 hạt trực quan, thanh tịnh. Hỗ trợ đếm hạt, đếm vòng, niệm Phật và trì chú chánh niệm cùng OmPraMani.",
  alternates: {
    canonical: "/trang-hat",
  },
  openGraph: {
    title: "Tràng Hạt Điện Tử 108 Hạt — Pháp Khí Tu Tập | OmPraMani",
    description: "Công cụ tràng hạt điện tử 108 hạt trực quan, thanh tịnh. Hỗ trợ đếm hạt, đếm vòng, niệm Phật và trì chú.",
    url: "/trang-hat",
    siteName: "OmPraMani",
    locale: "vi_VN",
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Tràng Hạt Điện Tử 108 Hạt — Pháp Khí Tu Tập | OmPraMani",
    description: "Công cụ tràng hạt điện tử 108 hạt trực quan, thanh tịnh.",
  },
};

export default function MalaPage() {
  return (
    <main id="main-content" className="min-h-screen bg-background">
      <Suspense
        fallback={
          <div className="max-w-md mx-auto px-4 py-20 text-center flex flex-col items-center justify-center">
            <div className="w-12 h-12 border-3 border-primary border-t-transparent rounded-full animate-spin mb-4" />
            <p className="font-label-md text-sm text-outline">
              Đang tải tràng hạt điện tử...
            </p>
          </div>
        }
      >
        <MalaClient />
      </Suspense>
    </main>
  );
}
