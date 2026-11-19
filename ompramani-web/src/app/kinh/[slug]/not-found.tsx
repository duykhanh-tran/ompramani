import React from "react";
import Link from "next/link";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";

export default function SutraNotFound() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main className="w-full bg-surface flex-1 pt-16 flex items-center justify-center p-6">
        <div className="bg-surface-container-lowest rounded-3xl p-10 max-w-md w-full text-center space-y-5 border border-outline-variant/30 shadow-sm">
          <div className="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center mx-auto text-primary">
            <span className="material-symbols-outlined text-[36px]">
              menu_book
            </span>
          </div>
          <div>
            <h1 className="font-headline-lg text-2xl font-bold text-on-surface">
              Không tìm thấy kinh văn
            </h1>
            <p className="font-body-sm text-body-sm text-on-surface-variant mt-2">
              Bài kinh bạn tìm kiếm không tồn tại, chưa được xuất bản hoặc đường
              dẫn không chính xác.
            </p>
          </div>
          <Link
            href="/kinh"
            className="inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-primary text-on-primary font-label-md text-label-md font-semibold hover:bg-on-primary-container transition-colors shadow-xs"
          >
            <span className="material-symbols-outlined text-[18px]">
              arrow_back
            </span>
            <span>Quay lại Thư Viện Kinh</span>
          </Link>
        </div>
      </main>

      <Footer />
    </div>
  );
}
