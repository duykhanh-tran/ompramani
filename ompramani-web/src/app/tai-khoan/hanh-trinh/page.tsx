import React, { Suspense } from "react";
import type { Metadata } from "next";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import DharmaJourneyDashboard from "@/components/dharma-journey/DharmaJourneyDashboard";

export const metadata: Metadata = {
  title: "Hành Trình Phật Pháp | OmPraMani",
  description: "Tổng quan hành trình tu học, thời khóa hành trì và tiến trình tụng niệm cá nhân",
  robots: {
    index: false,
    follow: false,
  },
};

export default function HanhTrinhPage() {
  return (
    <div className="min-h-screen flex flex-col bg-surface">
      <Header />
      <main className="flex-1 px-4 sm:px-6 py-24 sm:py-28">
        <Suspense
          fallback={
            <div className="w-full max-w-5xl mx-auto space-y-6 animate-pulse">
              <div className="h-44 rounded-3xl bg-surface-container-low" />
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {[1, 2, 3, 4].map((n) => (
                  <div key={n} className="h-28 rounded-2xl bg-surface-container-low" />
                ))}
              </div>
              <div className="h-64 rounded-3xl bg-surface-container-low" />
            </div>
          }
        >
          <DharmaJourneyDashboard />
        </Suspense>
      </main>
      <Footer />
    </div>
  );
}
