import React, { Suspense } from "react";
import type { Metadata } from "next";
import { MeditationClient } from "./MeditationClient";

export const metadata: Metadata = {
  title: "Quán Niệm Hơi Thở — Thiền Định Chánh Niệm | OmPraMani",
  description:
    "Thực hành thiền định quán niệm hơi thở (Anapanasati) cùng OmPraMani. Nuôi dưỡng định lực, an trú trong hiện tại và ghi nhận tiến trình tu tập cá nhân.",
  alternates: {
    canonical: "/thien",
  },
  openGraph: {
    title: "Quán Niệm Hơi Thở — Thiền Định Chánh Niệm | OmPraMani",
    description: "Thực hành thiền định quán niệm hơi thở (Anapanasati) cùng OmPraMani. Nuôi dưỡng định lực, an trú trong hiện tại.",
    url: "/thien",
    siteName: "OmPraMani",
    locale: "vi_VN",
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Quán Niệm Hơi Thở — Thiền Định Chánh Niệm | OmPraMani",
    description: "Thực hành thiền định quán niệm hơi thở cùng OmPraMani.",
  },
};

export default function MeditationPage() {
  return (
    <main id="main-content" className="min-h-screen bg-background">
      <Suspense
        fallback={
          <div className="max-w-3xl mx-auto px-4 py-20 text-center flex flex-col items-center justify-center">
            <div className="w-12 h-12 border-3 border-primary border-t-transparent rounded-full animate-spin mb-4" />
            <p className="font-label-md text-sm text-outline">
              Đang tải công cụ thiền định...
            </p>
          </div>
        }
      >
        <MeditationClient />
      </Suspense>
    </main>
  );
}
