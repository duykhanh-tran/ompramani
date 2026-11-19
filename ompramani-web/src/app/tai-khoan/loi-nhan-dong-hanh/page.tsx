import React, { Suspense } from 'react';
import type { Metadata } from 'next';
import Link from 'next/link';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { PersonalPrayerResponsesManager } from '@/components/prayer/PersonalPrayerResponsesManager';

export const metadata: Metadata = {
  title: 'Lời Nhắn Đồng Hành Đã Gửi | OmPraMani',
  description: 'Quản lý các lời nhắn động viên, chia sẻ thiện lành đã gửi trên OmPraMani',
  robots: {
    index: false,
    follow: false,
  },
};

export default function LoiNhanDongHanhTaiKhoanPage() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main className="w-full flex-1 pt-24 pb-16">
        <div className="max-w-[1360px] mx-auto px-4 sm:px-6">
          <div className="max-w-4xl mx-auto space-y-6">
            {/* Header Navigation */}
            <div className="flex items-center justify-between gap-4">
              <Link
                href="/tai-khoan/loi-nguyen"
                className="inline-flex items-center gap-1.5 text-primary hover:text-on-primary-container font-label-md text-sm font-semibold transition-colors"
              >
                <span className="material-symbols-outlined text-[18px]">arrow_back</span>
                <span>Trở lại Sổ Tay Lời Nguyện</span>
              </Link>
            </div>

            {/* Header Title Banner */}
            <div className="rounded-3xl bg-surface-container-lowest border border-outline-variant/40 p-6 sm:p-8 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div className="space-y-1">
                <span className="font-label-caps text-xs text-primary uppercase tracking-wider font-semibold">
                  Tâm Nguyện Đồng Hành
                </span>
                <h1 className="font-headline-md text-2xl sm:text-3xl font-bold text-on-surface">
                  Lời Nhắn Đã Gửi
                </h1>
                <p className="font-body-md text-sm text-outline max-w-xl">
                  Theo dõi trạng thái kiểm duyệt, chỉnh sửa hoặc rút lại các lời nhắn động viên thiện lành quý vị đã chia sẻ với cộng đồng.
                </p>
              </div>

              <Link
                href="/cau-nguyen"
                className="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-full bg-primary text-on-primary hover:bg-primary-hover font-label-md text-xs font-semibold shadow-xs shrink-0 transition-colors"
              >
                <span className="material-symbols-outlined text-[16px]">explore</span>
                <span>Khám phá Lời Nguyện</span>
              </Link>
            </div>

            {/* Manager View */}
            <Suspense
              fallback={
                <div className="space-y-3 py-4 animate-pulse">
                  {[1, 2, 3].map((i) => (
                    <div key={i} className="h-28 rounded-2xl bg-surface-container-low" />
                  ))}
                </div>
              }
            >
              <PersonalPrayerResponsesManager />
            </Suspense>
          </div>
        </div>
      </main>

      <Footer />
    </div>
  );
}
