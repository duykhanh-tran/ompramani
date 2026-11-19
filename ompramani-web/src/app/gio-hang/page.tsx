import React, { Suspense } from "react";
import type { Metadata } from "next";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { CartPageClient } from "@/components/cart/CartPageClient";

export const metadata: Metadata = {
  title: "Giỏ Hàng Vật Phẩm — OmPraMani",
  description:
    "Xem lại các vật phẩm tu học, pháp khí và kinh sách quý đạo hữu đã chọn tại OmPraMani.",
  robots: {
    index: false,
    follow: false,
  },
  alternates: {
    canonical: "/gio-hang",
  },
  openGraph: {
    title: "Giỏ Hàng Vật Phẩm — OmPraMani",
    description: "Giỏ hàng vật phẩm tu học Phật giáo thanh tịnh, chánh niệm.",
    url: "/gio-hang",
    siteName: "OmPraMani",
    locale: "vi_VN",
    type: "website",
  },
};

export default function CartPage() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="w-full flex-1 pt-20 pb-16">
        <div className="max-w-[1360px] mx-auto px-4 sm:px-6">
          <Suspense
            fallback={
              <div className="p-12 text-center text-on-surface-variant font-body-md">
                Đang tải giỏ hàng...
              </div>
            }
          >
            <CartPageClient />
          </Suspense>
        </div>
      </main>

      <Footer />
    </div>
  );
}
