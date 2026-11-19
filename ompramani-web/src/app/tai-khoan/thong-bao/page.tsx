import React, { Suspense } from 'react';
import type { Metadata } from 'next';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { NotificationCenter } from '@/components/notification/NotificationCenter';

export const metadata: Metadata = {
  title: 'Thông Báo | OmPraMani',
  description: 'Quản lý thông báo trạng thái kiểm duyệt và hoạt động đồng hành của bạn.',
  robots: {
    index: false,
    follow: false,
  },
};

export default function ThongBaoPage() {
  return (
    <div className="min-h-screen flex flex-col bg-surface">
      <Header />
      <main className="flex-1 px-4 sm:px-6 py-24 sm:py-28">
        <Suspense
          fallback={
            <div className="w-full max-w-4xl mx-auto p-6 space-y-4 animate-pulse">
              <div className="h-10 bg-surface-container-low rounded-2xl w-48" />
              <div className="h-24 bg-surface-container-low rounded-2xl" />
              <div className="h-24 bg-surface-container-low rounded-2xl" />
            </div>
          }
        >
          <NotificationCenter />
        </Suspense>
      </main>
      <Footer />
    </div>
  );
}
