import React from "react";
import type { Metadata } from "next";
import Link from "next/link";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";

export const metadata: Metadata = {
  title: "Liên Hệ Ban Quản Trị & Hỗ Trợ Tu Học | OmPraMani",
  description:
    "Kênh tiếp nhận ý kiến đóng góp, hỗ trợ kỹ thuật và liên hệ cùng Ban Quản Trị nền tảng Phật giáo OmPraMani.",
  alternates: {
    canonical: "/contact",
  },
};

export default function ContactPage() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="flex-1 max-w-4xl mx-auto px-4 sm:px-6 pt-24 pb-16 w-full">
        <div className="space-y-8">
          {/* Header section */}
          <div className="text-center space-y-3 border-b border-outline-variant/30 pb-8">
            <span className="font-label-md text-xs font-semibold text-primary uppercase tracking-widest">
              Lắng Nghe &amp; Đồng Hành
            </span>
            <h1 className="font-headline-xl text-3xl sm:text-4xl font-bold font-serif text-primary">
              Liên Hệ Ban Quản Trị
            </h1>
            <p className="font-body-md text-sm text-on-surface-variant max-w-2xl mx-auto leading-relaxed">
              Mọi ý kiến đóng góp xây dựng chánh pháp, thông báo lỗi kỹ thuật hoặc tâm thư trao đổi xin vui lòng gửi về Ban Quản Trị OmPraMani.
            </p>
          </div>

          {/* Contact Cards */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div className="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/30 space-y-3">
              <div className="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                <span className="material-symbols-outlined text-[24px]">mail</span>
              </div>
              <h2 className="text-base font-bold font-serif text-primary">Thư Điện Tử Ban Hộ Pháp</h2>
              <p className="text-sm text-on-surface-variant">
                Tiếp nhận góp ý, hiệu đính kinh văn và đóng góp nội dung Phật sự:
              </p>
              <p className="text-sm font-semibold text-primary">contact@ompramani.com</p>
            </div>

            <div className="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/30 space-y-3">
              <div className="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                <span className="material-symbols-outlined text-[24px]">support_agent</span>
              </div>
              <h2 className="text-base font-bold font-serif text-primary">Hỗ Trợ Kỹ Thuật &amp; Đơn Hàng</h2>
              <p className="text-sm text-on-surface-variant">
                Hỗ trợ tra cứu đơn hàng, thanh toán SePay và tài khoản tu học:
              </p>
              <p className="text-sm font-semibold text-primary">hotro@ompramani.com</p>
            </div>
          </div>

          {/* Additional notes */}
          <div className="bg-surface-container-low/60 p-6 rounded-2xl border border-outline-variant/20 text-center space-y-2">
            <p className="font-quote-editorial text-sm italic text-primary">
              “Nguyện đem công đức này, hướng về khắp tất cả. Đệ tử và chúng sanh, đều trọn thành Phật đạo.”
            </p>
            <p className="text-xs text-outline">
              Thời gian phản hồi thông thường: Trong vòng 24 - 48 giờ làm việc.
            </p>
          </div>

          <div className="pt-4 text-center">
            <Link
              href="/"
              className="inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-primary text-on-primary font-medium hover:bg-primary-hover shadow-xs transition-colors"
            >
              <span className="material-symbols-outlined text-[18px]">arrow_back</span>
              <span>Trở về Trang Chủ</span>
            </Link>
          </div>
        </div>
      </main>

      <Footer />
    </div>
  );
}
