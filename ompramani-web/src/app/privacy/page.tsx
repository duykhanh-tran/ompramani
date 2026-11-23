import React from "react";
import type { Metadata } from "next";
import Link from "next/link";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";

export const metadata: Metadata = {
  title: "Chính Sách Thanh Tịnh & Quyền Riêng Tư | OmPraMani",
  description:
    "Cam kết bảo vệ dữ liệu cá nhân, thông tin tu học và lời nguyện của Phật tử trên nền tảng OmPraMani.",
  alternates: {
    canonical: "/privacy",
  },
};

export default function PrivacyPage() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="flex-1 max-w-4xl mx-auto px-4 sm:px-6 pt-24 pb-16 w-full">
        <div className="space-y-8">
          {/* Header section */}
          <div className="text-center space-y-3 border-b border-outline-variant/30 pb-8">
            <span className="font-label-md text-xs font-semibold text-primary uppercase tracking-widest">
              Bảo Mật &amp; Minh Bạch
            </span>
            <h1 className="font-headline-xl text-3xl sm:text-4xl font-bold font-serif text-primary">
              Chính Sách Thanh Tịnh &amp; Riêng Tư
            </h1>
            <p className="font-body-md text-sm text-on-surface-variant max-w-2xl mx-auto leading-relaxed">
              Chúng tôi trân trọng và tôn kính từng giây phút tu học, lời hồi hướng và dữ liệu cá nhân của quý đạo hữu.
              Nền tảng cam kết bảo vệ sự thanh tịnh và an toàn tuyệt đối cho hành trình tâm linh của bạn.
            </p>
          </div>

          {/* Content sections */}
          <div className="space-y-6 text-on-surface leading-relaxed text-sm sm:text-base">
            <section className="bg-surface-container-lowest p-6 sm:p-8 rounded-2xl border border-outline-variant/30 space-y-3">
              <h2 className="text-lg font-bold font-serif text-primary flex items-center gap-2">
                <span className="material-symbols-outlined text-[20px]">lock</span>
                1. Bảo Mật Thông Tin Tu Học
              </h2>
              <p className="text-on-surface-variant">
                Tiến trình tụng kinh, số biến trì chú, nhật ký quán chiếu và lời nguyện cá nhân được lưu trữ bảo mật bằng kiến trúc xác thực đa lớp.
                Dữ liệu tu học của quý vị thuộc về riêng quý vị và chỉ được hiển thị công khai khi quý vị chủ động chia sẻ.
              </p>
            </section>

            <section className="bg-surface-container-lowest p-6 sm:p-8 rounded-2xl border border-outline-variant/30 space-y-3">
              <h2 className="text-lg font-bold font-serif text-primary flex items-center gap-2">
                <span className="material-symbols-outlined text-[20px]">no_sim</span>
                2. Cam Kết Không Thương Mại Hóa Dữ Liệu
              </h2>
              <p className="text-on-surface-variant">
                OmPraMani cam kết không bao giờ bán, cho thuê hoặc chia sẻ dữ liệu cá nhân của người dùng cho bất kỳ bên quảng cáo thứ ba nào.
                Chúng tôi hướng tới việc duy trì một không gian thanh tịnh không vướng bận rác thông tin thương mại.
              </p>
            </section>

            <section className="bg-surface-container-lowest p-6 sm:p-8 rounded-2xl border border-outline-variant/30 space-y-3">
              <h2 className="text-lg font-bold font-serif text-primary flex items-center gap-2">
                <span className="material-symbols-outlined text-[20px]">shield</span>
                3. An Toàn Giao Dịch &amp; Cổng Thanh Toán
              </h2>
              <p className="text-on-surface-variant">
                Mọi giao dịch thỉnh pháp bảo và cúng dường qua cổng thanh toán SePay / Chuyển khoản QR đều được mã hóa bằng chuẩn SSL/TLS an toàn cao nhất.
                Hệ thống không lưu trữ thông tin thẻ hay mật khẩu ngân hàng của khách hàng.
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
