import React from "react";
import Link from "next/link";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";

export default function ArticleNotFound() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main className="w-full bg-surface flex-1 pt-16 flex items-center justify-center px-4">
        <div className="bg-surface-container-lowest rounded-3xl p-10 sm:p-12 text-center space-y-5 max-w-lg w-full border border-outline-variant/30 shadow-sm my-12">
          <div className="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center mx-auto text-primary">
            <span className="material-symbols-outlined text-[34px]">
              menu_book
            </span>
          </div>
          <h1 className="font-headline-xl text-2xl sm:text-3xl font-bold text-on-surface">
            Không tìm thấy bài viết
          </h1>
          <p className="font-body-md text-on-surface-variant">
            Bài viết hoặc pháp thoại bạn đang tìm kiếm không tồn tại hoặc đã được gỡ khỏi hệ thống công khai.
          </p>
          <div className="pt-2">
            <Link
              href="/bai-viet"
              className="inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-primary text-on-primary font-label-lg text-label-lg font-semibold hover:bg-on-primary-container transition-colors shadow-xs"
            >
              <span className="material-symbols-outlined text-[18px]">
                arrow_back
              </span>
              <span>Quay về Thư viện Bài Viết</span>
            </Link>
          </div>
        </div>
      </main>

      <Footer />
    </div>
  );
}
