import React from "react";
import type { Metadata } from "next";
import Link from "next/link";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";

export const metadata: Metadata = {
  title: "Quy Chế Bảo Vệ Chánh Pháp & Điều Khoản Sử Dụng | OmPraMani",
  description:
    "Quy chế hoạt động, nguyên tắc gìn giữ không gian tu học thanh tịnh và điều khoản sử dụng nền tảng Phật giáo OmPraMani.",
  alternates: {
    canonical: "/terms",
  },
};

export default function TermsPage() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="flex-1 max-w-4xl mx-auto px-4 sm:px-6 pt-24 pb-16 w-full">
        <div className="space-y-8">
          {/* Header section */}
          <div className="text-center space-y-3 border-b border-outline-variant/30 pb-8">
            <span className="font-label-md text-xs font-semibold text-primary uppercase tracking-widest">
              Nguyên Tắc Cộng Đồng Tu Học
            </span>
            <h1 className="font-headline-xl text-3xl sm:text-4xl font-bold font-serif text-primary">
              Quy Chế Bảo Vệ Chánh Pháp
            </h1>
            <p className="font-body-md text-sm text-on-surface-variant max-w-2xl mx-auto leading-relaxed">
              OmPraMani là không gian tu tập, đọc tụng kinh điển và kết nối tâm thiện lành dành cho cộng đồng Phật tử.
              Mọi hoạt động trên nền tảng đều hướng tới tinh thần hòa hợp, chánh niệm và trí tuệ.
            </p>
          </div>

          {/* Content sections */}
          <div className="space-y-6 text-on-surface leading-relaxed text-sm sm:text-base">
            <section className="bg-surface-container-lowest p-6 sm:p-8 rounded-2xl border border-outline-variant/30 space-y-3">
              <h2 className="text-lg font-bold font-serif text-primary flex items-center gap-2">
                <span className="material-symbols-outlined text-[20px]">verified</span>
                1. Tôn Trọng Chánh Pháp &amp; Kinh Điển
              </h2>
              <p className="text-on-surface-variant">
                Kinh văn, thần chú và giáo lý trên nền tảng được trích dẫn cẩn trọng từ các bản dịch truyền thống Tam Tạng Kinh Điển.
                Thành viên tham gia tu học cam kết không xuyên tạc, bóp méo hoặc truyền bá tà kiến, mê tín dị đoan đi ngược lại tinh thần Từ bi - Hỷ xả của Đạo Phật.
              </p>
            </section>

            <section className="bg-surface-container-lowest p-6 sm:p-8 rounded-2xl border border-outline-variant/30 space-y-3">
              <h2 className="text-lg font-bold font-serif text-primary flex items-center gap-2">
                <span className="material-symbols-outlined text-[20px]">groups</span>
                2. Gìn Giữ Khẩu Nghiệp Thanh Tịnh
              </h2>
              <p className="text-on-surface-variant">
                Tại các khu vực cộng đồng như Cầu Nguyện, Thảo Luận và Phòng Tu Tập Trực Tuyến, Phật tử giữ trọn chánh ngữ.
                Nghiêm cấm các phát ngôn kích động thù hận, công kích cá nhân, tranh cãi thế tục hoặc quảng cáo thương mại trái phép.
              </p>
            </section>

            <section className="bg-surface-container-lowest p-6 sm:p-8 rounded-2xl border border-outline-variant/30 space-y-3">
              <h2 className="text-lg font-bold font-serif text-primary flex items-center gap-2">
                <span className="material-symbols-outlined text-[20px]">volunteer_activism</span>
                3. Thiện Nguyện &amp; Ấn Tống Pháp Bảo
              </h2>
              <p className="text-on-surface-variant">
                Mọi hoạt động thỉnh vật phẩm, ấn tống kinh sách hay cúng dường Tam Bảo đều được thực hiện trên tinh thần tự nguyện, minh bạch và hồi hướng công đức vì sự an lạc của muôn loài.
              </p>
            </section>

            <section className="bg-surface-container-lowest p-6 sm:p-8 rounded-2xl border border-outline-variant/30 space-y-3">
              <h2 className="text-lg font-bold font-serif text-primary flex items-center gap-2">
                <span className="material-symbols-outlined text-[20px]">security</span>
                4. Quyền Hạn Của Ban Điều Hành
              </h2>
              <p className="text-on-surface-variant">
                Ban Quản Trị có quyền xóa bỏ các nội dung vi phạm thuần phong mỹ tục, xuyên tạc chánh pháp và tạm ngừng tài khoản người dùng có hành vi phương hại đến không gian tu học chung.
              </p>
            </section>
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
