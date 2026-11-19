import React, { Suspense } from 'react';
import type { Metadata } from 'next';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { PracticeReminderManager } from '@/components/practice-reminder/PracticeReminderManager';

export const metadata: Metadata = {
  title: 'Nhắc Nhở Tu Tập Cá Nhân | OmPraMani',
  description: 'Thiết lập và quản lý lời nhắc thời khóa tu tập cá nhân: thiền định, niệm Phật, trì chú, tụng kinh.',
  robots: {
    index: false,
    follow: false,
  },
};

export default function NhacNhoTuTapPage() {
  return (
    <div className="min-h-screen flex flex-col bg-surface">
      <Header />
      <main className="flex-1 px-4 sm:px-6 py-24 sm:py-28">
        <Suspense
          fallback={
            <div className="w-full max-w-4xl mx-auto p-6 space-y-4 animate-pulse">
              <div className="h-10 bg-surface-container-low rounded-2xl w-48" />
              <div className="h-28 bg-surface-container-low rounded-3xl" />
              <div className="h-28 bg-surface-container-low rounded-3xl" />
            </div>
          }
        >
          <PracticeReminderManager />
        </Suspense>
      </main>
      <Footer />
    </div>
  );
}
