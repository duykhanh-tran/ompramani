import React, { Suspense } from "react";
import type { Metadata } from "next";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { PaymentResultClient } from "./PaymentResultClient";

interface PaymentResultPageProps {
  params: Promise<{ publicId: string }>;
}

export const metadata: Metadata = {
  title: "Kết Quả Thanh Toán — OmPraMani",
  description: "Trạng thái xác thực giao dịch thanh toán trực tuyến tại OmPraMani.",
  robots: {
    index: false,
    follow: false,
  },
};

async function PaymentResultContent({ params }: PaymentResultPageProps) {
  const { publicId } = await params;
  return <PaymentResultClient publicId={publicId} />;
}

export default function PaymentResultPage({ params }: PaymentResultPageProps) {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="w-full flex-1 pt-20 pb-16">
        <div className="max-w-[1360px] mx-auto px-4 sm:px-6">
          <Suspense
            fallback={
              <div className="max-w-xl mx-auto py-16 px-4 space-y-6 animate-pulse text-center">
                <div className="w-16 h-16 mx-auto rounded-full bg-surface-container-high" />
                <div className="h-8 bg-surface-container-high rounded-2xl w-48 mx-auto" />
                <div className="h-24 bg-surface-container-low rounded-3xl" />
              </div>
            }
          >
            <PaymentResultContent params={params} />
          </Suspense>
        </div>
      </main>

      <Footer />
    </div>
  );
}
