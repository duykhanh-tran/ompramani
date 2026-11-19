import React, { Suspense } from 'react';
import type { Metadata } from 'next';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { PracticePlannerManager } from '@/components/practice-planner/PracticePlannerManager';

export const metadata: Metadata = {
  title: 'Kế Hoạch Tu Tập Cá Nhân | OmPraMani',
  description:
    'Tự sắp xếp thời khóa tu học cá nhân: thiền định, niệm Phật, trì chú, tụng kinh phù hợp với nhịp sống và thời gian của bạn.',
  robots: {
    index: false,
    follow: false,
  },
};

export default function KeHoachTuTapPage() {
  return (
    <div className="min-h-screen flex flex-col bg-surface">
      <Header />
      <main className="flex-1 px-4 sm:px-6 py-24 sm:py-28">
        <Suspense
          fallback={
            <div className="w-full max-w-5xl mx-auto p-6 space-y-4 animate-pulse">
              <div className="h-10 bg-surface-container-low rounded-2xl w-48" />
              <div className="h-32 bg-surface-container-low rounded-3xl" />
              <div className="h-64 bg-surface-container-low rounded-3xl" />
            </div>
          }
        >
          <PracticePlannerManager />
        </Suspense>
      </main>
      <Footer />
    </div>
  );
}
