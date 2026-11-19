import React, { Suspense } from 'react';
import type { Metadata } from 'next';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { PersonalPrayerManager } from '@/components/prayer/PersonalPrayerManager';

export const metadata: Metadata = {
  title: 'Lời Nguyện Của Tôi | OmPraMani',
  description: 'Quản lý sổ tay tâm nguyện tu học cá nhân trên OmPraMani',
  robots: {
    index: false,
    follow: false,
  },
};

export default function LoiNguyenTaiKhoanPage() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main className="w-full flex-1 pt-24 pb-16">
        <div className="max-w-[1360px] mx-auto px-4 sm:px-6">
          <Suspense
            fallback={
              <div className="max-w-4xl mx-auto space-y-6 animate-pulse">
                <div className="h-20 rounded-3xl bg-surface-container-low" />
                <div className="h-64 rounded-3xl bg-surface-container-low" />
              </div>
            }
          >
            <PersonalPrayerManager />
          </Suspense>
        </div>
      </main>

      <Footer />
    </div>
  );
}
