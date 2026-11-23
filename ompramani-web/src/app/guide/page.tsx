import React from "react";
import type { Metadata } from "next";
import Link from "next/link";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";

export const metadata: Metadata = {
  title: "Hướng Dẫn Sử Dụng Nền Tảng Tu Học Số | OmPraMani",
  description:
    "Cẩm nang hướng dẫn sử dụng thư viện kinh điển, tràng hạt điện tử, chuông mõ khai tâm và các pháp bảo trực tuyến trên OmPraMani.",
  alternates: {
    canonical: "/guide",
  },
};

export default function GuidePage() {
  const guideItems = [
    {
      title: "Thư Viện Kinh Điển & Tụng Niệm",
      icon: "menu_book",
      desc: "Tra cứu toàn văn các bản kinh Đại thừa, Tịnh Độ và Mật chú. Hỗ trợ tùy chỉnh cỡ chữ, âm thanh tụng niệm và chế độ chuyên chú.",
      href: "/kinh",
    },
    {
      title: "Tràng Hạt Niệm Phật Điện Tử",
      icon: "adjust",
      desc: "Lần chuỗi hạt trên màn hình cảm ứng hoặc phím cách (Space), tự động đếm số biến trì niệm và lưu vào sổ tu học cá nhân.",
      href: "/trang-hat",
    },
    {
      title: "Chuông Mõ Khai Tâm Online",
      icon: "notifications",
      desc: "Gõ chuông, mõ với âm thanh thanh tịnh thu từ chùa cổ truyền thống. Giúp hành giả giữ nhịp tụng niệm trang nghiêm tại gia.",
      href: "/chuong-mo",
    },
    {
      title: "Thiền Định Quán Niệm Hơi Thở",
      icon: "self_improvement",
      desc: "Đồng hồ hẹn giờ thiền định, chuông chánh niệm định kỳ và hướng dẫn thở an tịnh tâm ý.",
      href: "/thien",
    },
    {
      title: "Phòng Cầu Nguyện & Gửi Tâm Nguyện",
      icon: "favorite",
      desc: "Gửi ý niệm cầu an, cầu siêu và cùng đạo hữu khắp năm châu đồng tâm chú nguyện hồi hướng.",
      href: "/cau-nguyen",
    },
    {
      title: "Tra Cứu Chùa Viện Toàn Quốc",
      icon: "temple_buddhist",
      desc: "Tìm kiếm các ngôi cổ tự, thiền viện, tu viện theo tỉnh thành, xem bản đồ đường đi và thông tin Phật sự.",
      href: "/tim-chua",
    },
  ];

  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="flex-1 max-w-5xl mx-auto px-4 sm:px-6 pt-24 pb-16 w-full">
        <div className="space-y-8">
          {/* Header section */}
          <div className="text-center space-y-3 border-b border-outline-variant/30 pb-8">
            <span className="font-label-md text-xs font-semibold text-primary uppercase tracking-widest">
              Cẩm Nang Thực Hành
            </span>
            <h1 className="font-headline-xl text-3xl sm:text-4xl font-bold font-serif text-primary">
              Hướng Dẫn Sử Dụng Cổng Web
            </h1>
            <p className="font-body-md text-sm text-on-surface-variant max-w-2xl mx-auto leading-relaxed">
              Khám phá các phương tiện thiện xảo được OmPraMani kiến tạo nhằm hỗ trợ quý hành giả tinh tấn tu học mọi lúc, mọi nơi.
            </p>
          </div>

          {/* Guide Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {guideItems.map((item) => (
              <Link
                key={item.href}
                href={item.href}
                className="group bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/30 hover:border-primary/50 hover:shadow-md transition-all flex flex-col justify-between"
              >
                <div className="space-y-3">
                  <div className="w-11 h-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center group-hover:bg-primary group-hover:text-on-primary transition-colors">
                    <span className="material-symbols-outlined text-[24px]">{item.icon}</span>
                  </div>
                  <h2 className="text-base font-bold font-serif text-on-surface group-hover:text-primary transition-colors">
                    {item.title}
                  </h2>
                  <p className="text-sm text-on-surface-variant leading-relaxed">
                    {item.desc}
                  </p>
                </div>
                <div className="pt-4 flex items-center gap-1.5 text-xs font-semibold text-primary">
                  <span>Trải nghiệm ngay</span>
                  <span className="material-symbols-outlined text-[16px] group-hover:translate-x-1 transition-transform">
                    arrow_forward
                  </span>
                </div>
              </Link>
            ))}
          </div>

          <div className="pt-6 text-center">
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
