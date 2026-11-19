import React, { Suspense } from "react";
import type { Metadata } from "next";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { OrderConfirmationClient } from "./OrderConfirmationClient";

interface OrderConfirmationPageProps {
  params: Promise<{ publicId: string }>;
}

export const metadata: Metadata = {
  title: "Xác Nhận Đơn Hàng — OmPraMani",
  description: "Thông tin chi tiết đơn hàng vật phẩm tu học tại OmPraMani.",
  robots: {
    index: false,
    follow: false,
  },
};

async function OrderConfirmationContent({ params }: OrderConfirmationPageProps) {
  const { publicId } = await params;
  return <OrderConfirmationClient publicId={publicId} />;
}

export default function OrderConfirmationPage({ params }: OrderConfirmationPageProps) {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="w-full flex-1 pt-20 pb-16">
        <div className="max-w-[1360px] mx-auto px-4 sm:px-6">
          <Suspense
            fallback={
              <div className="max-w-3xl mx-auto py-16 px-4 space-y-6 animate-pulse">
                <div className="h-24 bg-surface-container-high rounded-3xl" />
                <div className="h-64 bg-surface-container-low rounded-3xl" />
                <div className="h-48 bg-surface-container-low rounded-3xl" />
              </div>
            }
          >
            <OrderConfirmationContent params={params} />
          </Suspense>
        </div>
      </main>

      <Footer />
    </div>
  );
}