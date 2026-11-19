import React, { Suspense } from 'react';
import type { Metadata } from 'next';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { NotificationPreferencesForm } from '@/components/notification/NotificationPreferencesForm';

export const metadata: Metadata = {
  title: 'Cài Đặt Thông Báo | OmPraMani',
  description: 'Tùy chỉnh các tùy chọn nhận thông báo cá nhân trên OmPraMani.',
  robots: {
    index: false,
    follow: false,
  },
};

export default function CaiDatThongBaoPage() {
  return (
    <div className="min-h-screen flex flex-col bg-surface">
      <Header />
      <main className="flex-1 px-4 sm:px-6 py-24 sm:py-28">
        <Suspense
          fallback={
            <div className="w-full max-w-2xl mx-auto p-6 space-y-4 animate-pulse">
              <div className="h-8 bg-surface-container-low rounded-xl w-48" />
              <div className="h-40 bg-surface-container-low rounded-2xl" />
            </div>
          }
        >
          <NotificationPreferencesForm />
        </Suspense>
      </main>
      <Footer />
    </div>
  );
}
