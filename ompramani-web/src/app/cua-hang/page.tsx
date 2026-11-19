import React, { Suspense } from "react";
import type { Metadata } from "next";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { ShopCatalogClient } from "./ShopCatalogClient";

export const metadata: Metadata = {
  title: "Cửa Hàng Vật Phẩm Phật Giáo — OmPraMani",
  description:
    "Danh mục tràng hạt, chuông mõ pháp khí, kinh sách và hương trầm thanh tịnh phục vụ tu học và hành trì Phật pháp.",
  alternates: {
    canonical: "/cua-hang",
  },
  openGraph: {
    title: "Cửa Hàng Vật Phẩm Phật Giáo — OmPraMani",
    description:
      "Vật phẩm Phật giáo mộc mạc, trang nghiêm, cam kết chánh niệm và không thương mại hóa tâm linh.",
    url: "/cua-hang",
    siteName: "OmPraMani",
    locale: "vi_VN",
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Cửa Hàng Vật Phẩm Phật Giáo — OmPraMani",
    description: "Vật phẩm Phật giáo mộc mạc, trang nghiêm, cam kết chánh niệm và không thương mại hóa tâm linh.",
  },
};

export default function ShopCatalogPage() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="w-full flex-1 pt-16 pb-16">
        <Suspense
          fallback={
            <div className="p-12 text-center text-on-surface-variant font-body-md">
              Đang tải danh mục vật phẩm Phật giáo...
            </div>
          }
        >
          <ShopCatalogClient />
        </Suspense>
      </main>

      <Footer />
    </div>
  );
}
