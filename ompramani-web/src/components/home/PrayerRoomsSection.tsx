import React from "react";
import Link from "next/link";
import Image from "next/image";

export function PrayerRoomsSection() {
  return (
    <section className="relative w-full overflow-hidden min-h-[460px] sm:min-h-[520px] md:min-h-[580px] lg:min-h-[620px] flex items-center border-y border-outline-variant/30 group">
      {/* 1. Ảnh nằm dưới cùng (tràn toàn bộ hai bên trái phải 100vw) */}
      <Image
        src="/images/prayer-room-banner.jpg"
        alt="Phòng Lời Nguyện Trực Tuyến - Quán Thế Âm Bồ Tát"
        fill
        priority
        sizes="100vw"
        className="object-cover object-[center_right] group-hover:scale-102 transition-transform duration-700"
      />

      {/* Lớp gradient tự nhiên loang dần từ trái sang phải để tôn chữ, giữ trong suốt ở bên phải nơi Bồ Tát ngự */}
      <div className="absolute inset-0 bg-gradient-to-r from-stone-950/85 via-stone-900/45 via-50% to-transparent pointer-events-none" />
      <div className="absolute inset-0 bg-gradient-to-t from-stone-950/40 via-transparent to-transparent pointer-events-none" />

      {/* Căn lề nội dung theo khung chuẩn 1360px của trang */}
      <div className="max-w-[1360px] mx-auto px-4 sm:px-6 w-full py-12 sm:py-16 md:py-20 relative z-10">
        <div className="max-w-xl sm:max-w-2xl space-y-4 sm:space-y-5">
          {/* Status Badge */}
          <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-amber-300 shadow-xs">
            <span className="w-2 h-2 rounded-full bg-amber-400 animate-pulse" />
            <span className="font-label-caps text-xs uppercase tracking-wider font-semibold">
              Không Gian Thanh Tịnh Trực Tuyến · Đang Diễn Ra
            </span>
          </div>

          <div>
            <span className="font-label-caps text-xs sm:text-sm uppercase tracking-wider text-amber-200/90 font-semibold block">
              Cầu An &amp; Hồi Hướng Thiện Lành
            </span>
            <h2 className="font-headline-xl text-3xl sm:text-5xl lg:text-6xl font-bold text-white tracking-tight font-serif drop-shadow-[0_2px_8px_rgba(0,0,0,0.7)] leading-[1.15] mt-1">
              Phòng Lời Nguyện Cộng Đồng
            </h2>
          </div>

          <p className="font-body-md text-base sm:text-lg text-white/90 leading-relaxed drop-shadow-[0_1px_4px_rgba(0,0,0,0.8)] max-w-xl font-normal">
            Nơi đồng đạo khắp muôn phương cùng nhau gửi lời nguyện bình an cho gia đạo, sẻ chia sự động viên và hồi hướng thiện lành trong tinh thần tương trợ lẫn nhau.
          </p>

          {/* Buttons nằm trực tiếp trên ảnh */}
          <div className="flex flex-wrap items-center gap-4 pt-3">
            <Link
              href="/cau-nguyen"
              className="px-7 py-3.5 rounded-full bg-primary hover:bg-primary/90 text-on-primary font-label-md text-sm font-semibold flex items-center gap-2.5 transition-all shadow-md hover:shadow-lg cursor-pointer ring-2 ring-primary/30"
            >
              <span className="material-symbols-outlined text-[20px]">
                folded_hands
              </span>
              <span>Vào Phòng Lời Nguyện</span>
            </Link>

            <Link
              href="/cau-nguyen"
              className="px-6 py-3.5 rounded-full bg-white/15 hover:bg-white/25 backdrop-blur-md text-white border border-white/30 font-label-md text-sm font-medium transition-all shadow-xs hover:shadow-sm"
            >
              <span>Xem lời nguyện cộng đồng</span>
            </Link>
          </div>
        </div>
      </div>
    </section>
  );
}
