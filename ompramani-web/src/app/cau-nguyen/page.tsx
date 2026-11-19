import React, { Suspense } from 'react';
import type { Metadata } from 'next';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { PrayerChatRoom } from '@/components/prayer/PrayerChatRoom';
import { getPrayerRoomMessages } from '@/lib/api/prayer-room';
import { PrayerRoomMessage, PrayerRoomMessagesMeta } from '@/types/prayer-room';

export const metadata: Metadata = {
  title: 'Phòng Lời Nguyện | OmPraMani',
  description:
    'Phòng chia sẻ lời nguyện cộng đồng, gửi lời nhắn đồng hành và cùng nhau hướng về sự bình an, từ bi.',
  robots: {
    index: false,
    follow: false,
  },
};

async function CommunityPrayerRoomContent() {
  let messages: PrayerRoomMessage[] = [];
  let meta: PrayerRoomMessagesMeta = {
    has_more_older: false,
    has_more_newer: false,
    oldest_id: null,
    newest_id: null,
  };

  try {
    const res = await getPrayerRoomMessages({ limit: 35 });
    if (res?.data) {
      messages = res.data;
      meta = res.meta;
    }
  } catch (err) {
    console.warn('Could not fetch initial prayer room messages:', err);
  }

  return <PrayerChatRoom initialMessages={messages} initialMeta={meta} />;
}

export default function CauNguyenPage() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="w-full flex-1 pt-20 pb-8 px-3 sm:px-6">
        <Suspense
          fallback={
            <div className="w-full max-w-[1000px] mx-auto h-[650px] rounded-3xl bg-surface-container-low border border-outline-variant/30 animate-pulse flex items-center justify-center text-outline">
              <span className="material-symbols-outlined text-4xl animate-spin">progress_activity</span>
            </div>
          }
        >
          <CommunityPrayerRoomContent />
        </Suspense>
      </main>

      <Footer />
    </div>
  );
}
