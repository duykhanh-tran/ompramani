import React from "react";
import Link from "next/link";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";

export default function NotFound() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main
        id="main-content"
        className="flex-1 flex items-center justify-center px-4 py-24 sm:py-32"
      >
        <div className="max-w-md w-full text-center space-y-6">
          <div className="w-20 h-20 mx-auto rounded-full bg-surface-container-high border border-outline-variant/40 flex items-center justify-center shadow-xs">
            <span className="material-symbols-outlined text-[42px] text-primary">
              explore_off
            </span>
          </div>

          <div className="space-y-2">
            <p className="font-label-caps text-xs font-bold text-outline tracking-wider uppercase">
              Mã Lỗi 404
            </p>
            <h1 className="font-headline-xl text-2xl sm:text-3xl font-bold font-serif text-on-surface">
              Không Tìm Thấy Nội Dung
            </h1>
            <p className="font-body-md text-sm text-on-surface-variant leading-relaxed">
              Trang quý đạo hữu đang tìm kiếm không tồn tại, đã được lưu trữ hoặc đường dẫn đã thay đổi.
            </p>
          </div>

          <div className="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
            <Link
              href="/"
              className="w-full sm:w-auto px-6 py-2.5 rounded-full bg-primary text-on-primary font-label-md text-sm font-semibold hover:bg-primary-hover shadow-xs transition-colors flex items-center justify-center gap-2"
            >
              <span className="material-symbols-outlined text-[18px]">home</span>
              <span>Trang chủ</span>
            </Link>

            <Link
              href="/kinh"
              className="w-full sm:w-auto px-6 py-2.5 rounded-full border border-outline-variant/60 hover:bg-surface-container-low text-on-surface font-label-md text-sm font-medium transition-colors flex items-center justify-center gap-2"
            >
              <span className="material-symbols-outlined text-[18px]">menu_book</span>
              <span>Thư viện Kinh</span>
            </Link>
          </div>
        </div>
      </main>

      <Footer />
    </div>
  );
}
