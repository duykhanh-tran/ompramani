import React, { Suspense } from "react";
import type { Metadata } from "next";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { CheckoutClient } from "./CheckoutClient";

export const metadata: Metadata = {
  title: "Đặt Hàng & Thanh Toán — OmPraMani",
  description:
    "Xác nhận đơn hàng và thông tin giao nhận vật phẩm tu học Phật giáo thanh tịnh tại OmPraMani.",
  robots: {
    index: false,
    follow: false,
  },
  alternates: {
    canonical: "/thanh-toan",
  },
};

export default function CheckoutPage() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="w-full flex-1 pt-20 pb-16">
        <div className="max-w-[1360px] mx-auto px-4 sm:px-6">
          <Suspense
            fallback={
              <div className="max-w-4xl mx-auto py-16 px-4 text-center space-y-4">
                <div className="h-8 bg-surface-container-high rounded-xl w-64 mx-auto animate-pulse" />
                <div className="p-12 text-center text-on-surface-variant font-body-md">
                  Đang tải trang thanh toán...
                </div>
              </div>
            }
          >
            <CheckoutClient />
          </Suspense>
        </div>
      </main>

      <Footer />
    </div>
  );
}