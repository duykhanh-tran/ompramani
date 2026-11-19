import React, { Suspense } from "react";
import type { Metadata } from "next";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { OrderListClient } from "./OrderListClient";

export const metadata: Metadata = {
  title: "Đơn Hàng Của Tôi — OmPraMani",
  description: "Xem và quản lý lịch sử đơn hàng vật phẩm tu học của quý đạo hữu trên OmPraMani.",
  robots: {
    index: false,
    follow: false,
  },
  alternates: {
    canonical: "/tai-khoan/don-hang",
  },
};

export default function MyOrdersPage() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main className="w-full flex-1 pt-24 pb-16">
        <div className="max-w-[1360px] mx-auto px-4 sm:px-6">
          <Suspense
            fallback={
              <div className="max-w-4xl mx-auto py-16 space-y-6 animate-pulse">
                <div className="h-8 bg-surface-container-high rounded-xl w-60" />
                <div className="h-12 bg-surface-container-low rounded-2xl" />
                <div className="h-40 bg-surface-container-low rounded-3xl" />
              </div>
            }
          >
            <OrderListClient />
          </Suspense>
        </div>
      </main>

      <Footer />
    </div>
  );
}