import React, { Suspense } from 'react';
import { Metadata } from 'next';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { LichVanNienView } from '@/components/calendar/LichVanNienView';

export const metadata: Metadata = {
  title: 'Lịch Vạn Niên — Âm Lịch & Phật Lịch Việt Nam | OmPraMani',
  description:
    'Tra cứu lịch âm dương Việt Nam chuẩn giờ Hà Nội (UTC+07:00), ngày lễ Phật giáo, ngày vía Phật, Bồ Tát, ngày trai giới ăn chay và kệ pháp mỗi ngày.',
  alternates: {
    canonical: '/lich-van-nien',
  },
  openGraph: {
    title: 'Lịch Vạn Niên & Phật Lịch — OmPraMani',
    description:
      'Tra cứu lịch âm dương Việt Nam, ngày lễ Phật giáo, ngày vía chư Phật, Bồ Tát và ngày ăn chay theo chuẩn giờ Việt Nam.',
    url: '/lich-van-nien',
    type: 'website',
  },
  twitter: {
    card: 'summary_large_image',
    title: 'Lịch Vạn Niên & Phật Lịch — OmPraMani',
    description: 'Tra cứu lịch âm dương Việt Nam, ngày lễ Phật giáo, ngày vía chư Phật, Bồ Tát và ngày ăn chay.',
  },
};

interface LichVanNienPageProps {
  searchParams: Promise<{
    date?: string;
    month?: string;
  }>;
}

async function LichVanNienContent({ searchParams }: LichVanNienPageProps) {
  const params = await searchParams;
  return (
    <LichVanNienView
      initialDate={params.date}
      initialMonth={params.month}
    />
  );
}

export default function LichVanNienPage({ searchParams }: LichVanNienPageProps) {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="w-full flex-1 pt-16 bg-surface-container-lowest/30">
        <Suspense
          fallback={
            <div className="max-w-[1440px] mx-auto px-4 sm:px-6 py-6">
              <div className="h-8 w-60 bg-surface-container-low rounded-xl animate-pulse mb-6" />
              <div className="grid grid-cols-1 lg:grid-cols-12 gap-5">
                <div className="lg:col-span-7 h-[580px] bg-surface-container-low rounded-3xl animate-pulse" />
                <div className="lg:col-span-5 h-[580px] bg-surface-container-low rounded-3xl animate-pulse" />
              </div>
            </div>
          }
        >
          <LichVanNienContent searchParams={searchParams} />
        </Suspense>
      </main>

      <Footer />
    </div>
  );
}
