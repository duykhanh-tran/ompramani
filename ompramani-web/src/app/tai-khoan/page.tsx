import React, { Suspense } from "react";
import type { Metadata } from "next";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import AccountOverview from "@/components/auth/AccountOverview";

export const metadata: Metadata = {
  title: "Hồ Sơ Cá Nhân & Tu Học | OmPraMani",
  description: "Quản lý thông tin tài khoản tu học trên OmPraMani",
  robots: {
    index: false,
    follow: false,
  },
};

export default function TaiKhoanPage() {
  return (
    <div className="min-h-screen flex flex-col bg-[#fcfaf7]">
      <Header />
      <main id="main-content" className="flex-1 px-4 sm:px-6 lg:px-8 py-20 sm:py-24">
        <Suspense
          fallback={
            <div className="w-full max-w-6xl mx-auto p-6 space-y-6 animate-pulse">
              <div className="h-10 rounded-2xl bg-surface-container-low w-1/3" />
              <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <div className="lg:col-span-4 h-96 rounded-3xl bg-surface-container-low" />
                <div className="lg:col-span-8 h-96 rounded-3xl bg-surface-container-low" />
              </div>
            </div>
          }
        >
          <AccountOverview />
        </Suspense>
      </main>
      <Footer />
    </div>
  );
}
